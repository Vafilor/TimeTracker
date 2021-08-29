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
use App\Form\Model\AddStatisticModel;
use App\Form\AddStatisticFormType;
use App\Manager\TagManager;
use App\Repository\StatisticRepository;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Traits\TaggableController;
use App\Util\TimeType;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiStatisticController extends BaseController
{
    use TaggableController;

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

        $defaultModel = new AddStatisticModel();
        $form = $this->createForm(AddStatisticFormType::class, $defaultModel, [
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR)
            );
        }

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(
                ApiFormError::invalidAction('bad_data', 'Form not submitted')
            );
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var AddStatisticModel $data */
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
            $response = [
                'statistic' => $apiStatistic,
                'view' => $this->renderView('statistic/partials/_statistic.html.twig', ['statistic' => $statistic])
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiStatistic, Response::HTTP_CREATED);
    }

    #[Route('/api/statistic/{id}/tag', name: 'api_statistic_tag_create', methods: ['POST'])]
    #[Route('/json/statistic/{id}/tag', name: 'json_statistic_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        StatisticRepository $statisticRepository,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->addTagRequest(
            $request,
            $tagManager,
            $tagLinkRepository,
            $this->getUser(),
            $statistic
        );
    }

    #[Route('/api/statistic/{id}/tag/{tagName}', name: 'api_statistic_tag_delete', methods: ['DELETE'])]
    #[Route('/json/statistic/{id}/tag/{tagName}', name: 'json_statistic_tag_delete', methods: ['DELETE'])]
    public function removeTag(
        Request $request,
        StatisticRepository $statisticRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeTagRequest(
            $tagRepository,
            $tagLinkRepository,
            $this->getUser(),
            $tagName,
            $statistic
        );
    }

    #[Route('/api/statistic/{id}/tags', name: 'api_statistic_tags', methods: ["GET"])]
    #[Route('/json/statistic/{id}/tags', name: 'json_statistic_tags', methods: ["GET"])]
    public function indexTag(
        Request $request,
        StatisticRepository $statisticRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->getTagsRequest($statistic);
    }
}
