<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Entity\Statistic;
use App\Form\AddStatisticFormType;
use App\Form\EditStatisticFormType;
use App\Form\Model\AddStatisticModel;
use App\Form\Model\EditStatisticModel;
use App\Repository\StatisticRepository;
use App\Util\TimeType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticController extends BaseController
{
    public const CODE_NAME_TAKEN = 'code_name_taken';

    #[Route('/statistic', name: 'statistic_index')]
    public function index(
        Request $request,
        StatisticRepository $statisticRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $statisticRepository->findWithUser($this->getUser());
        $queryBuilder = $statisticRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic.createdAt',
            'direction' => 'desc',
        ]);

        $form = $this->createForm(AddStatisticFormType::class, new AddStatisticModel(), [
            'action' => $this->generateUrl('statistic_create'),
        ]);

        return $this->renderForm('statistic/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/statistic_partial', name: 'partial_statistic_index')]
    public function _index(
        Request $request,
        StatisticRepository $statisticRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $term = strtolower($request->query->get('q'));
        $timeType = strtolower($request->query->get('timeType', 'instant'));

//         TODO
        if (!TimeType::isValid($timeType)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::invalidPropertyValue('timeType')
            );

            throw new ApiProblemException($problem);
        }

        $queryBuilder = $statisticRepository->findWithUser($this->getUser())
            ->andWhere('statistic.canonicalName LIKE :term')
            ->andWhere('statistic.timeType = :timeType')
            ->setParameter('term', "%$term%")
            ->setParameter('timeType', $timeType)
        ;

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'statistic.name',
                'direction' => 'asc',
            ]
        );

        return $this->render('statistic/partials/_statistic_list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/statistic/create', name: 'statistic_create')]
    public function create(
        Request $request,
        StatisticRepository $statisticRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(AddStatisticFormType::class, new AddStatisticModel());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddStatisticModel $data */
            $data = $form->getData();
            $name = $data->getName();
            $canonicalName = Statistic::canonicalizeName($name);

            $existingStatistic = $statisticRepository->findWithUserNameCanonical($this->getUser(), $canonicalName, $data->getTimeType());
            if (!is_null($existingStatistic)) {
                $this->addFlash('danger', "Statistic '$name' already exists for user '{$this->getUser()->getUsername()}'");

                return $this->redirectToRoute('statistic_index');
            }

            $statistic = new Statistic($this->getUser(), $name);
            $statistic->setDescription($data->getDescription());
            $statistic->setTimeType($data->getTimeType());

            $this->persist($statistic, true);

            $this->addFlash('success', "Statistic '$name' has been created");

            return $this->redirectToRoute('statistic_index');
        }

        $queryBuilder = $statisticRepository->findWithUser($this->getUser());
        $queryBuilder = $statisticRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'statistic.createdAt',
            'direction' => 'desc',
        ]);

        return $this->renderForm('statistic/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/statistic/{id}/view', name: 'statistic_view')]
    public function view(Request $request, StatisticRepository $statisticRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $statistic = $statisticRepository->findOrException($id);
        if (!$statistic->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $model = EditStatisticModel::fromEntity($statistic);

        $form = $this->createForm(EditStatisticFormType::class, $model);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditStatisticModel $data */
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
            'form' => $form->createView(),
            'tags' => ApiTag::fromEntities($statistic->getTags()),
        ]);
    }

    #[Route('/statistic/{id}/delete', name: 'statistic_delete')]
    public function remove(
        Request $request,
        StatisticRepository $statisticRepository,
        string $id
    ): Response {
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
