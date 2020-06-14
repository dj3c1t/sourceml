<?php

namespace Sourceml\Form\Sources\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Sourceml\Entity\Sources\Author;

class AuthorType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add(
            'name',
            TextType::class,
            array(
                'label' => 'Name'
            )
        );
        $builder->add(
            'description',
            TextareaType::class,
            array(
                'label' => 'Description'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'data_class' => Author::class,
                'translation_domain' => 'sourceml',
            )
        );
    }

}
