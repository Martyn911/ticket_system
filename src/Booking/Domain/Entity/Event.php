<?php

namespace App\Booking\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Booking\Domain\ValueObject\Ticket;
use App\Booking\Domain\Exception\TicketsSoldOut;
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

    // ДОДАЄМО ПОЛЕ ДЛЯ ОПТИМІСТИЧНОГО БЛОКУВАННЯ (Race Condition)
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
        // ГОЛОВНЕ БІЗНЕС-ПРАВИЛО (ІНВАРІАНТ)
        if ($this->soldTickets >= $this->totalTickets) {
            throw new TicketsSoldOut('No tickets left for event: ' . $this->name);
        }

        $this->soldTickets++;

        // Створюємо Квиток (Value Object)
        return new Ticket($this->id, Uuid::fromString($clientId), $this->soldTickets);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
