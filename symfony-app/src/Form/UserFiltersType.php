<?php

namespace App\Form;

use App\Dto\UserFilters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserFiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('first_name', TextType::class, [
                'label' => 'First name',
                'required' => false,
                'trim' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'e.g. Adam',
                    'maxlength' => 255,
                    'autocomplete' => 'off',
                ],
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Last name',
                'required' => false,
                'trim' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'e.g. Kowalski',
                    'maxlength' => 255,
                    'autocomplete' => 'off',
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'required' => false,
                'placeholder' => 'Any',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                ],
            ])
            ->add('birthdate_from', DateType::class, [
                'label' => 'Birthdate from',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'string',
                'attr' => [
                    'min' => '1970-01-01',
                    'max' => '2024-12-31',
                ],
            ])
            ->add('birthdate_to', DateType::class, [
                'label' => 'Birthdate to',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'string',
                'attr' => [
                    'min' => '1970-01-01',
                    'max' => '2024-12-31',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserFilters::class,
            'csrf_protection' => false, 
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
