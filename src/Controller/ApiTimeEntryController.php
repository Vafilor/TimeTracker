<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Api\ApiTask;
use App\Api\ApiTimeEntry;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Form\EditTimeEntryFormType;
use App\Form\FilterTimeEntryFormType;
use App\Form\Model\EditTimeEntryModel;
use App\Form\Model\FilterTimeEntryModel;
use App\Manager\TagManager;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Traits\HasStatisticDataTrait;
use App\Traits\TaggableController;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTimeEntryController extends BaseController
{
    use TaggableController;

    use HasStatisticDataTrait;

    /**
     * @throws Exception
     */
    #[Route('/api/time-entry', name: 'api_time_entry_index', methods: ['GET'])]
    #[Route('/json/time-entry', name: 'json_time_entry_index', methods: ['GET'])]
    public function index(
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
            FilterTimeEntryFormType::class,
            new FilterTimeEntryModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'csrf_protection' => false,
                'method' => 'GET',
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterTimeEntryModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $timeEntryRepository->applyFilter($queryBuilder, $data);
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'time_entry.startedAt',
            'direction' => 'desc',
        ]);

        $items = ApiTimeEntry::fromEntities($pagination->getItems(), $this->getUser());
        foreach ($items as $item) {
            if (str_starts_with($request->getPathInfo(), '/api')) {
                $item->url = $this->generateUrl('api_time_entry_view', ['id' => $item->id]);
            } else {
                $item->url = $this->generateUrl('time_entry_view', ['id' => $item->id]);
            }
        }

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    /**
     * @throws Exception
     */
    #[Route('/api/time-entry/today', name: 'api_time_entry_today', methods: ['GET'])]
    public function today(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
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
            ->leftJoin('time_entry.tagLinks', 'tagLink')
            ->leftJoin('tagLink.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL')
            ->andWhere('time_entry.startedAt > :start')
            ->andWhere('time_entry.startedAt < :end')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
        ;

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'time_entry.startedAt',
            'direction' => 'desc',
        ]);

        $items = ApiTimeEntry::fromEntities($pagination->getItems(), $this->getUser());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/time-entry', name: 'api_time_entry_create', methods: ['POST'])]
    #[Route('/json/time-entry', name: 'json_time_entry_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        TagManager $tagManager,
        TaskRepository $taskRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            throw new ApiProblemException(ApiProblem::invalidAction(TimeEntryController::CODE_RUNNING_TIMER, 'You have a running timer', ['resource' => $runningTimeEntry->getIdString()]));
        }

        $timeEntry = new TimeEntry($this->getUser());

        $data = $this->getJsonBody($request, []);

        if (array_key_exists('tags', $data)) {
            $tagNames = $tagManager->parseFromString($data['tags']);
            $tagObjects = $tagManager->findOrCreateByNames($tagNames, $this->getUser());
            foreach ($tagObjects as $tag) {
                $tagLink = new TagLink($timeEntry, $tag);
                $entityManager->persist($tagLink);
            }
        }
        if (array_key_exists('taskId', $data)) {
            $task = $taskRepository->findOrException($data['taskId']);
            $timeEntry->setTask($task);
        }

        $entityManager->persist($timeEntry);
        $entityManager->flush();

        if (!array_key_exists('time_format', $data)) {
            $timeFormat = 'date';
        } else {
            $timeFormat = $data['time_format'];
        }

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $url = $this->generateUrl('api_time_entry_view', ['id' => $timeEntry->getIdString()]);
        } else {
            $url = $this->generateUrl('time_entry_view', ['id' => $timeEntry->getIdString()]);
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser(), $timeFormat);
        $data = [
            'timeEntry' => $apiTimeEntry,
            'url' => $url,
        ];

        $template = $request->query->get('template', 'false');
        if ('false' !== $template) {
            if ('regular' === $template) {
                $data['template'] = $this->renderView('time_entry/partials/_time-entry.html.twig', [
                    'timeEntry' => $timeEntry,
                ]);
            } elseif ('small' === $template) {
                $data['template'] = $this->renderView('time_entry/partials/_time-entry-small.html.twig', [
                    'timeEntry' => $timeEntry,
                ]);
            } else {
                $problem = ApiProblem::withErrors(
                    Response::HTTP_BAD_REQUEST,
                    ApiProblem::TYPE_VALIDATION_ERROR,
                    ApiError::invalidPropertyValue('template')
                );

                throw new ApiProblemException($problem);
            }
        }

        return $this->jsonNoNulls($data, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_view', methods: ['GET'])]
    public function view(
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->jsonNoNulls($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_edit', methods: ['PUT'])]
    #[Route('/json/time-entry/{id}', name: 'json_time_entry_update', methods: ['PUT'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditTimeEntryFormType::class, EditTimeEntryModel::fromEntity($timeEntry), [
            'timezone' => $this->getUser()->getTimezone(),
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTimeEntryModel $data */
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

            $entityManager->flush();
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->jsonNoNulls($apiTimeEntry);
    }

    /**
     * To continue a time-entry means to create a new time entry with the same tags.
     * It's you "continuing" to do something again.
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/api/time-entry/{id}/continue', name: 'api_time_entry_continue', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/continue', name: 'json_time_entry_continue', methods: ['POST'])]
    public function continue(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $existingTimeEntry = $timeEntryRepository->findOrException($id);
        if (!$existingTimeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            throw new ApiProblemException(ApiProblem::invalidAction(TimeEntryController::CODE_RUNNING_TIMER, 'You have a running timer', ['resource' => $runningTimeEntry->getIdString()]));
        }

        $tagLinks = $tagLinkRepository->findForTimeEntry($existingTimeEntry);

        $timeEntry = new TimeEntry($this->getUser());
        foreach ($tagLinks as $tagLink) {
            $copy = new TagLink($timeEntry, $tagLink->getTag());
            $timeEntry->addTagLink($copy);
            $entityManager->persist($copy);
        }

        $timeEntry->setTask($existingTimeEntry->getTask());

        $entityManager->persist($timeEntry);
        $entityManager->flush();

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $url = $this->generateUrl('api_time_entry_view', ['id' => $timeEntry->getIdString()]);
        } else {
            $url = $this->generateUrl('time_entry_view', ['id' => $timeEntry->getIdString()]);
        }

        $data = [
            'timeEntry' => $apiTimeEntry,
            'url' => $url,
        ];

        if (boolval($request->query->get('template', 'false'))) {
            $data['template'] = $this->renderView('time_entry/partials/_time-entry.html.twig', [
                'timeEntry' => $timeEntry,
            ]);
        }

        return $this->jsonNoNulls($data);
    }

    #[Route('/api/time-entry/{id}/stop', name: 'api_time_entry_stop', methods: ['PUT'])]
    #[Route('/json/time-entry/{id}/stop', name: 'json_time_entry_stop', methods: ['PUT'])]
    public function stop(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findWithTagFetchOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($timeEntry->isOver()) {
            throw new ApiProblemException(ApiProblem::invalidAction(TimeEntryController::CODE_TIME_ENTRY_OVER, 'Time entry is already over'));
        }

        $timeEntry->stop();
        $entityManager->flush();

        $data = $this->getJsonBody($request);
        if (!array_key_exists('time_format', $data)) {
            $timeFormat = 'date';
        } else {
            $timeFormat = $data['time_format'];
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser(), $timeFormat);

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $apiTimeEntry->url = $this->generateUrl('api_time_entry_view', ['id' => $apiTimeEntry->id]);
        } else {
            $apiTimeEntry->url = $this->generateUrl('time_entry_view', ['id' => $apiTimeEntry->id]);
        }

        return $this->jsonNoNulls($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}/resume', name: 'api_time_entry_resume', methods: ['PUT'])]
    public function resume(
        TimeEntryRepository $timeEntryRepository,
        EntityManagerInterface $entityManager,
        string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if (!$timeEntry->isOver()) {
            $problem = ApiProblem::invalidAction(
                TimeEntryController::CODE_RUNNING_TIMER,
                'Time entry is still running, no need to resume'
            );
            $problem->set('resource', $timeEntry->getIdString());

            throw new ApiProblemException($problem);
        }

        $activeTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($activeTimeEntry)) {
            $problem = ApiProblem::invalidAction(
                TimeEntryController::CODE_RUNNING_TIMER,
                'You already have a running time entry'
            );
            $problem->set('resource', $activeTimeEntry->getIdString());

            throw new ApiProblemException($problem);
        }

        $timeEntry->resume();
        $entityManager->flush();

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->jsonNoNulls($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_delete', methods: ['DELETE'])]
    public function delete(
        TimeEntryRepository $timeEntryRepository,
        EntityManagerInterface $entityManager,
        string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($timeEntry->running()) {
            $timeEntry->stop();
        }

        $timeEntry->softDelete();

        $entityManager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/time-entry/{id}/tag', name: 'api_time_entry_tag_create', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/tag', name: 'json_time_entry_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->addTagRequest(
            $request,
            $entityManager,
            $tagManager,
            $tagLinkRepository,
            $this->getUser(),
            $timeEntry
        );
    }

    #[Route('/api/time-entry/{id}/tag/{tagName}', name: 'api_time_entry_tag_delete', methods: ['DELETE'])]
    #[Route('/json/time-entry/{id}/tag/{tagName}', name: 'json_time_entry_tag_delete', methods: ['DELETE'])]
    public function deleteTag(
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeTagRequest(
            $entityManager,
            $tagRepository,
            $tagLinkRepository,
            $this->getUser(),
            $tagName,
            $timeEntry
        );
    }

    #[Route('/api/time-entry/{id}/tags', name: 'api_time_entry_tags', methods: ['GET'])]
    #[Route('/json/time-entry/{id}/tags', name: 'json_time_entry_tags', methods: ['GET'])]
    public function indexTag(TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->getTagsRequest($timeEntry);
    }

    // TOOD rename to assignTask?
    #[Route('/api/time-entry/{id}/task', name: 'api_time_entry_task_create', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/task', name: 'json_time_entry_task_create', methods: ['POST'])]
    public function addTask(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (!array_key_exists('name', $data)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::missingProperty('name')
            );

            throw new ApiProblemException($problem);
        }

        $taskName = $data['name'];

        $createdTask = false;
        /** @var Task|null $task */
        $task = null;
        if (array_key_exists('id', $data)) {
            $taskId = $data['id'];
            $task = $taskRepository->find($taskId);
        } else {
            $task = $taskRepository->findNotCompleted($this->getUser(), $taskName);
        }

        if (is_null($task)) {
            $task = new Task($this->getUser(), $taskName);
            $entityManager->persist($task);
            $createdTask = true;
        }

        $timeEntry->setTask($task);
        $entityManager->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $apiTask->url = $this->generateUrl('api_task_view', ['id' => $task->getIdString()]);
        } else {
            $apiTask->url = $this->generateUrl('task_view', ['id' => $task->getIdString()]);
        }

        if ($createdTask) {
            return $this->jsonNoNulls($apiTask, Response::HTTP_CREATED);
        } else {
            return $this->jsonNoNulls($apiTask, Response::HTTP_OK);
        }
    }

    #[Route('/api/time-entry/{id}/task', name: 'api_time_entry_task_delete', methods: ['DELETE'])]
    #[Route('/json/time-entry/{id}/task', name: 'json_time_entry_task_delete', methods: ['DELETE'])]
    public function removeTask(
        TimeEntryRepository $timeEntryRepository,
        EntityManagerInterface $entityManager,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if (!$timeEntry->assignedToTask()) {
            throw new ApiProblemException(ApiProblem::invalidAction(TaskController::CODE_NO_ASSIGNED_TASK, 'Time entry has no assigned task'));
        }

        $timeEntry->removeTask();
        $entityManager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/time-entry/active', name: 'api_time_entry_active', methods: ['GET'])]
    #[Route('/json/time-entry/active', name: 'json_time_entry_active', methods: ['GET'])]
    public function getActiveTimeEntry(TimeEntryRepository $timeEntryRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (is_null($timeEntry)) {
            return $this->jsonNoContent();
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->jsonNoNulls($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}/statistic', name: 'api_time_entry_statistic_create', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/statistic', name: 'json_time_entry_statistic_create', methods: ['POST'])]
    public function addStatisticValue(
        Request $request,
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $statisticValue = $this->addStatisticValueRequest(
            $request,
            $entityManager,
            $statisticRepository,
            $statisticValueRepository,
            $this->getUser(),
            $timeEntry
        );

        $apiStatisticValue = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());
        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'statisticValue' => $apiStatisticValue,
                'view' => $this->renderView('statistic_value/partials/_statistic-value.html.twig', ['value' => $statisticValue]),
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiStatisticValue, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}/statistic/{statisticId}', name: 'api_time_entry_statistic_delete', methods: ['DELETE'])]
    #[Route('/json/time-entry/{id}/statistic/{statisticId}', name: 'json_time_entry_statistic_delete', methods: ['DELETE'])]
    public function removeStatisticValue(
        EntityManagerInterface $entityManager,
        TimeEntryRepository $timeEntryRepository,
        StatisticValueRepository $statisticValueRepository,
        string $id,
        string $statisticId,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeStatisticValueRequest($entityManager, $statisticValueRepository, $statisticId);
    }
}
