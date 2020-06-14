<?php

namespace Sourceml\Form\App\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class UserType extends AbstractType implements DataMapperInterface {

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $builder->setDataMapper($this);
        $builder->add(
            'username',
            TextType::class,
            array(
                'label' => 'Login'
            )
        );
        $builder->add(
            'email',
            TextType::class,
            array(
                'label' => 'Email'
            )
        );
        if($options["withAdminFields"]) {
            $builder->add(
                'roles',
                EntityType::class,
                array(
                    'label' => 'Roles',
                    'class' => \Sourceml\Entity\App\Role::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                )
            );
        }
        $builder->add(
            'changePassword',
            CheckboxType::class,
            array(
                'mapped' => false,
                'label' => 'Change password',
                'required' => false,
            )
        );
        $builder->add(
            'password',
            RepeatedType::class,
            array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match',
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Password (confirm)'),
            )
        );
        if($options["withAdminFields"]) {
            $builder->add(
                'isActive',
                CheckboxType::class,
                array(
                    'label' => 'Active account'
                )
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'data_class' => 'Sourceml\Entity\App\User',
                'translation_domain' => 'app',
                'withAdminFields' => false,
            )
        );
    }

    public function mapDataToForms($data, $forms) {
        $empty = null === $data || array() === $data;
        if(!$empty && !is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }
        foreach($forms as $name => $form) {
            if($name == "roles") {
                $form->setData($data->getRolesEntities());
            }
            else {
                $propertyPath = $form->getPropertyPath();
                $config = $form->getConfig();
                if (!$empty && null !== $propertyPath && $config->getMapped()) {
                    $form->setData($this->propertyAccessor->getValue($data, $propertyPath));
                } else {
                    $form->setData($form->getConfig()->getData());
                }
            }
        }
    }

    public function mapFormsToData($forms, &$data) {
        if(null === $data) {
            return;
        }
        if(!is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }
        foreach($forms as $name => $form) {
            if($name == "roles") {
            }
            else {
                $propertyPath = $form->getPropertyPath();
                $config = $form->getConfig();
                if(null !== $propertyPath && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                    if($form->getData() instanceof \DateTime && $form->getData() == $this->propertyAccessor->getValue($data, $propertyPath)) {
                        continue;
                    }
                    if(!is_object($data) || !$config->getByReference() || $form->getData() !== $this->propertyAccessor->getValue($data, $propertyPath)) {
                        $this->propertyAccessor->setValue($data, $propertyPath, $form->getData());
                    }
                }
            }
        }
    }

}
