<?php

namespace App\Booking\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class ClientId
{
    public function __construct(
        private Uuid $value,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
