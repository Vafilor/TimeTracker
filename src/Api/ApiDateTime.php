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
        if (DateFormatType::DATE === $format) {
            return $dateTz->format($user->getDateFormat());
        } elseif (DateFormatType::DATE_TIME === $format) {
            return $dateTz->format($user->getDateTimeFormat());
        } elseif (DateFormatType::DATE_TIME_TODAY === $format) {
            return $dateTz->format($user->getTodayDateTimeFormat());
        } else {
            $message = DateFormatType::invalidErrorMessage($format);

            throw new Exception($message);
        }
    }
}
