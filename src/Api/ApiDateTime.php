<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\User;
use DateTime;
use DateTimeZone;
use Exception;

class ApiDateTime
{
    public static function formatUserDate(DateTime $dateTime, User $user, string $format): string
    {
        $dateTz = $dateTime->setTimezone(new DateTimeZone($user->getTimezone()));
        if ($format === 'date') {
            return $dateTz->format($user->getDateFormat());
        } elseif ($format === 'today') {
            return $dateTz->format($user->getTodayDateFormat());
        } else {
            throw new Exception("Unknown date format. Only 'date' and 'today' are supported. '$format' was given");
        }
    }
}
