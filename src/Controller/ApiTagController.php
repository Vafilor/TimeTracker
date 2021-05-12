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
use App\Form\Model\TagEditModel;
use App\Form\Model\TagListFilterModel;
use App\Form\Model\TagModel;
use App\Form\TagEditFormType;
use App\Form\TagFormType;
use App\Form\TagListFilterFormType;
use App\Repository\TagRepository;
use HttpException;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTagController extends BaseController
{
    #[Route('/api/tag', name: 'api_tag_index', methods: ["GET"])]
    public function index(
        Request $request,
        TagRepository $tagRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = $request->query->get('searchTerm');
        $excludeString = $request->query->get('exclude', '');
        $excludeItems = [];

        if ($excludeString !== '') {
            $excludeItems = explode(',', $excludeString);
        }

        $queryBuilder = $tagRepository->createDefaultQueryBuilder()
            ->andWhere('tag.name LIKE :term')
            ->setParameter('term', "%$term%");

        if (count($excludeItems) !== 0) {
            $queryBuilder = $queryBuilder->andWhere('tag.name NOT IN (:exclude)')
                ->setParameter('exclude', $excludeItems);
        }

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'tag.name',
                'direction' => 'asc'
            ]
        );

        $items = [];
        foreach ($pagination->getItems() as $tag) {
            $items[] = ApiTag::fromEntity($tag);
        }

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/tag', name: 'api_tag_create', methods: ["POST"])]
    public function create(
        Request $request,
        TagRepository $tagRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultTagModel = new TagModel();
        $form = $this->createForm(TagFormType::class, $defaultTagModel, [
            'csrf_protection' => false,
        ]);

        try {
            $data = json_decode($request->getContent(), true);
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TagModel $data */
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

            $tag = new Tag($name);
            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            return $this->json(ApiTag::fromEntity($tag), Response::HTTP_CREATED);
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        throw new HttpException('Invalid state');
    }

    #[Route('/api/tag/{name}', name: 'api_tag_view')]
    public function view(
        Request $request,
        TagRepository $tagRepository,
        string $name
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $tag = $tagRepository->findOneByOrException(['name' => $name]);

        return $this->json(ApiTag::fromEntity($tag));
    }
}
