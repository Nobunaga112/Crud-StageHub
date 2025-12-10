<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Equipment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Customer_Name', TextType::class, [
                'label' => 'Customer Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter customer name'
                ]
            ])
            ->add('Customer_Email', EmailType::class, [
                'label' => 'Customer Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'customer@example.com'
                ]
            ])
            ->add('Start_Date', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control datepicker'
                ]
            ])
            ->add('End_Date', DateType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control datepicker'
                ]
            ])
            ->add('Status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Completed' => 'completed'
                ],
                'placeholder' => 'Select status',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('Equipment', EntityType::class, [
                'class' => Equipment::class,
                'choice_label' => 'Equipment',
                'placeholder' => 'Select equipment',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}