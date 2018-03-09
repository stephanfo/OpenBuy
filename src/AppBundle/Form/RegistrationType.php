<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, array(
                'label' => 'user.label.firstname',
                'translation_domain' => 'form',
            ))
            ->add('lastname', TextType::class, array(
                'label' => 'user.label.lastname',
                'translation_domain' => 'form',
            ))
            ->add('jobrole', TextType::class, array(
                'required' => false,
                'label' => 'user.label.jobrole',
                'translation_domain' => 'form',
            ))
            ->add('phone', TextType::class, array(
                'required' => false,
                'label' => 'user.label.phone',
                'translation_domain' => 'form',
            ))
            ->add('mobile', TextType::class, array(
                'required' => false,
                'label' => 'user.label.mobile',
                'translation_domain' => 'form',
            ))
            ->add('address', TextType::class, array(
                'required' => false,
                'label' => 'user.label.address',
                'translation_domain' => 'form',
            ))
            ->add('location', TextType::class, array(
                'required' => false,
                'label' => 'user.label.location',
                'translation_domain' => 'form',
            ))
            ->add('country', TextType::class, array(
                'required' => false,
                'label' => 'user.label.country',
                'translation_domain' => 'form',
            ))
            ->add('longitude', TextType::class, array(
                'required' => false,
                'label' => 'user.label.longitude',
                'translation_domain' => 'form',
            ))
            ->add('latitude', TextType::class, array(
                'required' => false,
                'label' => 'user.label.latitude',
                'translation_domain' => 'form',
            ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_profile';
    }
}