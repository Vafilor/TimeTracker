<?php

declare(strict_types=1);

namespace App\Util;

class TimeType
{
    const INSTANT = 'instant';
    const INTERVAL = 'interval';

    public static function isValid(string $type): bool
    {
        return $type === self::INSTANT || $type === self::INTERVAL;
    }

    public static function invalidErrorMessage(string $type): string
    {
        return "timeType '$type' is invalid. Excepted either 'instant' or 'interval'";
    }
}
