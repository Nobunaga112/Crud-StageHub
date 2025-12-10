<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['placeholder' => 'Enter unique username'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'user@stagehub.com'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'User Role',
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Staff Member' => 'ROLE_STAFF',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => $isEdit ? false : true,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $isEdit ? 'Leave empty to keep current password' : 'Enter password',
                ],
                'help' => $isEdit ? 'Leave blank to keep current password' : 'Minimum 6 characters',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Account Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Inactive accounts cannot log in to the system.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
        
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}