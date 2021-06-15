<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatistic;
use App\Api\ApiTag;
use App\Entity\Tag;
use App\Form\Model\TagEditModel;
use App\Form\Model\TagListFilterModel;
use App\Form\Model\TagModel;
use App\Form\TagEditFormType;
use App\Form\TagFormType;
use App\Form\TagListFilterFormType;
use App\Repository\StatisticRepository;
use App\Repository\TagRepository;
use App\Util\TimeType;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

        $items = ApiStatistic::fromEntities($pagination->getItems());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }
}
