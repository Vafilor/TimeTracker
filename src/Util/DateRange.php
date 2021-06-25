<?php

declare(strict_types=1);

namespace App\Util;

use DateTime;
use DateTimeZone;

class DateRange
{
    private DateTime $start;
    private DateTime $end;

    public static function dayFromDateTime(DateTime $dateTime, string $timeZone = 'UTC'): DateRange
    {
        $start = clone $dateTime;
        $start->setTime(0, 0, 0, 0);
        $start->setTimezone(new DateTimeZone($timeZone));

        $end = clone $dateTime;
        $end->setTime(23, 59, 59);
        $end->setTimezone(new DateTimeZone($timeZone));

        return new DateRange($start, $end);
    }

    public function __construct(DateTime $start, DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function getEnd(): DateTime
    {
        return $this->end;
    }
}
