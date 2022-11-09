<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Statistic;
use App\Entity\User;
use App\Repository\StatisticRepository;
use App\Util\TimeType;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class StatisticManager
{
    private StatisticRepository $statisticRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(StatisticRepository $statisticRepository, EntityManagerInterface $entityManager)
    {
        $this->statisticRepository = $statisticRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Finds a statistic for a given name/user/timeType. If it does not exist, it is created and persisted to the database.
     */
    public function findOrCreateByName(string $name, User $assignedTo, string $timeType): Statistic
    {
        if (!TimeType::isValid($timeType)) {
            throw new InvalidArgumentException(TimeType::invalidErrorMessage($timeType));
        }

        $statistic = $this->statisticRepository->findOneBy([
            'canonicalName' => Statistic::canonicalizeName($name),
            'assignedTo' => $assignedTo,
            'timeType' => $timeType,
        ]);

        if (is_null($statistic)) {
            $statistic = new Statistic($assignedTo, $name, $timeType);
            $this->entityManager->persist($statistic);
        }

        return $statistic;
    }
}
