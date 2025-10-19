<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Event;

interface EventRepositoryInterface
{
    public function find(string $id): ?Event;

    public function save(Event $event): void;
}
