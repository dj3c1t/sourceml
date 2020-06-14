<?php

namespace Sourceml\Form\Sources\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceInfo;
use Sourceml\Entity\Sources\SourceAuthor;
use Sourceml\Entity\Sources\Licence;
use Sourceml\Entity\Sources\Author;

class SourceType extends AbstractType {

    private $em;
    private $sm;
    private $sourceRepo;
    private $licenceRepo;
    private $source;
    private $isNew;

    private $container;
    private $user;

    private $options;

    public function setContainer() {
        if($this->options["container"] === null) {
            throw new \Exception("container passed in options to SourceType is null");
        }
        $this->container = $this->options["container"];
        $this->em = $this->container->get('doctrine')->getManager();
        $this->sm = $this->container->get('sourceml.source_manager');
        $this->sourceRepo = $this->em->getRepository(Source::class);
        $this->licenceRepo = $this->em->getRepository(Licence::class);
        if(!($this->user = $this->container->get('security.token_storage')->getToken()->getUser())) {
            throw new \Exception("user must be logged in to access source form");
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $this->options = $options;
        $this->setContainer();
        $this->source = $builder->getData();
        $this->isNew = ($this->source->getId()) === null;
        if($this->isNew || $this->options["isReference"]) {
            $option = array(
                'class' => Author::class,
                'label' => $this->options["isReference"] ? 'Imported by' : 'Author',
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')
                        ->join('a.user', 'u')
                        ->where('u.id = :user_id')
                        ->setParameter('user_id', $this->user->getId());
                },
            );
            if($this->options["isReference"]) {
                $option["data"] = $this->source->getImportedBy();
            }
            $builder->add(
                'author',
                EntityType::class,
                $option
            );
        }
        if($this->options["isReference"]) {
            $builder->add(
                'referenceUrl',
                TextType::class,
                array(
                    'label' => 'XML URL'
                )
            );
        }
        else {
            if(($description = $this->source->getInfo("description")) === null) {
                $description = "";
            }
            $builder->add(
                'title',
                TextType::class,
                array(
                    'label' => 'Title'
                )
            );
            $builder->add(
                'description',
                TextareaType::class,
                array(
                    'label' => 'Description',
                    'data' => $description,
                    'mapped' => false,
                )
            );

            if(
                $this->licenceRepo->createQueryBuilder('l')
                    ->select('COUNT(l)')
                    ->getQuery()->getSingleScalarResult()
            ) {
                $builder->add(
                    'licence',
                    EntityType::class,
                    array(
                        'class' => Licence::class,
                        'label' => 'Licence',
                    )
                );
            }
        }
        $compositionType = null;
        $compositionLabel = "";
        switch($this->source->getSourceType()->getName()) {
            case "track":
                $compositionType = $this->sm->getSourceType("album");
                $compositionLabel = "In album";
                break;
            case "source":
                $compositionType = $this->sm->getSourceType("track");
                $compositionLabel = "In track";
                break;
        }
        if(isset($compositionType)) {
            $composition = null;
            if(!$this->isNew) {
                foreach($this->source->getCompositions() as $sourceComposition) {
                    $composition = $sourceComposition->getComposition();
                    break;
                }
            }
            if(
                    $this->isNew
                ||  !isset($composition)
                ||  $this->sm->userCan("contribute", $composition)
            ) {
                if(
                    $compositions = $this->sourceRepo->getSourceQuery(
                        array(
                            "user" => $this->user,
                            "sourceType" => $compositionType->getName(),
                            "isReference" => false,
                        )
                    )->getResult()
                ) {
                    $builderParams = array(
                        'class' => Source::class,
                        'label' => $compositionLabel,
                        'mapped' => false,
                        'choices' => $compositions,
                        'required' => false,
                    );
                    if(isset($composition)) {
                        $builderParams["data"] = $composition;
                    }
                    $builder->add(
                        'composition',
                        EntityType::class,
                        $builderParams
                    );
                }
            }
        }
        $builder->addEventListener(
            FormEvents::SUBMIT,
            array($this, 'onSubmit')
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            array($this, 'onPreSubmit')
        );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'data_class' => Source::class,
                'translation_domain' => 'sourceml',
                'isReference' => false,
                'container' => null,
            )
        );
    }

    public function onPreSubmit(FormEvent $event) {
        if(!$this->isNew) {
            if(!$this->sm->userCan("edit", $this->source)) {
                throw new \Exception("your are not allowed to edit this source");
            }
        }
    }

    public function onSubmit(FormEvent $event) {
        $form = $event->getForm();

        // SourceInfo : description

        if(!$this->options["isReference"]) {
            $sourceInfo = null;
            foreach($this->source->getInfos() as $info) {
                if($info->getInfoKey() == "description") {
                    $sourceInfo = $info;
                }
            }
            if(!isset($sourceInfo)) {
                $sourceInfo = new SourceInfo();
                $sourceInfo->setSource($this->source);
                $sourceInfo->setInfoKey("description");
                $this->em->persist($sourceInfo);
                $this->source->addInfo($sourceInfo);
            }
            $sourceInfo->setInfoValue($form->get('description')->getData());
        }

        // SourceAuthor

        if($this->isNew || $this->options["isReference"]) {
            if($authors = $this->source->getAuthors()) {
                foreach($authors as $sourceAuthor) {
                    $this->source->removeAuthor($sourceAuthor);
                    $this->em->remove($sourceAuthor);
                }
            }
            if(!($author = $form->get('author')->getData())) {
                throw new \Exception("source author is required");
            }
            $sourceAuthor = new SourceAuthor();
            $sourceAuthor->setAuthor($author);
            $sourceAuthor->setSource($this->source);
            $sourceAuthor->setAuthorRole($this->sm->getAuthorRole("admin"));
            $sourceAuthor->setIsValid(true);
            $this->em->persist($sourceAuthor);
            $this->source->addAuthor($sourceAuthor);
        }

        // composition

        if($form->has('composition')) {
            $this->sm->setComposition($this->source, $form->get('composition')->getData());
        }

    }

}
