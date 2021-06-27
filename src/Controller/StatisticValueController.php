<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StatisticValue;
use App\Form\Model\StatisticValueEditModel;
use App\Form\StatisticValueEditFormType;
use App\Repository\StatisticValueRepository;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticValueController extends BaseController
{
    #[Route('/record', name: 'statistic_value_index')]
    public function index(
        Request $request,
        StatisticValueRepository $statisticValueRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $statisticValueRepository->findWithUserQueryBuilder($this->getUser());

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic_value.startedAt',
            'direction' => 'DESC'
        ]);

        $data = [];

        /** @var StatisticValue|null $previousValue */
        $previousValue = null;

        $dateFormat = $this->getUser()->getDateFormat();
        $timezone = $this->getUser()->getTimezone();

        /** @var StatisticValue $statisticValue */
        foreach($pagination->getItems() as $statisticValue) {
            $start = clone $statisticValue->getStartedAt();
            $startedAt = $start->setTimezone(new DateTimeZone($timezone));
            $key = $startedAt->format($dateFormat);

            if (!$previousValue) {
                $previousValue = $statisticValue;
                $data[$key] = [$statisticValue];
                continue;
            }

            $dateDiff = $previousValue->getStartedAt()->diff($statisticValue->getStartedAt());
            if ($dateDiff->d < 1) {
                $data[$key][] = $statisticValue;
            } else {
                $data[$key] = [$statisticValue];
            }

            $previousValue = $statisticValue;
        }

        return $this->render('statistic_value/index.html.twig', [
            'pagination' => $pagination,
            'data' => $data
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

        $form = $this->createForm(StatisticValueEditFormType::class, StatisticValueEditModel::fromEntity($statisticValue));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var StatisticValueEditModel $data */
            $data = $form->getData();

            $statisticValue->setValue($data->getValue());

            $this->addFlash('success', 'Record successfully updated.');

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('statistic_value/view.html.twig', [
            'statisticValue' => $statisticValue,
            'form' => $form->createView()
        ]);
    }

    #[Route('/record/{id}/delete', name: 'statistic_value_delete')]
    public function remove(
        Request $request,
        StatisticValueRepository $statisticValueRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $statisticValue = $statisticValueRepository->findOrException($id);
        if (!$statisticValue->getStatistic()->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($statisticValue, true);

        $this->addFlash('success', 'Record successfully removed');

        return $this->redirectToRoute('statistic_value_index');
    }
}
