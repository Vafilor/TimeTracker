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
    const duration = 'duration';

    public static function isValid(string $type): bool
    {
        return $type === TimeType::instant || $type === TimeType::duration;
    }

    public static function invalidErrorMessage(string $type)
    {
        return "timeType '$type' is invalid. Excepted either 'instant' or 'duration'";
    }
}
