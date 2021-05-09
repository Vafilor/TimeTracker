<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTimeEntry;
use App\Entity\TimeEntry;
use App\Entity\TimeEntryTag;
use App\Form\Model\TimeEntryListFilterModel;
use App\Form\Model\TimeEntryModel;
use App\Form\TimeEntryFormType;
use App\Form\TimeEntryListFilterFormType;
use App\Manager\TagManager;
use App\Repository\TagRepository;
use App\Repository\TimeEntryRepository;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTimeEntryController extends BaseController
{
    #[Route('/api/time-entry', name: 'api_time_entry_index', methods: ["GET"])]
    public function index(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timeEntryRepository->findByUserQueryBuilder($this->getUser())
            ->addSelect('time_entry_tag')
            ->leftJoin('time_entry.timeEntryTags', 'time_entry_tag')
            ->leftJoin('time_entry_tag.tag', 'tag')
            ->andWhere('time_entry.deletedAt IS NULL')
        ;

        $filterForm = $formFactory->createNamed('',
                                                TimeEntryListFilterFormType::class,
                                                new TimeEntryListFilterModel(), [
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

        $items = [];
        foreach($pagination->getItems() as $timeEntry) {
            $items[] = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());
        }

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/time-entry', name: 'api_time_entry_create', methods: ["POST"])]
    public function create(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        TagRepository $tagRepository,
        TagManager $tagManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TimeEntryController::codeRunningTimer,
                    'You have a running timer',
                    ['resource' => $runningTimeEntry->getIdString()]
                )
            );
        }

        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());

        $data = json_decode($request->getContent(), true);
        if (is_null($data)) {
            $data = [];
        }

        if (array_key_exists('tags', $data)) {
            $tagNames = explode(',', $data['tags']);
            $tagObjects = $tagManager->findOrCreateByNames($tagNames);
            foreach ($tagObjects as $tag) {
                $timeEntryTag = new TimeEntryTag($timeEntry, $tag);
                $manager->persist($timeEntryTag);
            }
        }

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
            'url' => $this->generateUrl('api_time_entry_view', ['id' => $timeEntry->getIdString()])
        ];

        return $this->json($data, Response::HTTP_CREATED);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_view', methods: ["GET"])]
    public function view(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            throw $this->createNotFoundException();
        }

        $apiTimeEntry = ApiTimeEntry::fromEntity($timeEntry, $this->getUser());

        return $this->json($apiTimeEntry);
    }

    #[Route('/api/time-entry/{id}', name: 'api_time_entry_edit', methods: ["PUT"])]
    public function edit(
        Request $request,
        TimeEntryRepository $timeEntryRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var TimeEntry|null $timeEntry */
        $timeEntry = $timeEntryRepository->find($id);
        if (is_null($timeEntry)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TimeEntryFormType::class, TimeEntryModel::fromEntity($timeEntry), [
            'timezone' => $this->getUser()->getTimezone(),
            'csrf_protection' => false,
        ]);

        try {
            $data = json_decode($request->getContent(), true);
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

    // Continue
    // STOP
    // RESume
    // delete
    // add tag
    // delete tag
    // get tags
    // add task
    // delete task
    // today
}
