<?php

namespace App\Booking\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $clientId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $eventId;

    #[ORM\Column(type: 'integer')]
    private int $ticketNumber;

    public function __construct(Uuid $id, Uuid $clientId, Uuid $eventId, int $ticketNumber)
    {
        $this->id = $id; // use provided id, do not regenerate
        $this->clientId = $clientId;
        $this->eventId = $eventId;
        $this->ticketNumber = $ticketNumber;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicketNumber(): int
    {
        return $this->ticketNumber;
    }
}
