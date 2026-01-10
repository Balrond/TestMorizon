<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UserFilters
{
    #[Assert\Length(max: 255)]
    public ?string $first_name = null;

    #[Assert\Length(max: 255)]
    public ?string $last_name = null;

    #[Assert\Choice(choices: ['male', 'female'])]
    public ?string $gender = null;

    #[Assert\Date]
    #[Assert\Range(
        min: '1970-01-01',
        max: '2024-12-31',
        notInRangeMessage: 'Birthdate must be between {{ min }} and {{ max }}.'
    )]
    public ?string $birthdate_from = null;

    #[Assert\Date]
    #[Assert\Range(
        min: '1970-01-01',
        max: '2024-12-31',
        notInRangeMessage: 'Birthdate must be between {{ min }} and {{ max }}.'
    )]
    public ?string $birthdate_to = null;

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if ($this->birthdate_from && $this->birthdate_to) {
            if ($this->birthdate_from > $this->birthdate_to) {
                $context
                    ->buildViolation('Birthdate "from" must be earlier than "to".')
                    ->atPath('birthdate_from')
                    ->addViolation();
            }
        }
    }
}
