<?php

declare(strict_types=1);

namespace App\Util;

use Countable;
use Exception;
use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class TimeType
{
    const instant = 'instant';
    const interval = 'interval';

    public static function isValid(string $type): bool
    {
        return $type === TimeType::instant || $type === TimeType::interval;
    }

    public static function invalidErrorMessage(string $type)
    {
        return "timeType '$type' is invalid. Excepted either 'instant' or 'interval'";
    }
}
