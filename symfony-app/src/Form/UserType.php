<?php

namespace App\Form;

use App\Dto\UserInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'First name',
                'trim' => true,
                'attr' => [
                    'autocomplete' => 'given-name',
                    'maxlength' => 255,
                    'placeholder' => 'e.g. Adam',
                ],
                'help' => 'Only letters, spaces, hyphens and apostrophes.',
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Last name',
                'trim' => true,
                'attr' => [
                    'autocomplete' => 'family-name',
                    'maxlength' => 255,
                    'placeholder' => 'e.g. Kowalski',
                ],
                'help' => 'Only letters, spaces, hyphens and apostrophes.',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                ],
                'placeholder' => 'Choose…',
                'attr' => [
                    'autocomplete' => 'sex',
                ],
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Birthdate',
                'widget' => 'single_text',
                'input' => 'string', // zwraca "YYYY-MM-DD" do DTO/API
                'help' => 'Allowed range: 1970-01-01 to 2024-12-31.',
                'attr' => [
                    'min' => '1970-01-01',
                    'max' => '2024-12-31',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserInput::class,
        ]);
    }
}
