<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTask;
use App\Api\ApiTimeEntry;
use App\Entity\Tag;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Form\Model\TimeEntryListFilterModel;
use App\Form\Model\TimeEntryModel;
use App\Form\TimeEntryFormType;
use App\Form\TimeEntryListFilterFormType;
use App\Manager\TagManager;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use DateTime;
use DateTimeZone;
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
    /**
     * @throws Exception
     */
    #[Route('/api/time-entry', name: 'api_time_entry_index', methods: ["GET"])]
    #[Route('/json/time-entry', name: 'json_time_entry_index', methods: ["GET"])]
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
            if (str_starts_with($request->getPathInfo(), '/api')) {
                $item->url = $this->generateUrl('api_time_entry_view', ['id' => $item->id]);
            } else {
                $item->url = $this->generateUrl('time_entry_view', ['id' => $item->id]);
            }
        }

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    /**
     * @throws Exception
     */
    #[Route('/api/time-entry/today', name: 'api_time_entry_today')]
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

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/time-entry', name: 'api_time_entry_create', methods: ["POST"])]
    #[Route('/json/time-entry', name: 'json_time_entry_create', methods: ['POST'])]
    public function create(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagManager $tagManager,
        TaskRepository $taskRepository,
    ): JsonResponse {
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

        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());

        $data = $this->getJsonBody($request, []);

        if (array_key_exists('tags', $data)) {
            $tagNames = $tagManager->parseFromString($data['tags']);
            $tagObjects = $tagManager->findOrCreateByNames($tagNames, $this->getUser());
            foreach ($tagObjects as $tag) {
                $tagLink = new TagLink($timeEntry, $tag);
                $manager->persist($tagLink);
            }
        }
        if (array_key_exists('taskId', $data)) {
            $task = $taskRepository->findOrException($data['taskId']);
            $timeEntry->setTask($task);
        }

        $manager->persist($timeEntry);
        $manager->flush();

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
            'url' => $url
        ];

        if ( boolval($request->query->get('template', 'false')) ) {
            $data['template'] = $this->renderView('time_entry/partials/_time-entry.html.twig', [
                'timeEntry' => $timeEntry
            ]);
        }

        return $this->json($data, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_view', methods: ["GET"])]
    public function view(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_edit', methods: ["PUT"])]
    #[Route('/json/time-entry/{id}', name: 'json_time_entry_update', methods: ['PUT'])]
    public function edit(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TimeEntryFormType::class, TimeEntryModel::fromEntity($timeEntry), [
            'timezone' => $this->getUser()->getTimezone(),
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

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

            $this->getDoctrine()->getManager()->flush();
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    /**
     * To continue a time-entry means to create a new time entry with the same tags.
     * It's you "continuing" to do something again.
     *
     * @param TimeEntryRepository $timeEntryRepository
     * @param string $id
     * @return Response
     * @throws Exception
     */
    #[Route('/api/time-entry/{id}/continue', name: 'api_time_entry_continue')]
    public function continue(
        TimeEntryRepository $timeEntryRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $existingTimeEntry = $timeEntryRepository->findOrException($id);
        if (!$existingTimeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TimeEntryController::CODE_RUNNING_TIMER,
                    'You already have a running time entry'
                )
            );
        }

        $tagLinks = $tagLinkRepository->findForTimeEntry($existingTimeEntry);
        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());
        foreach ($tagLinks as $tagLink) {
            $copy = new TagLink($timeEntry, $tagLink->getTag());
            $manager->persist($copy);
        }

        $manager->persist($timeEntry);
        $manager->flush();

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}/stop', name: 'api_time_entry_stop', methods: ['PUT'])]
    #[Route('/json/time-entry/{id}/stop', name: 'json_time_entry_stop', methods: ['PUT'])]
    public function stop(Request $request, TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
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

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $apiTimeEntry->url = $this->generateUrl('api_time_entry_view', ['id' => $apiTimeEntry->id]);
        } else {
            $apiTimeEntry->url = $this->generateUrl('time_entry_view', ['id' => $apiTimeEntry->id]);
        }

        return $this->json($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}/resume', name: 'api_time_entry_resume')]
    public function resume(TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
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
        $this->getDoctrine()->getManager()->flush();

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}/delete', name: 'api_time_entry_delete')]
    public function delete(TimeEntryRepository $timeEntryRepository, string $id): JsonResponse
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

        return $this->jsonNoContent();
    }

    #[Route('/api/time-entry/{id}/tag', name: 'api_time_entry_tag_create', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/tag', name: 'json_time_entry_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (!array_key_exists('tagName', $data)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::missingProperty('tagName')
            );

            throw new ApiProblemException($problem);
        }

        $tagName = $data['tagName'];

        $tag = $tagRepository->findOneBy(['name' => $tagName, 'createdBy' => $this->getUser()]);
        if (is_null($tag)) {
            $tag = new Tag($this->getUser(), $tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        } else {
            $exitingLink = $tagLinkRepository->findOneBy([
                                                                  'timeEntry' => $timeEntry,
                                                                  'tag' => $tag
                                                              ]);

            if (!is_null($exitingLink)) {
                return $this->json([], Response::HTTP_CONFLICT);
            }
        }

        $tagLink = new TagLink($timeEntry, $tag);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($tagLink);
        $manager->flush();

        $apiTag = ApiTag::fromEntity($tag);

        return $this->json($apiTag, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}/tag/{tagName}', name: 'api_time_entry_tag_delete', methods: ['DELETE'])]
    #[Route('/json/time-entry/{id}/tag/{tagName}', name: 'json_time_entry_tag_delete', methods: ['DELETE'])]
    public function deleteTag(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $tag = $tagRepository->findWithUserName($this->getUser(), $tagName);
        if (is_null($tag)) {
            throw $this->createNotFoundException();
        }

        $exitingLink = $tagLinkRepository->findOneByOrException([
                                                                         'timeEntry' => $timeEntry,
                                                                         'tag' => $tag
                                                                     ]);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/time-entry/{id}/tags', name: 'api_time_entry_tags')]
    #[Route('/json/time-entry/{id}/tags', name: 'json_time_entry_tags')]
    public function tags(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isOwnedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTags = ApiTag::fromEntities($timeEntry->getTags());

        return $this->json($apiTags);
    }

    #[Route('/api/time-entry/{id}/task', name: 'api_time_entry_task_create', methods: ['POST'])]
    #[Route('/json/time-entry/{id}/task', name: 'json_time_entry_task_create', methods: ['POST'])]
    public function addTask(
        Request $request,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        string $id
    ): JsonResponse {
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
                ApiError::missingProperty('name')
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

        if (str_starts_with($request->getPathInfo(), '/api')) {
            $apiTask->setUrl($this->generateUrl('api_task_view', ['id' => $task->getIdString()]));
        } else {
            $apiTask->setUrl($this->generateUrl('task_view', ['id' => $task->getIdString()]));
        }

        return $this->json($apiTask, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}/task', name: 'api_time_entry_task_delete', methods: ['DELETE'])]
    #[Route('/json/time-entry/{id}/task', name: 'json_time_entry_task_delete', methods: ['DELETE'])]
    public function removeTask(
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
                    TimeEntryController::CODE_NO_ASSIGNED_TASK,
                    'Time entry has no assigned task',
                )
            );
        }

        $timeEntry->removeTask();
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/time-entry/active', name: 'api_time_entry_active', methods: ['GET'])]
    #[Route('/json/time-entry/active', name: 'json_time_entry_active', methods: ['GET'])]
    public function getActiveTimeEntry(Request $request, TimeEntryRepository $timeEntryRepository): JsonResponse
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
