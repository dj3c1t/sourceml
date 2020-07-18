<?php

namespace Sourceml\Controller\Sources\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceComposition;
use Sourceml\Entity\Sources\SourceType;
use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\AuthorRole;
//use Sourceml\Form\Sources\Type\SourceType as SourceFormType;

class SourceController extends Controller {

    public function indexAction(Request $request, $sourceType) {
        $em = $this->get('doctrine')->getManager();
        $sm = $this->get('sourceml.source_manager');
        $queryParams = array(
            "sourceType" => $sourceType,
            "user" => $this->getUser(),
            "userCan" => "contribute",
        );
        if($request->query->get('author')) {
            $queryParams['author'] = $request->query->get('author');
        }
        if($request->query->get('composition')) {
            $queryParams['composition'] = $request->query->get('composition');
        }
        if(isset($queryParams['composition'])) {
            $sources = $sm->getSourceQuery($queryParams)->getResult();
        }
        else {
            $sources = $this->get('knp_paginator')->paginate(
                $sm->getSourceQuery($queryParams),
                $request->query->getInt('page', 1),
                10
            );
        }
        $authorRepo = $em->getRepository(Author::class);
        $authors = $authorRepo->findBy(
            array(
                "user" => $this->getUser()->getId()
            )
        );
        $compositions = array();
        $compositionType = null;
        switch($sourceType) {
            case "track":
                $compositionType = $sm->getSourceType("album");
                break;
            case "source":
                $compositionType = $sm->getSourceType("track");
                break;
        }
        if(isset($compositionType)) {
            $compositions = $sm->getSourceQuery(
                array(
                    "sourceType" => $compositionType->getName(),
                    "user" => $this->getUser(),
                    "userCan" => "contribute",
                    "isReference" => false,
                )
            )->getResult();
        }
        return $this->render(
            'Sources/Account/Source/index.html.twig',
            array(
                'sourceType' => $sourceType,
                'sources' => $sources,
                'authors' => $authors,
                'compositions' => $compositions,
            )
        );
    }

    public function orderAction(Request $request, $sourceType) {
        $em = $this->get('doctrine')->getManager();
        $sm = $this->get('sourceml.source_manager');
        $sourceRepo = $em->getRepository(Source::class);
        $sourceCompositionRepo = $em->getRepository(SourceComposition::class);
        $composition_id = 0;
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            try {
                if(!isset($data["composition"]) || !isset($data["position"])) {
                    throw new \Exception("missing parameter");
                }
                if(!($composition = $sourceRepo->find($data["composition"]))) {
                    throw new \Exception("can't find composition ".$source_id);
                }
                $composition_id = $composition->getId();
                if(!$sm->userCan('edit', $composition)) {
                    throw new AccessDeniedException("your are not allowed to edit this source");
                }
                foreach($data["position"] as $source_id => $position) {
                    if(
                        !(
                            $sourceComposition = $sourceCompositionRepo->findOneBy(
                                array(
                                    "source" => $source_id,
                                    "composition" => $composition,
                                )
                            )
                        )
                    ) {
                        throw new \Exception("can't find sourceComposition for source ".$source_id);
                    }
                    $sourceComposition->setPosition($position);
                }
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', "L'ordre a été enregistré");
            }
            catch(\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }
        return $this->redirect(
            $this->generateUrl(
                'account_source_index',
                array(
                    "sourceType" => $sourceType,
                    "composition" => $composition_id,
                )
            )
        );
    }

    public function addAction(Request $request, $sourceType) {
        $em = $this->get('doctrine')->getManager();
        $authorRepo = $em->getRepository(Author::class);
        if(!$authorRepo->findBy(["user" => $this->getUser()])) {
            $this->get('session')->getFlashBag()->add(
                "info",
                "Vous devez ajouter un auteur avant d'ajouter une source"
            );
            return $this->redirect($this->get('router')->generate('account_author_add'));
        }
        $sm = $this->get('sourceml.source_manager');
        $source = new Source();
        if(!($SourceType = $sm->getSourceType($sourceType))) {
            throw new \Exception("can't find '".$sourceType."' source type");
        }
        $source->setSourceType($SourceType);
        $form = $this->createForm(
            \Sourceml\Form\Sources\Type\SourceType::class,
            $source,
            array(
                "container" => $this->container,
            )
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($source);
                    $em->flush();
                    $upload_manager = $this->get('jq_file_upload.upload_manager');
                    $upload_manager->init("sourceml_source_image", $source->getId());
                    $media = $upload_manager->makeMediaFromFiles("image");
                    if(isset($media)) {
                        if($error = $media->getError()) {
                            $em->remove($source);
                            $em->flush();
                            throw new \Exception($error);
                        }
                        $em->persist($media);
                        $source->setImage($media);
                        $em->flush();
                    }
                    $this->get('session')->getFlashBag()->add('success', "La source a été ajoutée");
                    return $this->redirect(
                        $this->generateUrl(
                            'account_source_edit',
                            array(
                                "sourceType" => $sourceType,
                                "source" => $source->getId()
                            )
                        )
                    );
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Account/Source/add.html.twig',
            array(
                "form" => $form->createView(),
                'sourceType' => $sourceType,
                "source" => $source,
            )
        );
    }

    public function addReferenceAction(Request $request, $sourceType) {
        $em = $this->get('doctrine')->getManager();
        $authorRepo = $em->getRepository(Author::class);
        if(!$authorRepo->findBy(["user" => $this->getUser()])) {
            $this->get('session')->getFlashBag()->add(
                "info",
                "Vous devez ajouter un auteur avant d'ajouter une source"
            );
            return $this->redirect($this->get('router')->generate('account_author_add'));
        }
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->canOpenUrl()) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'cannot read external url from the server'
            );
            return $this->redirect(
                $this->generateUrl(
                    'account_source_index',
                    array(
                        "sourceType" => $sourceType
                    )
                )
            );
        }
        $em = $this->get('doctrine')->getManager();
        $source = new Source();
        if(!($SourceType = $sm->getSourceType($sourceType))) {
            throw new \Exception("can't find '".$sourceType."' source type");
        }
        $source->setSourceType($SourceType);
        $form = $this->createForm(
            \Sourceml\Form\Sources\Type\SourceType::class,
            $source,
            array(
                "container" => $this->container,
                "isReference" => true,
            )
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($source);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "La source a été ajoutée");
                    return $this->redirect(
                        $this->generateUrl(
                            'account_source_edit',
                        	array(
                                "sourceType" => $sourceType,
                                "source" => $source->getId()
                            )
                        )
                    );
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Account/Source/addReference.html.twig',
            array(
                "form" => $form->createView(),
                'sourceType' => $sourceType,
                "source" => $source,
            )
        );
    }

    public function editAction(Request $request, $sourceType, Source $source) {
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("contribute", $source)) {
            throw new AccessDeniedException("your are not allowed to edit this source");
        }
        $em = $this->get('doctrine')->getManager();
        if($sm->userCan("edit", $source)) {
            $form = $this->createForm(
                \Sourceml\Form\Sources\Type\SourceType::class,
                $source,
                array(
                    "container" => $this->container,
                    "isReference" => $source->isReference(),
                )
            );
            $form->handleRequest($request);
            if($form->isSubmitted()) {
                if($form->isValid()) {
                    try {
                        $em->flush();
                        $this->get('session')->getFlashBag()->add('success', "La source a été enregistrée");
                        return $this->redirect(
                            $this->generateUrl(
                                'account_source_edit',
                                array(
                                    "sourceType" => $sourceType,
                                    "source" => $source->getId()
                                )
                            )
                        );
                    }
                    catch(\Exception $e) {
                        $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                    }
                }
            }
            if($source->isReference()) {
                return $this->render(
                    'Sources/Account/Source/editReference.html.twig',
                    array(
                        "form" => $form->createView(),
                        'sourceType' => $sourceType,
                        "source" => $source,
                    )
                );
            }
            $sourceAuthors = array();
            foreach($source->getAuthors() as $sourceAuthor) {
                $sourceAuthors[] = $sourceAuthor->getAuthor()->getId();
            }
            $qb = $em->createQueryBuilder();
            $authors = $qb->select('a')
                ->from('Sourceml\Entity\Sources\Author', 'a')
                ->where($qb->expr()->notIn('a.id', $sourceAuthors))
                ->getQuery()
                ->getResult();
            return $this->render(
                'Sources/Account/Source/edit.html.twig',
                array(
                    "form" => $form->createView(),
                    'sourceType' => $sourceType,
                    "source" => $source,
                    "authors" => $authors,
                    "authorRoles" => $em->getRepository(AuthorRole::class)->findAll(),
                )
            );
        }
        return $this->render(
            'Sources/Account/Source/editAsContributor.html.twig',
            array(
                'sourceType' => $sourceType,
                "source" => $source,
            )
        );
    }

    public function deleteAction(Request $request, $sourceType, Source $source) {
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("admin", $source)) {
            throw new AccessDeniedException("your are not allowed to delete this source");
        }
        try {
            $sm->remove($source);
            $this->get('session')->getFlashBag()->add('success', "La source a été supprimée");
        }
        catch(\Excption $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect(
            $this->generateUrl(
                'account_source_index',
                array(
                    "sourceType" => $sourceType
                )
            )
        );
    }

}
