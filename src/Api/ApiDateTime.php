<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\User;
use App\Util\DateFormatType;
use DateTime;
use DateTimeZone;
use Exception;

class ApiDateTime
{
    public static function formatUserDate(DateTime $dateTime, User $user, string $format): string
    {
        $dateTz = $dateTime->setTimezone(new DateTimeZone($user->getTimezone()));
        if ($format === DateFormatType::DATE) {
            return $dateTz->format($user->getDateFormat());
        } elseif ($format === DateFormatType::DATE_TIME) {
            return $dateTz->format($user->getDateTimeFormat());
        } elseif ($format === DateFormatType::DATE_TIME_TODAY) {
            return $dateTz->format($user->getTodayDateTimeFormat());
        } else {
            throw new Exception("Unknown date format. Only 'date' and 'today' are supported. '$format' was given");
        }
    }
}
