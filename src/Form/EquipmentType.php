<?php

namespace App\Form;

use App\Entity\Equipment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ✅ Equipment Type with a real disabled placeholder
            ->add('Equipment_Type', ChoiceType::class, [
                'choices' => [
                    'Lighting' => 'Lighting',
                    'Sound System' => 'Sound System',
                    'Stage' => 'Stage',
                    'Visuals / LED Wall' => 'Visuals / LED Wall',
                    'Special Effects' => 'Special Effects',
                    
                ],
                'label' => 'Equipment Type',
                'placeholder' => false, // disable Symfony’s default placeholder
                'attr' => ['class' => 'form-select'],
            ])
            ->add('Equipment', null, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('Price', null, [
                'attr' => ['class' => 'form-control'],
            ])
            // ✅ Availability with a disabled first option (placeholder)
            ->add('Availability', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'label' => 'Availability',
                'placeholder' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
