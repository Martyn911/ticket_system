<?php

namespace App\Booking\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final class ClientId
{
    private Uuid $value;
}
