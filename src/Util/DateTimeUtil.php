<?php

declare(strict_types=1);

namespace App\Util;

use DateInterval;
use DateTime;

class DateTimeUtil
{
    public static function dateIntervalFromSeconds(int $seconds): DateInterval
    {
        // DateInterval will not convert just seconds to minutes/hours, etc.
        // So we "fake" it to do so by subtracting DateTimes
        $d1 = new DateTime();
        $d2 = clone $d1;
        $d2->add(new DateInterval("PT{$seconds}S"));

        return $d2->diff($d1);
    }

    public static function dateFromTimestamp(int $timestamp): DateTime
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }
}
