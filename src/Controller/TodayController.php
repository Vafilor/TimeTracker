<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TimeEntryRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TodayController extends BaseController
{
    #[Route('/today', name: 'today_index')]
    #[Route('/', name: 'app_homepage')]
    public function today(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $userTimeZone = $this->getUser()->getTimezone();

        $todayStart = new DateTime('now', new DateTimeZone($userTimeZone));
        $todayStart->setTime(0, 0, 0, 0);
        $todayStart->setTimezone(new DateTimeZone('UTC'));

        $todayEnd = new DateTime('now', new DateTimeZone($userTimeZone));
        $todayEnd->setTime(23, 59, 59);
        $todayEnd->setTimezone(new DateTimeZone('UTC'));

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->addSelect('tag_link')
            ->leftJoin('time_entry.tagLinks', 'tag_link')
            ->leftJoin('tag_link.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.startedAt > :start')
            ->andWhere('time_entry.startedAt < :end')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
        ;

        $totalTime = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->select('SUM(time_entry.endedAt - time_entry.startedAt)')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.startedAt > :start')
            ->andWhere('time_entry.startedAt < :end')
            ->andWhere('time_entry.endedAt IS NOT NULL')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'time_entry.startedAt',
            'direction' => 'desc',
        ]);

        $latestTimeEntry = $timeEntryRepository->getLatestTimeEntry($this->getUser());

        return $this->render('today/index.html.twig', [
            'pagination' => $pagination,
            'latestTimeEntry' => $latestTimeEntry,
            'totalTime' => $totalTime
        ]);
    }
}