<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatistic;
use App\Entity\Statistic;
use App\Form\Model\StatisticModel;
use App\Form\StatisticFormType;
use App\Repository\StatisticRepository;
use App\Util\TimeType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiStatisticController extends BaseController
{
    #[Route('/api/statistic', name: 'api_statistic_index', methods: ["GET"])]
    #[Route('/json/statistic', name: 'json_statistic_index', methods: ["GET"])]
    public function index(
        Request $request,
        StatisticRepository $statisticRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = strtolower($request->query->get('searchTerm'));
        $timeType = strtolower($request->query->get('timeType', 'timestamp'));

        if (!TimeType::isValid($timeType)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::invalidPropertyValue('timeType')
            );

            throw new ApiProblemException($problem);
        }

        $queryBuilder = $statisticRepository->findWithUser($this->getUser())
                                            ->andWhere('statistic.canonicalName LIKE :term')
                                            ->andWhere('statistic.timeType = :timeType')
                                            ->setParameter('term', "%$term%")
                                            ->setParameter('timeType', $timeType)
        ;

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'statistic.name',
                'direction' => 'asc'
            ]
        );

        $items = ApiStatistic::fromEntities($pagination->getItems(), $this->getUser());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/statistic', name: 'api_statistic_create', methods: ["POST"])]
    #[Route('/json/statistic', name: 'json_statistic_create', methods: ["POST"])]
    public function create(Request $request, StatisticRepository $statisticRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultModel = new StatisticModel();
        $form = $this->createForm(StatisticFormType::class, $defaultModel, [
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        $form->submit($data);

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(
                ApiFormError::invalidAction('bad_data', 'Form not submitted')
            );
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var StatisticModel $data */
        $data = $form->getData();
        $name = $data->getName();
        $canonicalName = Statistic::canonicalizeName($name);

        $existingStatistic = $statisticRepository->findWithUserNameCanonical($this->getUser(), $canonicalName);
        if (!is_null($existingStatistic)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_CONFLICT,
                ApiProblem::TYPE_INVALID_ACTION,
                new ApiError(StatisticController::CODE_NAME_TAKEN, "Statistic '$name' already exists")
            );

            throw new ApiProblemException($problem);
        }

        $statistic = new Statistic($this->getUser(), $name);
        $statistic->setDescription($data->getDescription());
        $statistic->setTimeType($data->getTimeType());

        $this->persist($statistic, true);

        $apiStatistic = ApiStatistic::fromEntity($statistic, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $apiStatistic->url = $this->generateUrl('statistic_view', ['id' => $statistic->getIdString()]);
        }

        return $this->jsonNoNulls($apiStatistic, Response::HTTP_CREATED);
    }
}
