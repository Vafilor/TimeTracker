<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiPagination;
use App\Api\ApiTag;
use App\Entity\Tag;
use App\Form\Model\TagEditModel;
use App\Form\Model\TagListFilterModel;
use App\Form\Model\TagModel;
use App\Form\TagEditFormType;
use App\Form\TagFormType;
use App\Form\TagListFilterFormType;
use App\Repository\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends BaseController
{
    const CODE_NAME_TAKEN = 'code_name_taken';

    #[Route('/tag', name: 'tag_index')]
    public function index(
        Request $request,
        TagRepository $tagRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $tagRepository->findWithUser($this->getUser());

        $filterForm = $formFactory->createNamed(
            '',
            TagListFilterFormType::class,
            new TagListFilterModel(),
            ['csrf_protection' => false, 'method' => 'GET']
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TagListFilterModel $data */
            $data = $filterForm->getData();
            $nameLike = $data->getName();
            if ($nameLike !== '') {
                $queryBuilder = $queryBuilder->andWhere('LOWER(tag.name) LIKE :name')
                    ->setParameter('name', "%$nameLike%")
                ;
            }
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'tag.name',
            'direction' => 'asc'
        ]);

        $defaultTagModel = new TagModel();
        $createForm = $this->createForm(TagFormType::class, $defaultTagModel, [
            'action' => $this->generateUrl('tag_create')
        ]);

        return $this->render('tag/index.html.twig', [
            'pagination' => $pagination,
            'form' => $createForm->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route('/tag/create', name: 'tag_create')]
    public function create(Request $request, TagRepository $tagRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultTagModel = new TagModel();
        $form = $this->createForm(TagFormType::class, $defaultTagModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TagModel $data */
            $data = $form->getData();
            $name = $data->getName();

            $tagExists = $tagRepository->existsForUser($name, $this->getUser());
            if ($tagExists) {
                $this->addFlash('danger', "Tag '$name' already exists for user '{$this->getUser()->getUsername()}'");
                return $this->redirectToRoute('tag_view', ['name' => $name]);
            }

            $tag = new Tag($this->getUser(), $name);
            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Tag '$name' has been created");

            return $this->redirectToRoute('tag_index');
        }

        return $this->redirectToRoute('tag_index');
    }

    #[Route('/tag/{id}/view', name: 'tag_view')]
    public function view(Request $request, TagRepository $tagRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $tag = $tagRepository->findOrException($id);
        if (!$tag->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $tagEditModel = TagEditModel::fromEntity($tag);

        $form = $this->createForm(TagEditFormType::class, $tagEditModel);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TagEditModel $data */
            $data = $form->getData();
            $color = $data->getColor();
            $tag->setColor($color);

            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Tag '{$tag->getName()}' has been updated");
        }

        return $this->render('tag/view.html.twig', [
            'tag' => $tag,
            'form' => $form->createView()
        ]);
    }
}
