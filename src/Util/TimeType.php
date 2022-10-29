<?php

declare(strict_types=1);

namespace App\Util;

class TimeType
{
    public const INSTANT = 'instant';
    public const INTERVAL = 'interval';

    public static function isValid(string $type): bool
    {
        return self::INSTANT === $type || self::INTERVAL === $type;
    }

    public static function invalidErrorMessage(string $type): string
    {
        return "timeType '$type' is invalid. Excepted either 'instant' or 'interval'";
    }
}
