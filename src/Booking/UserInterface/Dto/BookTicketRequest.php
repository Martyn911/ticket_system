<?php

namespace App\Booking\UserInterface\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BookTicketRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $eventId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $clientId,

        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $clientEmail,
    ) {}
}
