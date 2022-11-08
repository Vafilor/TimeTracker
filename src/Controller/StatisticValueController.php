<?php

declare(strict_types=1);

namespace App\Controller;

use App\Error\StatisticValueDayConflict;
use App\Form\AddStatisticValueFormType;
use App\Form\EditStatisticValueFormType;
use App\Form\Model\AddStatisticValueModel;
use App\Form\Model\EditStatisticValueModel;
use App\Manager\StatisticValueManager;
use App\Repository\StatisticValueRepository;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticValueController extends BaseController
{
    #[Route('/record', name: 'statistic_value_index')]
    public function index(
        Request $request,
        StatisticValueManager $statisticValueManager,
        StatisticValueRepository $statisticValueRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $statisticValueRepository->findWithUserQueryBuilder($this->getUser());

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic_value.startedAt',
            'direction' => 'DESC',
        ]);

        $data = $statisticValueManager->groupByDay(
            $this->getUser()->getDateFormat(),
            new DateTimeZone($this->getUser()->getTimezone()),
            $pagination->getItems()
        );

        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValueModel(), [
            'timezone' => $this->getUser()->getTimezone(),
            'action' => $this->generateUrl('statistic_value_create'),
        ]);

        $form->handleRequest($request);

        return $this->renderForm('statistic_value/index.html.twig', [
            'pagination' => $pagination,
            'data' => $data,
            'form' => $form,
        ]);
    }

    #[Route('/record/{id}/view', name: 'statistic_value_view')]
    public function view(Request $request, StatisticValueRepository $statisticValueRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $statisticValue = $statisticValueRepository->findOrException($id);
        if (!$statisticValue->getStatistic()->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditStatisticValueFormType::class, EditStatisticValueModel::fromEntity($statisticValue));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditStatisticValueModel $data */
            $data = $form->getData();

            $statisticValue->setValue($data->getValue());

            $this->addFlash('success', 'Record successfully updated.');

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('statistic_value/view.html.twig', [
            'statisticValue' => $statisticValue,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/record/{id}/delete', name: 'statistic_value_delete')]
    public function remove(
        StatisticValueRepository $statisticValueRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $statisticValue = $statisticValueRepository->findOrException($id);
        if (!$statisticValue->getStatistic()->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($statisticValue, true);

        $this->addFlash('success', 'Record successfully removed');

        return $this->redirectToRoute('statistic_value_index');
    }

    #[Route('/record/create', name: 'statistic_value_create', methods: ['POST'])]
    public function addForDay(
        Request $request,
        StatisticValueManager $statisticValueManager,
        StatisticValueRepository $statisticValueRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValueModel(), [
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddStatisticValueModel $data */
            $data = $form->getData();

            try {
                $statisticValueManager->addForDay($this->getUser(), $data);
            } catch (StatisticValueDayConflict $exception) {
                $this->addFlash(
                    'danger',
                    $exception->getMessage()
                );
            }

            $this->flush();

            return $this->redirectToRoute('statistic_value_index');
        }

        $queryBuilder = $statisticValueRepository->findWithUserQueryBuilder($this->getUser());
        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic_value.startedAt',
            'direction' => 'DESC',
        ]);

        $data = $statisticValueManager->groupByDay(
            $this->getUser()->getDateFormat(),
            new DateTimeZone($this->getUser()->getTimezone()),
            $pagination->getItems()
        );

        return $this->renderForm('statistic_value/index.html.twig', [
            'pagination' => $pagination,
            'data' => $data,
            'form' => $form,
        ]);
    }
}
