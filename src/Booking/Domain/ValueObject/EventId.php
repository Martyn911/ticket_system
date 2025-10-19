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

    // Фабричні методи для створення
    public static function fromString(string $id): self
    {
        // Тут може бути валідація, що рядок є валідним UUID
        return new self(Uuid::fromString($id));
    }

    public static function generate(): self
    {
        return new self(Uuid::v4());
    }

    // Метод для отримання значення
    public function toString(): string
    {
        return $this->value->toRfc4122();
    }

    // Ключова вимога VO: Рівність
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
