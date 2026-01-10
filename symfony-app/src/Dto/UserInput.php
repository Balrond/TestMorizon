<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UserInput
{
    #[Assert\NotBlank(message: 'First name is required.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'First name must be at least {{ limit }} characters.',
        maxMessage: 'First name cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: "/^[\p{L}\p{M}][\p{L}\p{M}\s'-]*$/u",
        message: "First name contains invalid characters."
    )]
    public ?string $first_name = null;

    #[Assert\NotBlank(message: 'Last name is required.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Last name must be at least {{ limit }} characters.',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: "/^[\p{L}\p{M}][\p{L}\p{M}\s'-]*$/u",
        message: "Last name contains invalid characters."
    )]
    public ?string $last_name = null;

    #[Assert\NotBlank(message: 'Gender is required.')]
    #[Assert\Choice(
        choices: ['male', 'female'],
        message: 'Gender must be either male or female.'
    )]
    public ?string $gender = null;

    #[Assert\NotBlank(message: 'Birthdate is required.')]
    #[Assert\Date(message: 'Birthdate must be a valid date (YYYY-MM-DD).')]
    #[Assert\Range(
        min: '1970-01-01',
        max: '2024-12-31',
        notInRangeMessage: 'Birthdate must be between {{ min }} and {{ max }}.'
    )]
    public ?string $birthdate = null;
}
