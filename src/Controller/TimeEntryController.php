<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiErrorResponseBody;
use App\Api\ApiTag;
use App\Api\ApiTimeEntry;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Entity\TimeEntryTag;
use App\Form\Model\TimeEntryListFilterModel;
use App\Form\Model\TimeEntryModel;
use App\Form\TimeEntryFormType;
use App\Form\TimeEntryListFilterFormType;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Repository\TimeEntryTagRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TimeEntryController extends BaseController
{
    const codeRunningTimer = 'code_running_timer';

    #[Route('/time-entry/list', name: 'time_entry_list')]
    public function list(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        FormFactoryInterface $formFactory,
        TaskRepository $taskRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->addSelect('time_entry_tag')
            ->leftJoin('time_entry.timeEntryTags', 'time_entry_tag')
            ->leftJoin('time_entry_tag.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL');

        $filterForm = $formFactory->createNamed('',
            TimeEntryListFilterFormType::class,
            new TimeEntryListFilterModel(), [
                'timezone' => $this->getUser()->getTimezone(),
                'csrf_protection' => false,
                'method' => 'GET',
            ]
        );

        /** @var Task|null $task */
        $task = null;
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TimeEntryListFilterModel $data */
            $data = $filterForm->getData();

            if ($data->hasStart()) {
                $queryBuilder = $queryBuilder
                    ->andWhere('time_entry.startedAt >= :start')
                    ->setParameter('start', $data->getStart())
                ;
            }

            if ($data->hasEnd()) {
                $queryBuilder = $queryBuilder
                    ->andWhere('time_entry.endedAt <= :end')
                    ->setParameter('end', $data->getEnd())
                ;
            }

            if ($data->hasTags()) {
                $tags = $data->getTagsArray();
                $queryBuilder = $queryBuilder
                    ->andWhere('tag.name IN (:tags)')
                    ->setParameter('tags', $tags)
                ;
            }

            if ($data->hasTask()) {
                $queryBuilder = $queryBuilder
                    ->andWhere('time_entry.task = :taskId')
                    ->setParameter('taskId', $data->getTaskId())
                ;
                $task = $taskRepository->find($data->getTaskId());
            }
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'time_entry.startedAt',
            'direction' => 'desc'
        ]);

        return $this->render('time_entry/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(),
            'task' => $task
        ]);
    }

    #[Route('/time-entry/today', name: 'time_entry_today')]
    #[Route('/', name: 'app_homepage')]
    public function today(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
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

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->addSelect('time_entry_tag')
            ->leftJoin('time_entry.timeEntryTags', 'time_entry_tag')
            ->leftJoin('time_entry_tag.tag', 'tag')
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

        return $this->render('time_entry/today.html.twig', [
            'pagination' => $pagination,
            'latestTimeEntry' => $latestTimeEntry,
            'totalTime' => $totalTime
        ]);
    }

    #[Route('/time-entry/create', name: 'time_entry_create')]
    public function create(TimeEntryRepository $timeEntryRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_list');
        }

        $timeEntry = new TimeEntry($this->getUser());
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timeEntry);
        $manager->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $timeEntry->getIdString()]);
    }

    #[Route('/json/time-entry/create', name: 'time_entry_json_create', methods: ['POST'])]
    public function jsonCreate(Request $request, TimeEntryRepository $timeEntryRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            $data = new ApiErrorResponseBody(
                new ApiError(
                    self::codeRunningTimer,
                    'You have a running timer',
                    $runningTimeEntry->getIdString())
            );
            return $this->json($data, Response::HTTP_BAD_REQUEST);
        }

        $timeEntry = new TimeEntry($this->getUser());
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timeEntry);
        $manager->flush();

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('time_format', $data)) {
            $timeFormat = 'date';
        } else {
            $timeFormat = $data['time_format'];
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser(), $timeFormat);
        $data = [
            'timeEntry' => $apiTimeEntry,
            'url' => $this->generateUrl('time_entry_view', ['id' => $timeEntry->getIdString()])
        ];

        return $this->json($data, Response::HTTP_CREATED);
    }

    #[Route('/time-entry/{id}/continue', name: 'time_entry_continue')]
    public function continue(
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_list');
        }

        $existingTimeEntry = $timeEntryRepository->find($id);
        if (is_null($existingTimeEntry)) {
            $this->addFlash('danger', 'Time entry does not exist');
            return $this->redirectToRoute('time_entry_list');
        }

        /** @var TimeEntryTag[] $timeEntryTags */
        $timeEntryTags = $timeEntryTagRepository->createDefaultQueryBuilder()
                                       ->addSelect('tag')
                                       ->join('time_entry_tag.tag', 'tag')
                                       ->andWhere('time_entry_tag.timeEntry = :timeEntry')
                                       ->setParameter('timeEntry', $existingTimeEntry)
                                       ->getQuery()
                                       ->getResult()
        ;

        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());
        foreach ($timeEntryTags as $timeEntryTag) {
            $copy = new TimeEntryTag($timeEntry, $timeEntryTag->getTag());
            $manager->persist($copy);
        }

        $manager->persist($timeEntry);
        $manager->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $timeEntry->getIdString()]);
    }

    #[Route('/time-entry/{id}/view', name: 'time_entry_view')]
    public function view(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            $this->addFlash('danger', 'Time Entry not found');
            return $this->redirectToRoute('time_entry_list');
        }

        $form = $this->createForm(TimeEntryFormType::class, TimeEntryModel::fromEntity($timeEntry), [
            'timezone' => $this->getUser()->getTimezone(),
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimeEntryModel $data */
            $data = $form->getData();
            $timeEntry->setDescription($data->getDescription());
            $timeEntry->setStartedAt($data->getStartedAt());
            $timeEntry->setTask($data->getTask());
            if ($data->isEnded()) {
                $timeEntry->setEndedAt($data->getEndedAt());
            }

            $this->addFlash('success', 'Time entry has been updated');

            $this->getDoctrine()->getManager()->flush();
        }

        $timeEntryTags = $timeEntryTagRepository->findBy(['timeEntry' => $timeEntry]);
        $apiTags = array_map(
            fn($timeEntryTag) => ApiTag::fromEntity($timeEntryTag->getTag()),
            $timeEntryTags
        );

        return $this->render('time_entry/view.html.twig', [
            'timeEntry' => $timeEntry,
            'form' => $form->createView(),
            'tags' => $apiTags
        ]);
    }

    #[Route('/time-entry/{id}/stop', name: 'time_entry_stop')]
    public function stop(TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            $this->addFlash('danger', 'Time Entry not found');
            return $this->redirectToRoute('time_entry_list');
        }

        if ($timeEntry->isOver()) {
            $this->addFlash('danger', 'Time Entry already finished');
            return $this->redirectToRoute('time_entry_list');
        }

        $timeEntry->stop();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $id]);
    }

    #[Route('/json/time-entry/{id}/stop', name: 'time_entry_json_stop', methods: ['PUT'])]
    public function jsonStop(Request $request, TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->findWithTagFetch($id);
        if (is_null($timeEntry)) {
            return $this->json(['error' => 'Time entry not found'], Response::HTTP_NOT_FOUND);
        }

        if ($timeEntry->isOver()) {
            return $this->json(['error' => 'Time entry is already over'], Response::HTTP_BAD_REQUEST);
        }

        $timeEntry->stop();
        $this->getDoctrine()->getManager()->flush();

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('time_format', $data)) {
            $timeFormat = 'date';
        } else {
            $timeFormat = $data['time_format'];
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser(), $timeFormat);

        return $this->json($apiTimeEntry);
    }

    #[Route('/time-entry/{id}/resume', name: 'time_entry_resume')]
    public function resume(TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            $this->addFlash('danger', 'Time Entry not found');
            return $this->redirectToRoute('time_entry_list');
        }

        $activeTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($activeTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_list');
        }

        if (!$timeEntry->isOver()) {
            $this->addFlash('danger', 'Time Entry is still running');
            return $this->redirectToRoute('time_entry_list');
        }

        $timeEntry->resume();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $id]);
    }

    #[Route('/time-entry/{id}/delete', name: 'time_entry_delete')]
    public function delete(TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            $this->addFlash('danger', 'Time Entry not found');
            return $this->redirectToRoute('time_entry_list');
        }

        if (!$timeEntry->getOwner()->equalIds($this->getUser())) {
            $this->addFlash('danger', 'You do not have permission to delete this time entry');
            return $this->redirectToRoute('time_entry_list');
        }

        if ($timeEntry->running()) {
            $timeEntry->stop();
        }

        $timeEntry->softDelete();

        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Time entry deleted');

        return $this->redirectToRoute('time_entry_list');
    }

    #[Route('/json/time-entry/{id}/tag', name: 'time_entry_json_tag_create', methods: ['POST'])]
    public function jsonAddTag(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('tagName', $data)) {
            return $this->json(['error' => 'missing tagName'], Response::HTTP_BAD_REQUEST);
        }

        $tagName = $data['tagName'];

        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        if (is_null($tag)) {
            $tag = new Tag($tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        }

        $exitingLink = $timeEntryTagRepository->findOneBy([
            'timeEntry' => $timeEntry,
            'tag' => $tag
        ]);

        if (!is_null($exitingLink)) {
            return $this->json([], Response::HTTP_CONFLICT);
        }

        $timeEntryTag = new TimeEntryTag($timeEntry, $tag);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timeEntryTag);
        $manager->flush();

        $apiTag = ApiTag::fromEntity($tag);

        return $this->json($apiTag, Response::HTTP_CREATED);
    }

    #[Route('/json/time-entry/{id}/tag/{tagName}', name: 'time_entry_json_tag_delete', methods: ['DELETE'])]
    public function jsonDeleteTag(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id,
        string $tagName) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        if (is_null($tag)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $exitingLink = $timeEntryTagRepository->findOneBy([
                                                              'timeEntry' => $timeEntry,
                                                              'tag' => $tag
                                                          ]);

        if (is_null($exitingLink)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->json([], Response::HTTP_OK);
    }

    #[Route('/json/time-entry/{id}/tags', name: 'time_entry_json_tags')]
    public function jsonTags(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $timeEntryTags = $timeEntryTagRepository->findBy(['timeEntry' => $timeEntry]);

        $apiTimeEntryTags = array_map(
            fn($timeEntryTag) => ApiTag::fromEntity($timeEntryTag->getTag()),
            $timeEntryTags
        );

        return $this->json($apiTimeEntryTags);
    }

    #[Route('/json/time-entry/{id}', name: 'time_entry_json_update', methods: ['PUT'])]
    public function jsonUpdate(Request $request, TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            throw $this->createNotFoundException();
        }

        $data = json_decode($request->getContent(), true);

        if (array_key_exists('description', $data)) {
            $timeEntry->setDescription($data['description']);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json([]);
    }
}
