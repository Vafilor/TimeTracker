<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StatisticValue;
use App\Repository\StatisticValueRepository;
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

        $dateFormat = 'm/d/Y';
        $data = [];

        /** @var StatisticValue|null $previousValue */
        $previousValue = null;

        /** @var StatisticValue $statisticValue */
        foreach($pagination->getItems() as $statisticValue) {
            $key = $statisticValue->getStartedAt()->format($dateFormat);

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
}
