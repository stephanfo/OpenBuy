<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SupplierType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'supplier.label.name',
                'translation_domain' => 'form',
            ))
            ->add('addressLine1', TextType::class, array(
                'label' => 'supplier.label.address_line_1',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('addressLine2', TextType::class, array(
                'label' => 'supplier.label.address_line_2',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('addressLine3', TextType::class, array(
                'label' => 'supplier.label.address_line_3',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('postcode', TextType::class, array(
                'label' => 'supplier.label.postcode',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('city', TextType::class, array(
                'label' => 'supplier.label.city',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('state', TextType::class, array(
                'label' => 'supplier.label.state',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('country', TextType::class, array(
                'label' => 'supplier.label.country',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('interface', ChoiceType::class, array(
                'choices' => array(
                    'supplier.interface.choice.digikey' => 'digikey'
                ),
                'required' => false,
                'placeholder' => 'supplier.interface.placeholder',
                'label' => 'supplier.label.interface',
                'translation_domain' => 'form',
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Supplier'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_supplier';
    }


}
