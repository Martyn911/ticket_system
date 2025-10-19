<?php

namespace App\Booking\Application\Command;

class BookTicketCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $clientId,
        public readonly string $clientEmail
    ) {}
}
