<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Form\AddTagFormType;
use App\Form\EditTagFormType;
use App\Form\FilterTagFormType;
use App\Form\Model\AddTagModel;
use App\Form\Model\EditTagModel;
use App\Form\Model\FilterTagModel;
use App\Repository\TagRepository;
use App\Util\DateTimeUtil;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends BaseController
{
    public const CODE_NAME_TAKEN = 'code_name_taken';
    public const CODE_TAG_NOT_ASSOCIATED = 'tag_not_associated';

    private function createIndexFilterForm(FormFactoryInterface $formFactory)
    {
        return $formFactory->createNamed(
            '',
            FilterTagFormType::class,
            new FilterTagModel(),
            [
                'method' => 'GET',
                'allow_extra_fields' => true,
                'csrf_protection' => false,
            ]
        );
    }

    #[Route('/tag', name: 'tag_index')]
    public function index(
        Request $request,
        TagRepository $tagRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $tagRepository->findWithUser($this->getUser());
        $filterForm = $this->createIndexFilterForm($formFactory);

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterTagModel $data */
            $data = $filterForm->getData();
            $nameLike = $data->getName();
            if (!is_null($nameLike)) {
                $queryBuilder = $queryBuilder->andWhere('tag.canonicalName LIKE :name')
                    ->setParameter('name', "%$nameLike%")
                ;
            }
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'tag.name',
            'direction' => 'asc',
        ]);

        $createForm = $this->createForm(AddTagFormType::class, new AddTagModel(), [
            'action' => $this->generateUrl('tag_create'),
        ]);

        return $this->renderForm('tag/index.html.twig', [
            'pagination' => $pagination,
            'form' => $createForm,
            'filterForm' => $filterForm,
        ]);
    }

    #[Route('/tag/create', name: 'tag_create')]
    public function create(
        Request $request,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory,
        TagRepository $tagRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultTagModel = new AddTagModel();
        $form = $this->createForm(AddTagFormType::class, $defaultTagModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddTagModel $data */
            $data = $form->getData();
            $name = $data->getName();

            $existingTag = $tagRepository->findWithUserName($this->getUser(), $name);
            if (!is_null($existingTag)) {
                $this->addFlash('danger', "Tag '$name' already exists for user '{$this->getUser()->getUsername()}'");

                return $this->redirectToRoute('tag_view', ['id' => $existingTag->getIdString()]);
            }

            $tag = new Tag($this->getUser(), $name, $data->getColor());
            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Tag '$name' has been created");

            return $this->redirectToRoute('tag_index');
        }

        $filterForm = $this->createIndexFilterForm($formFactory);
        $createForm = $this->createForm(AddTagFormType::class, new AddTagModel(), [
            'action' => $this->generateUrl('tag_create'),
        ]);

        $queryBuilder = $tagRepository->findWithUser($this->getUser());
        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'tag.name',
            'direction' => 'asc',
        ]);

        return $this->redirectToRoute('tag_index', [
            'pagination' => $pagination,
            'filterForm' => $filterForm,
            'form' => $createForm,
        ]);
    }

    #[Route('/tag/{id}/view', name: 'tag_view')]
    public function view(Request $request, TagRepository $tagRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $tag = $tagRepository->findOrException($id);
        if (!$tag->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $tagEditModel = EditTagModel::fromEntity($tag);

        $form = $this->createForm(EditTagFormType::class, $tagEditModel);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTagModel $data */
            $data = $form->getData();
            $color = $data->getColor();
            $tag->setColor($color);

            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Tag '{$tag->getName()}' has been updated");
        }

        $count = $tagRepository->getReferenceCount($tag);
        $totalTime = $tagRepository->getTimeEntryDuration($tag);

        return $this->render('tag/view.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
            'references' => $count,
            'duration' => DateTimeUtil::dateIntervalFromSeconds($totalTime),
        ]);
    }

    #[Route('/tag/{id}/delete', name: 'tag_delete')]
    public function remove(
        Request $request,
        TagRepository $tagRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $tag = $tagRepository->findOrException($id);
        if (!$tag->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($tag, true);

        $this->addFlash('success', 'Tag successfully removed');

        return $this->redirectToRoute('tag_index');
    }

    #[Route('/tag_partial', name: 'tag_index_partial', methods: ['GET'])]
    public function partialIndex(
        Request $request,
        TagRepository $tagRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = strtolower(urldecode($request->query->get('q')));
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

        return $this->render('tag/partials/_tag_list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
