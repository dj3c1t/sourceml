<?php

namespace Sourceml\Form\Sources\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Sourceml\Entity\Sources\Licence;

class LicenceType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add(
            'name',
            TextType::class,
            array(
                'label' => 'Name'
            )
        );
        $builder->add(
            'url',
            TextType::class,
            array(
                'label' => 'URL'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'data_class' => Licence::class,
                'translation_domain' => 'sourceml',
            )
        );
    }

}
