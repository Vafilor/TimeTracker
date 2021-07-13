<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TimeEntry;
use App\Repository\TagRepository;
use App\Repository\TimeEntryRepository;
use App\Util\DateRange;
use App\Util\DateTimeUtil;
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
        PaginatorInterface $paginator
    ): Response
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
            ->select('tag.name, tag.color, SUM(time_entry.endedAt - time_entry.startedAt) as duration')
            ->groupBy('tag.name, tag.color')
            ->leftJoin('time_entry.tagLinks', 'tag_link')
            ->leftJoin('tag_link.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.startedAt > :start')
            ->andWhere('time_entry.startedAt < :end')
            ->andWhere('time_entry.endedAt IS NOT NULL')
            ->orderBy('duration', 'DESC')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->getQuery()
            ->getScalarResult()
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

        return $this->render('report/today.html.twig', [
            'summary' => $summary,
            'totalTime' => $totalTime
        ]);
    }

    #[Route('/report/tag/{id}/time-entry', name: 'report_tag_time_entry')]
    public function reportTimeEntry(
        Request $request,
        TagRepository $tagRepository,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $tag = $tagRepository->findOrException($id);
        if (!$tag->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        /** @var TimeEntry[] $timeEntries */
        $timeEntries = $timeEntryRepository->findForTagQueryBuilder($tag)
            ->orderBy('time_entry.startedAt', 'desc')
            ->getQuery()
            ->getResult()
        ;

        $data = [];

        $dateFormat = $this->getUser()->getDateFormat();
        $timezone = $this->getUser()->getTimezone();

        $keyTimestamp = [];

        foreach ($timeEntries as $timeEntry) {
            $end = clone $timeEntry->getEndedAt();
            $start = clone $timeEntry->getStartedAt();
            $startedAt = $start->setTimezone(new DateTimeZone($timezone));
            $endedAt = $end->setTimezone(new DateTimeZone($timezone));

            foreach (DateRange::splitIntoDays($startedAt, $endedAt) as $dayRange) {
                $key = $dayRange->getStart()->format($dateFormat);
                if (!array_key_exists($key, $data)) {
                    $data[$key] = 0;
                    $keyTimestamp[$key] = $dayRange->getStart()->getTimestamp();
                }

                $data[$key] += $dayRange->getEnd()->getTimestamp() - $dayRange->getStart()->getTimestamp();
            }
        }

        $totalSeconds = 0;
        foreach ($data as $key => $seconds) {
            $data[$key] = DateTimeUtil::dateIntervalFromSeconds($seconds);
            $totalSeconds += $seconds;
        }

        uksort($data, fn ($keyA, $keyB) => $keyTimestamp[$keyB] - $keyTimestamp[$keyA]);

        return $this->render('report/tag_time_entries.html.twig', [
            'tag' => $tag,
            'data' => $data,
            'total' => DateTimeUtil::dateIntervalFromSeconds($totalSeconds)
        ]);
    }
}
