<?php

namespace App\Booking\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * Value Object representing an issued ticket.
 * Its identity is determined by the combination of EventId and ticket number.
 */
class Ticket
{
    private Uuid $eventId;
    private Uuid $clientId;
    private int $ticketNumber;

    public function __construct(Uuid $eventId, Uuid $clientId, int $ticketNumber)
    {
        $this->eventId = $eventId;
        $this->clientId = $clientId;
        $this->ticketNumber = $ticketNumber;
    }

    public function getEventId(): Uuid
    {
        return $this->eventId;
    }

    public function getClientId(): Uuid
    {
        return $this->clientId;
    }

    public function getNumber(): int
    {
        return $this->ticketNumber;
    }

    /**
     * Checks whether two Ticket objects are equal.
     * Two tickets are equal if their attributes are equal.
     */
    public function equals(self $other): bool
    {
        return $this->eventId->equals($other->eventId)
            && $this->ticketNumber === $other->ticketNumber;
        // clientId may be excluded from equality if it is not part of the ticket's uniqueness
    }
}
