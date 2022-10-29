<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Entity\User;
use App\Error\StatisticValueDayConflict;
use App\Form\Model\AddStatisticValueModel;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Util\DateRange;
use App\Util\TimeType;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class StatisticValueManager
{
    private StatisticRepository $statisticRepository;
    private StatisticValueRepository $statisticValueRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->statisticRepository = $statisticRepository;
        $this->statisticValueRepository = $statisticValueRepository;
        $this->entityManager = $entityManager;
    }

    public function addForDay(User $user, AddStatisticValueModel $model): StatisticValue
    {
        $day = $model->getDay();
        $statisticName = $model->getStatisticName();

        if (!$day) {
            $day = new DateTime('now', new DateTimeZone($user->getTimezone()));
        } else {
            // The form converts the Date from the User's timezone to UTC
            // We're about to get a start/end of day from it, so we actually need it in the user's timezone, then convert to UTC
            // Otherwise, we get wrong start/end dates
            $day->setTimezone(new DateTimeZone($user->getTimezone()));
        }

        $dayRange = DateRange::dayFromDateTime($day);
        $statistic = $this->statisticRepository->findOneBy([
                                                         'canonicalName' => Statistic::canonicalizeName(
                                                             $statisticName
                                                         ),
                                                         'assignedTo' => $user,
                                                         'timeType' => TimeType::INTERVAL,
                                                     ]);

        if (is_null($statistic)) {
            $statistic = new Statistic($user, $statisticName, TimeType::INTERVAL);
            $this->entityManager->persist($statistic);
        } else {
            $date = $day->format($user->getDateFormat());
            $conflictMessage = "Record for statistic '{$statistic->getName()}' already exists for $date";

            try {
                $statisticValue = $this->statisticValueRepository->findForDay($statistic, $dayRange);
                if (!is_null($statisticValue)) {
                    throw new StatisticValueDayConflict($conflictMessage);
                }
            } catch (NonUniqueResultException $exception) {
                throw new StatisticValueDayConflict($conflictMessage);
            }
        }

        // StatisticValue converts the start/end to UTC before setting
        $statisticValue = StatisticValue::fromInterval(
            $statistic,
            $model->getValue(),
            $dayRange->getStart(),
            $dayRange->getEnd()
        );

        $this->entityManager->persist($statisticValue);

        return $statisticValue;
    }

    /**
     * @param iterable|StatisticValue[] $statisticValues
     */
    public function groupByDay(string $dateFormat, DateTimeZone $timezone, iterable $statisticValues): array
    {
        /** @var StatisticValue|null $previousValue */
        $previousValue = null;
        $data = [];

        foreach ($statisticValues as $statisticValue) {
            $start = clone $statisticValue->getStartedAt();
            $startedAt = $start->setTimezone($timezone);
            $key = $startedAt->format($dateFormat);

            if (!$previousValue) {
                $previousValue = $statisticValue;
                $data[$key] = [$statisticValue];
                continue;
            }

            $dateDiff = $previousValue->getStartedAt()->diff($statisticValue->getStartedAt());
            if ($dateDiff->d < 1) {
                $data[$key][] = $statisticValue;
            } else {
                $data[$key] = [$statisticValue];
            }

            $previousValue = $statisticValue;
        }

        return $data;
    }
}
