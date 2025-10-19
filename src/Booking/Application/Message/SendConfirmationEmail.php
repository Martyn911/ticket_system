<?php

namespace App\Booking\Application\Message;

class SendConfirmationEmail
{
    public function __construct(
        public readonly string $bookingId,
        public readonly string $clientEmail,
        public readonly int $ticketNumber
    ) {}
}
