<?php

namespace App\Booking\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final class EventId
{
    private Uuid $value;

    private function __construct(Uuid $value)
    {
        $this->value = $value;
    }

    // Factory methods for creation
    public static function fromString(string $id): self
    {
        // Here you may validate that the string is a valid UUID
        return new self(Uuid::fromString($id));
    }

    public static function generate(): self
    {
        return new self(Uuid::v4());
    }

    // Method to get the underlying value
    public function toString(): string
    {
        return $this->value->toRfc4122();
    }

    // Key VO requirement: equality semantics
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
