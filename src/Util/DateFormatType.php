<?php

declare(strict_types=1);

namespace App\Util;

class DateFormatType
{
    const DATE = 'date';
    const DATE_TIME = 'date_time';
    const DATE_TIME_TODAY = 'date_time_today';

    public static function isValid(string $type): bool
    {
        return $type === self::DATE || $type === self::DATE_TIME || $type === self::DATE_TIME_TODAY;
    }

    public static function invalidErrorMessage(string $type): string
    {
        return "DateFormatType '$type' is invalid. Excepted either one of 'date', 'date_time', 'date_time_today'";
    }
}
