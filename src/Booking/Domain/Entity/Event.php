<?php

namespace App\Booking\Domain\Entity;

use App\Booking\Domain\Exception\TicketsSoldOut;
use App\Booking\Domain\ValueObject\Ticket;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Event
{
    #[ORM\Id, ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $totalTickets;

    #[ORM\Column(type: 'integer')]
    private int $soldTickets = 0;

    // Add a field for optimistic locking (to handle race conditions)
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version;

    public function __construct(string $name, int $totalTickets)
    {
        $this->id = Uuid::v4();
        $this->name = $name;
        $this->totalTickets = $totalTickets;
    }

    /**
     * @throws TicketsSoldOut
     */
    public function bookTicket(string $clientId): Ticket
    {
        // CORE BUSINESS RULE (INVARIANT)
        if ($this->soldTickets >= $this->totalTickets) {
            throw new TicketsSoldOut('No tickets left for event: '.$this->name);
        }

        ++$this->soldTickets;

        // Create a Ticket (Value Object)
        return new Ticket($this->id, Uuid::fromString($clientId), $this->soldTickets);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
