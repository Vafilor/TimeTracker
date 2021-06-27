<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Statistic;
use App\Form\Model\StatisticEditModel;
use App\Form\Model\StatisticModel;
use App\Form\StatisticEditFormType;
use App\Form\StatisticFormType;
use App\Repository\StatisticRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticController extends BaseController
{
    const CODE_NAME_TAKEN = 'code_name_taken';

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

        return $this->render('statistic/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/statistic/create', name: 'statistic_create')]
    public function create(Request $request, StatisticRepository $statisticRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $defaultModel = new StatisticModel();
        $form = $this->createForm(StatisticFormType::class, $defaultModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var StatisticModel $data */
            $data = $form->getData();
            $name = $data->getName();
            $canonicalName = Statistic::canonicalizeName($name);

            $existingStatistic = $statisticRepository->findWithUserNameCanonical($this->getUser(), $canonicalName);
            if (!is_null($existingStatistic)) {
                $this->addFlash('danger', "Statistic '$name' already exists for user '{$this->getUser()->getUsername()}'");
                return $this->redirectToRoute('statistic_view', ['id' => $existingStatistic->getIdString()]);
            }

            $statistic = new Statistic($this->getUser(), $name);
            $statistic->setDescription($data->getDescription());
            $statistic->setTimeType($data->getTimeType());

            $this->persist($statistic, true);

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
            $statistic->setDescription($data->getDescription());
            $statistic->setTimeType($data->getTimeType());
            $statistic->setIcon($data->getIcon());
            $statistic->setColor($data->getColor());
            $statistic->setUnit($data->getUnit());

            $error = false;
            if ($data->getName() !== $statistic->getName()) {
                if ($statisticRepository->existsForUserName($this->getUser(), $data->getName())) {
                    $error = true;
                    $this->addFlash('danger', "Statistic with name '{$data->getName()}' already exists");
                } else {
                    $statistic->setName($data->getName());
                }
            }

            if (!$error) {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', "Statistic '{$statistic->getName()}' has been updated");
            }
        }

        return $this->render('statistic/view.html.twig', [
            'statistic' => $statistic,
            'form' => $form->createView()
        ]);
    }

    #[Route('/statistic/{id}/delete', name: 'statistic_delete')]
    public function remove(
        Request $request,
        StatisticRepository $statisticRepository,
        string $id
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($statistic, true);

        $this->addFlash('success', 'Statistic successfully removed');

        return $this->redirectToRoute('statistic_index');
    }
}
