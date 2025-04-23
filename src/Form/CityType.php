<?php

namespace App\Form;

use App\Entity\City;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null,[
                'required'=>false,
                'label'=>'Nom de la ville',
                'attr'=>['class'=>'form form-control', 'placeholder'=>'nom de la ville']
            ])
            ->add('shippingCost', null,[
                'label'=>'shipping cost',
                'attr'=>['class'=>'form form-control', 'placeholder'=>'shipping cost']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }
}
