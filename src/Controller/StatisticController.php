<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiPagination;
use App\Api\ApiTag;
use App\Entity\Statistic;
use App\Entity\Tag;
use App\Form\Model\StatisticEditModel;
use App\Form\Model\StatisticModel;
use App\Form\Model\TagEditModel;
use App\Form\Model\TagListFilterModel;
use App\Form\Model\TagModel;
use App\Form\StatisticEditFormType;
use App\Form\StatisticFormType;
use App\Form\TagEditFormType;
use App\Form\TagFormType;
use App\Form\TagListFilterFormType;
use App\Repository\StatisticRepository;
use App\Repository\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticController extends BaseController
{
    #[Route('/statistic', name: 'statistic_index')]
    public function index(
        Request $request,
        StatisticRepository $statisticRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $statisticRepository->findWithUser($this->getUser());

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic.name',
            'direction' => 'asc'
        ]);

        $defaultModel = new StatisticModel();
        $createForm = $this->createForm(StatisticFormType::class, $defaultModel, [
            'action' => $this->generateUrl('statistic_create')
        ]);

        return $this->render('statistic/index.html.twig', [
            'pagination' => $pagination,
            'form' => $createForm->createView(),
        ]);
    }

    #[Route('/statistic/create', name: 'statistic_create')]
    public function create(Request $request, TagRepository $tagRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultModel = new StatisticModel();
        $form = $this->createForm(StatisticFormType::class, $defaultModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var StatisticModel $data */
            $data = $form->getData();
            $name = $data->getName();

            $existingStatistic = $tagRepository->findWithUserName($this->getUser(), $name);
            if (!is_null($existingStatistic)) {
                $this->addFlash('danger', "Statistic '$name' already exists for user '{$this->getUser()->getUsername()}'");
                return $this->redirectToRoute('statistic_view', ['id' => $existingStatistic->getIdString()]);
            }

            $statistic = new Statistic($this->getUser(), $name);
            $this->getDoctrine()->getManager()->persist($statistic);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Statistic '$name' has been created");

            return $this->redirectToRoute('statistic_index');
        }

        return $this->redirectToRoute('statistic_index');
    }

    #[Route('/statistic/{id}/view', name: 'statistic_view')]
    public function view(Request $request, StatisticRepository $statisticRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $model = StatisticEditModel::fromEntity($statistic);

        $form = $this->createForm(StatisticEditFormType::class, $model);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var StatisticEditModel $data */
            $data = $form->getData();
            $statistic->setName($data->getName());
            $statistic->setDescription($data->getDescription());
            $statistic->setTimeType($data->getTimeType());
            $statistic->setValueType($data->getValueType());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "Statistic '{$statistic->getName()}' has been updated");
        }

        return $this->render('statistic/view.html.twig', [
            'statistic' => $statistic,
            'form' => $form->createView()
        ]);
    }
}
