<?php

namespace App\Booking\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * Value Object, що представляє виданий квиток.
 * Його ідентичність визначається комбінацією EventId та номера квитка.
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
     * Перевіряє, чи два об'єкти Ticket є рівними.
     * Два квитки рівні, якщо їхні атрибути рівні.
     */
    public function equals(self $other): bool
    {
        return $this->eventId->equals($other->eventId)
            && $this->ticketNumber === $other->ticketNumber;
        // clientId можна не включати в рівність, якщо він не є частиною унікальності квитка
    }
}
