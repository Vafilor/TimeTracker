<?php

declare(strict_types=1);

namespace App\Controller;

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
    #[Route('/tag/list', name: 'tag_list')]
    public function list(
        Request $request,
        TagRepository $tagRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $tagRepository->createDefaultQueryBuilder();

        $filterForm = $formFactory->createNamed('',
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
                $queryBuilder = $queryBuilder->andWhere('tag.name LIKE :name')
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

    #[Route('/json/tag/list', name: 'tag_json_list')]
    public function jsonList(
        Request $request,
        TagRepository $tagRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = $request->query->get('searchTerm');
        $excludeString = $request->query->get('exclude', '');
        $excludeItems = [];

        if ($excludeString !== '') {
            $excludeItems = explode(',', $excludeString);
        }

        $queryBuilder = $tagRepository->createDefaultQueryBuilder()
            ->andWhere('tag.name LIKE :term')
            ->setParameters(['term' => "%$term%"])
        ;

        if (count($excludeItems) !== 0) {
            $queryBuilder = $queryBuilder->andWhere('tag.name NOT IN (:exclude)')
                                         ->setParameter('exclude', $excludeItems)
            ;
        }

        $items = $queryBuilder->getQuery()
                              ->getResult()
        ;

        return $this->json($items);
    }

    #[Route('/tag/create', name: 'tag_create')]
    public function create(Request $request, TagRepository $tagRepository): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultTagModel = new TagModel();
        $form = $this->createForm(TagFormType::class, $defaultTagModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TagModel $data */
            $data = $form->getData();
            $name = $data->getName();

            $tagExists = $tagRepository->exists($name);
            if ($tagExists) {
                $this->addFlash('danger', "Tag '$name' already exists");
                return $this->redirectToRoute('tag_view', ['name' => $name]);
            }

            $tag = new Tag($name);
            $this->getDoctrine()->getManager()->persist($tag);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Tag '$name' has been created");

            return $this->redirectToRoute('tag_list');
        }

        return $this->redirectToRoute('tag_list');
    }

    #[Route('/tag/{name}/view', name: 'tag_view')]
    public function view(Request $request, TagRepository $tagRepository, string $name): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $tag = $tagRepository->findOneBy(['name' => $name]);
        if (is_null($tag)) {
            $this->createNotFoundException();
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