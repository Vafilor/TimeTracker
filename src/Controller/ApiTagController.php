<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Entity\Tag;
use App\Form\AddTagFormType;
use App\Form\Model\AddTagModel;
use App\Repository\TagRepository;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class ApiTagController extends BaseController
{
    #[Route('/api/tag', name: 'api_tag_index', methods: ['GET'])]
    #[Route('/json/tag', name: 'json_tag_index', methods: ['GET'])]
    public function index(
        Request $request,
        TagRepository $tagRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = strtolower(urldecode($request->query->get('searchTerm')));
        $excludeString = $request->query->get('exclude', '');
        $excludeItems = [];

        if ('' !== $excludeString) {
            $excludeItems = explode(',', urldecode($excludeString));
        }

        $queryBuilder = $tagRepository->findWithUser($this->getUser())
            ->andWhere('tag.canonicalName LIKE :term')
            ->setParameter('term', "%$term%");

        if (0 !== count($excludeItems)) {
            $queryBuilder = $queryBuilder->andWhere('tag.name NOT IN (:exclude)')
                ->setParameter('exclude', $excludeItems);
        }

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'tag.name',
                'direction' => 'asc',
            ]
        );

        $items = ApiTag::fromEntities($pagination->getItems());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/tag', name: 'api_tag_create', methods: ['POST'])]
    public function create(
        Request $request,
        TagRepository $tagRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultTagModel = new AddTagModel();
        $form = $this->createForm(AddTagFormType::class, $defaultTagModel, [
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddTagModel $data */
            $data = $form->getData();
            $name = $data->getName();

            $tagExists = $tagRepository->exists($name);
            if ($tagExists) {
                $problem = ApiProblem::withErrors(
                    Response::HTTP_CONFLICT,
                    ApiProblem::TYPE_INVALID_ACTION,
                    new ApiError(TagController::CODE_NAME_TAKEN, "Tag '$name' already exists")
                );

                throw new ApiProblemException($problem);
            }

            $tag = new Tag($this->getUser(), $name);
            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            return $this->jsonNoNulls(ApiTag::fromEntity($tag), Response::HTTP_CREATED);
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/api/tag/{name}', name: 'api_tag_view')]
    public function view(
        TagRepository $tagRepository,
        string $name
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $tag = $tagRepository->findOneByOrException(['name' => $name]);

        return $this->jsonNoNulls(ApiTag::fromEntity($tag));
    }
}
