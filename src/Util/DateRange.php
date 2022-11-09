<?php

declare(strict_types=1);

namespace App\Util;

use DateInterval;
use DateTime;
use LogicException;

class DateRange
{
    /**
     * @return DateRange[]
     */
    public static function splitIntoDays(DateTime $start, DateTime $end): array
    {
        if ($end->getTimestamp() < $start->getTimestamp()) {
            throw new LogicException('Start is after end');
        }

        $result = [];

        while (true) {
            $range = self::dayFromDateTime($start);
            if ($range->contains($end)) {
                $result[] = new DateRange($start, $end);
                break;
            }

            $result[] = new DateRange($start, $range->getEnd());

            $startEnd = clone $range->getEnd();
            $start = $startEnd->add(new DateInterval('PT1S'));
        }

        return $result;
    }

    public static function dayFromDateTime(DateTime $dateTime): DateRange
    {
        $start = clone $dateTime;
        $start->setTime(0, 0, 0, 0);

        $end = clone $dateTime;
        $end->setTime(23, 59, 59);

        return new DateRange($start, $end);
    }

    public function __construct(private DateTime $start, private DateTime $end)
    {
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
