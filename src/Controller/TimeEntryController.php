<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTask;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TimeEntryController extends BaseController
{
    const CODE_RUNNING_TIMER = 'code_running_timer';
    const CODE_NO_ASSIGNED_TASK = 'code_no_assigned_task';
    const CODE_TIME_ENTRY_OVER = 'code_time_entry_over';

    #[Route('/time-entry', name: 'time_entry_index')]
    public function index(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timeEntryRepository->preloadTags($queryBuilder);

        $filterForm = $formFactory->createNamed(
            '',
            TimeEntryListFilterFormType::class,
            new TimeEntryListFilterModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'csrf_protection' => false,
                'method' => 'GET',
                'allow_extra_fields' => true
            ]
        );

        /** @var Task|null $task */
        $task = null;
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TimeEntryListFilterModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $timeEntryRepository->applyFilter($queryBuilder, $data);
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
            return $this->redirectToRoute('time_entry_index');
        }

        $timeEntry = new TimeEntry($this->getUser());
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timeEntry);
        $manager->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $timeEntry->getIdString()]);
    }

    #[Route('/json/time-entry', name: 'time_entry_json_index', methods: ["GET"])]
    public function jsonIndex(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timeEntryRepository->preloadTags($queryBuilder);

        $filterForm = $formFactory->createNamed(
            '',
            TimeEntryListFilterFormType::class,
            new TimeEntryListFilterModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'csrf_protection' => false,
                'method' => 'GET',
                'allow_extra_fields' => true
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TimeEntryListFilterModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $timeEntryRepository->applyFilter($queryBuilder, $data);
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'time_entry.startedAt',
            'direction' => 'desc'
        ]);

        $items = ApiTimeEntry::fromEntities($pagination->getItems(), $this->getUser());
        foreach ($items as $item) {
            $item->url = $this->generateUrl('time_entry_view', ['id' => $item->id]);
        }

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/json/time-entry', name: 'time_entry_json_create', methods: ['POST'])]
    public function jsonCreate(Request $request, TimeEntryRepository $timeEntryRepository, TaskRepository $taskRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TimeEntryController::CODE_RUNNING_TIMER,
                    'You have a running timer',
                    ['resource' => $runningTimeEntry->getIdString()]
                )
            );
        }


        $timeEntry = new TimeEntry($this->getUser());

        $data = $this->getJsonBody($request);
        if (array_key_exists('taskId', $data)) {
            $task = $taskRepository->findOrException($data['taskId']);
            $timeEntry->setTask($task);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timeEntry);
        $manager->flush();

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

    /**
     * To continue a time-entry means to create a new time entry with the same tags and task (if applicable)
     * It's you "continuing" to do something again.
     *
     * @param TimeEntryRepository $timeEntryRepository
     * @param TimeEntryTagRepository $timeEntryTagRepository
     * @param string $id
     * @return Response
     */
    #[Route('/time-entry/{id}/continue', name: 'time_entry_continue')]
    public function continue(
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $existingTimeEntry = $timeEntryRepository->findOrException($id);
        if (!$existingTimeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_index');
        }

        $timeEntryTags = $timeEntryTagRepository->findForTimeEntry($existingTimeEntry);
        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());
        $timeEntry->setTask($existingTimeEntry->getTask());
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
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TimeEntryFormType::class, TimeEntryModel::fromEntity($timeEntry), [
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimeEntryModel $data */
            $data = $form->getData();

            if ($data->hasDescription()) {
                $timeEntry->setDescription($data->getDescription());
            }
            if ($data->hasStartedAt()) {
                $timeEntry->setStartedAt($data->getStartedAt());
            }
            if ($data->isEnded()) {
                $timeEntry->setEndedAt($data->getEndedAt());
            }

            $this->addFlash('success', 'Time entry has been updated');

            $this->getDoctrine()->getManager()->flush();
        }

        $apiTags = array_map(
            fn ($timeEntryTag) => ApiTag::fromEntity($timeEntryTag->getTag()),
            $timeEntry->getTimeEntryTags()->toArray()
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

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($timeEntry->isOver()) {
            $this->addFlash('danger', 'Time Entry already finished');
            return $this->redirectToRoute('time_entry_index');
        }

        $timeEntry->stop();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $id]);
    }

    #[Route('/json/time-entry/{id}/stop', name: 'time_entry_json_stop', methods: ['PUT'])]
    public function jsonStop(Request $request, TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findWithTagFetchOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($timeEntry->isOver()) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TimeEntryController::CODE_TIME_ENTRY_OVER,
                    'Time entry is already over'
                )
            );
        }

        $timeEntry->stop();
        $this->getDoctrine()->getManager()->flush();

        $data = $this->getJsonBody($request);
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
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $activeTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($activeTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_index');
        }

        if (!$timeEntry->isOver()) {
            $this->addFlash('danger', 'Time Entry is still running');
            return $this->redirectToRoute('time_entry_index');
        }

        $timeEntry->resume();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('time_entry_view', ['id' => $id]);
    }

    #[Route('/time-entry/{id}/delete', name: 'time_entry_delete')]
    public function delete(TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($timeEntry->running()) {
            $timeEntry->stop();
        }

        $timeEntry->softDelete();

        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Time entry deleted');

        return $this->redirectToRoute('time_entry_index');
    }

    #[Route('/json/time-entry/{id}/tag', name: 'time_entry_json_tag_create', methods: ['POST'])]
    public function jsonAddTag(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (!array_key_exists('tagName', $data)) {
            $problem = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_REQUEST_BODY);
            $problem->set('errors', [
                'message' => 'Missing value',
                'property' => 'tagName'
            ]);

            throw new ApiProblemException($problem);
        }

        $tagName = $data['tagName'];

        $tag = $tagRepository->findOneBy(['name' => $tagName, 'createdBy' => $this->getUser()]);
        if (is_null($tag)) {
            $tag = new Tag($this->getUser(), $tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        } else {
            $exitingLink = $timeEntryTagRepository->findOneBy([
                                                                  'timeEntry' => $timeEntry,
                                                                  'tag' => $tag
                                                              ]);

            if (!is_null($exitingLink)) {
                return $this->json([], Response::HTTP_CONFLICT);
            }
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
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $tag = $tagRepository->findOneByOrException(['name' => $tagName]);

        $exitingLink = $timeEntryTagRepository->findOneByOrException([
                                                              'timeEntry' => $timeEntry,
                                                              'tag' => $tag
                                                          ]);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/json/time-entry/{id}/tags', name: 'time_entry_json_tags')]
    public function jsonTags(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TimeEntryTagRepository $timeEntryTagRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $timeEntryTags = $timeEntryTagRepository->findBy(['timeEntry' => $timeEntry]);

        $apiTimeEntryTags = array_map(
            fn ($timeEntryTag) => ApiTag::fromEntity($timeEntryTag->getTag()),
            $timeEntryTags
        );

        return $this->json($apiTimeEntryTags);
    }

    #[Route('/json/time-entry/{id}', name: 'time_entry_json_update', methods: ['PUT'])]
    public function jsonUpdate(Request $request, TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (array_key_exists('description', $data)) {
            $timeEntry->setDescription($data['description']);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    #[Route('/json/time-entry/{id}/task', name: 'time_entry_json_task_create', methods: ['POST'])]
    public function jsonAddTask(
        Request $request,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ) : JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (!array_key_exists('name', $data)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::missingProperty('tagName')
            );

            throw new ApiProblemException($problem);
        }
        $taskName = $data['name'];

        $manager = $this->getDoctrine()->getManager();

        /** @var Task|null $task */
        $task = null;
        if (array_key_exists('id', $data)) {
            $taskId = $data['id'];
            $task = $taskRepository->find($taskId);
        }

        if (is_null($task)) {
            $task = new Task($this->getUser(), $taskName);
            $manager->persist($task);
        }

        $timeEntry->setTask($task);
        $manager->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());
        $apiTask->setUrl($this->generateUrl('task_view', ['id' => $task->getIdString()]));

        return $this->json($apiTask, Response::HTTP_CREATED);
    }

    #[Route('/json/time-entry/{id}/task', name: 'time_entry_json_task_delete', methods: ['DELETE'])]
    public function jsonRemoveTask(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $manager = $this->getDoctrine()->getManager();

        if (!$timeEntry->assignedToTask()) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    self::CODE_NO_ASSIGNED_TASK,
                    'Time entry has no assigned task',
                )
            );
        }

        $timeEntry->removeTask();
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/json/time-entry/active', name: 'time_entry_json_active', methods: ['GET'])]
    public function jsonGetActiveTimeEntry(Request $request, TimeEntryRepository $timeEntryRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (is_null($timeEntry)) {
            return $this->jsonNoContent();
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }
}
