<?php

declare(strict_types=1);

namespace App\Util;

use DateTime;
use DateTimeZone;

class DateRange
{
    private DateTime $start;
    private DateTime $end;

    public static function dayFromDateTime(DateTime $dateTime): DateRange
    {
        $start = clone $dateTime;
        $start->setTime(0, 0, 0, 0);

        $end = clone $dateTime;
        $end->setTime(23, 59, 59);

        return new DateRange($start, $end);
    }

    public function __construct(DateTime $start, DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): DateTime
    {

        return clone $this->start;
    }

    public function getEnd(): DateTime
    {
        return clone $this->end;
    }

    public function contains(DateTime $when): bool
    {
        $whenTimestamp = $when->getTimestamp();

        return $this->start->getTimestamp() <= $whenTimestamp &&
               $whenTimestamp <= $this->end->getTimestamp()
        ;
    }
}
