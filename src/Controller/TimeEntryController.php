<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTag;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Form\Model\TimeEntryListFilterModel;
use App\Form\Model\TimeEntryModel;
use App\Form\TimeEntryFormType;
use App\Form\TimeEntryListFilterFormType;
use App\Repository\TagLinkRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
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
        TaskRepository $taskRepository,
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
            ]
        );

        /** @var Task|null $task */
        $task = null;

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TimeEntryListFilterModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $timeEntryRepository->applyFilter($queryBuilder, $data);

            if ($data->hasTask()) {
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

    /**
     * To continue a time-entry means to create a new time entry with the same tags and task (if applicable)
     * It's you "continuing" to do something again.
     *
     * @param TimeEntryRepository $timeEntryRepository
     * @param string $id
     * @return Response
     */
    #[Route('/time-entry/{id}/continue', name: 'time_entry_continue')]
    public function continue(
        TimeEntryRepository $timeEntryRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $existingTimeEntry = $timeEntryRepository->findOrException($id);
        if (!$existingTimeEntry->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $runningTimeEntry = $timeEntryRepository->findRunningTimeEntry($this->getUser());
        if (!is_null($runningTimeEntry)) {
            $this->addFlash('danger', 'You already have a running time entry');
            return $this->redirectToRoute('time_entry_index');
        }

        $tagLinks = $tagLinkRepository->findForTimeEntry($existingTimeEntry);
        $manager = $this->getDoctrine()->getManager();

        $timeEntry = new TimeEntry($this->getUser());
        $timeEntry->setTask($existingTimeEntry->getTask());
        foreach ($tagLinks as $tagLink) {
            $copy = new TagLink($timeEntry, $tagLink->getTag());
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
        if (!$timeEntry->isAssignedTo($this->getUser())) {
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

        $apiTags = ApiTag::fromEntities($timeEntry->getTags());

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
        if (!$timeEntry->isAssignedTo($this->getUser())) {
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

    #[Route('/time-entry/{id}/resume', name: 'time_entry_resume')]
    public function resume(TimeEntryRepository $timeEntryRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timeEntry = $timeEntryRepository->findOrException($id);
        if (!$timeEntry->isAssignedTo($this->getUser())) {
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
        if (!$timeEntry->isAssignedTo($this->getUser())) {
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
}
