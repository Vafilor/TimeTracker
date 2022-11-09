<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Error\StatisticValueDayConflict;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValueModel;
use App\Manager\StatisticValueManager;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiStatisticValueController extends BaseController
{
    public const CODE_DAY_TAKEN = 'code_day_taken';

    #[Route('/api/record', name: 'api_statistic_value_create', methods: ['POST'])]
    #[Route('/json/record', name: 'json_statistic_value_create', methods: ['POST'])]
    public function addForDay(
        Request $request,
        StatisticValueManager $statisticValueManager,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository
    ): JsonResponse {
        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValueModel(), [
            'csrf_protection' => false,
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $data = $this->getJsonBody($request);
        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(ApiFormError::invalidAction('bad_data', 'Form not submitted'));
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var AddStatisticValueModel $data */
        $data = $form->getData();

        try {
            $statisticValue = $statisticValueManager->addForDay($this->getUser(), $data);
        } catch (StatisticValueDayConflict $exception) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_CONFLICT,
                ApiProblem::TYPE_INVALID_ACTION,
                new ApiError(
                    self::CODE_DAY_TAKEN,
                    $exception->getMessage()
                )
            );

            throw new ApiProblemException($problem);
        }

        $this->flush();

        $apiModel = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'statisticValue' => $apiModel,
                'view' => $this->renderView('statistic_value/partials/_statistic-value.html.twig', ['value' => $statisticValue]),
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiModel, Response::HTTP_CREATED);
    }

    #[Route('/api/statistic-value/{id}', name: 'api_statistic_value_update', methods: ['PUT'])]
    #[Route('/json/statistic-value/{id}', name: 'json_statistic_value_update', methods: ['PUT'])]
    public function updateStatisticValue(
        Request $request,
        StatisticValueRepository $statisticValueRepository,
        string $id,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $data = $this->getJsonBody($request);
        $this->ensureKeysExist($data, 'value');
        $value = floatval($data['value']);

        $statisticValue = $statisticValueRepository->findOrException($id);
        if (!$statisticValue->getStatistic()->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $statisticValue->setValue($value);

        $this->flush();

        $apiModel = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());

        return $this->jsonNoNulls($apiModel, Response::HTTP_OK);
    }

    #[Route('/api/statistic-value/{id}', name: 'api_statistic_value_delete', methods: ['DELETE'])]
    #[Route('/json/statistic-value/{id}', name: 'json_statistic_value_delete', methods: ['DELETE'])]
    public function removeStatisticValue(
        StatisticValueRepository $statisticValueRepository,
        string $id,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $statisticValue = $statisticValueRepository->findOrException($id);
        if (!$statisticValue->getStatistic()->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($statisticValue, true);

        return $this->jsonNoContent();
    }
}
