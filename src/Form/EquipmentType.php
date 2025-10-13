<?php

namespace App\Form;

use App\Entity\Equipment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;  // ✅ ADD THIS
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; 

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Equipment_Type', ChoiceType::class, [
        'choices' => [
            'Lighting' => 'Lighting',
            'Sound System' => 'Sound System',
            'Stage' => 'Stage',
            'Visuals / LED Wall' => 'Visuals / LED Wall',
            'Special Effects' => 'Special Effects',
            'Others' => 'Others',
        ],
        'placeholder' => 'Select Equipment Type',
        'label' => 'Equipment Type',
    ])
            ->add('Equipment')
            ->add('Availability')
            ->add('Price')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
