<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TimeEntryRepository;
use App\Repository\TimeEntryTagRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends BaseController
{
    #[Route('/report/today', name: 'report_today')]
    public function today(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $userTimeZone = $this->getUser()->getTimezone();

        $todayStart = new DateTime('now', new DateTimeZone($userTimeZone));
        $todayStart->setTime(0, 0, 0, 0);
        $todayStart->setTimezone(new DateTimeZone('UTC'));

        $todayEnd = new DateTime('now', new DateTimeZone($userTimeZone));
        $todayEnd->setTime(23, 59, 59);
        $todayEnd->setTimezone(new DateTimeZone('UTC'));

        $summary = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->select('tag.name, tag.color, SUM(time_entry.endedAt - time_entry.createdAt) as duration')
            ->groupBy('tag.name, tag.color')
            ->leftJoin('time_entry.timeEntryTags', 'time_entry_tag')
            ->leftJoin('time_entry_tag.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.createdAt > :start')
            ->andWhere('time_entry.createdAt < :end')
            ->andWhere('time_entry.endedAt IS NOT NULL')
            ->orderBy('duration', 'DESC')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->getQuery()
            ->getScalarResult()
        ;

        $totalTime = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->select('SUM(time_entry.endedAt - time_entry.createdAt)')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.createdAt > :start')
            ->andWhere('time_entry.createdAt < :end')
            ->andWhere('time_entry.endedAt IS NOT NULL')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $this->render('report/today.html.twig', [
            'summary' => $summary,
            'totalTime' => $totalTime
        ]);
    }
}
