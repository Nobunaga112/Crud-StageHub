<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Equipment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Customer_Name')
            ->add('Customer_Email')
            ->add('Start_Date')
            ->add('End_Date')
            ->add('Status')
            ->add('Equipment', EntityType::class, [
                'class' => Equipment::class,
                'choice_label' => 'Equipment',
                'placeholder' => 'Select equipment',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
