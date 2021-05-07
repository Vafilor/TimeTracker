<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiPagination;
use App\Api\ApiTimeEntry;
use App\Entity\Task;
use App\Form\Model\TimeEntryListFilterModel;
use App\Form\TimeEntryListFilterFormType;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTimeEntryController extends BaseController
{
    #[Route('/api/time-entry', name: 'api_time_entry_index')]
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
}
