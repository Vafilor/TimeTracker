<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Entity\StatisticValue;
use App\Entity\TimeEntry;
use App\Entity\User;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValue;
use App\Manager\StatisticManager;
use App\Repository\StatisticValueRepository;
use App\Repository\TimestampRepository;
use App\Util\DateRange;
use App\Util\TimeType;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiStatisticValueController extends BaseController
{
    #[Route('/api/record', name: 'api_statistic_value_create', methods: ["POST"])]
    #[Route('/json/record', name: 'json_statistic_value_create', methods: ["POST"])]
    public function addForDay(Request $request, StatisticManager $statisticManager): JsonResponse
    {
        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValue(), [
            'csrf_protection' => false
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR)
            );
        }

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(
                ApiFormError::invalidAction('bad_data', 'Form not submitted')
            );
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var AddStatisticValue $data */
        $data = $form->getData();
        $value = $data->getValue();
        $day = $data->getDay();

        $userTimeZone = $this->getUser()->getTimezone();
        if (!$day) {
            $day = new DateTime('now', new DateTimeZone($userTimeZone));
        }

        $dayRange = DateRange::dayFromDateTime($day);
        $statistic = $statisticManager->findOrCreateByName($data->getStatisticName(), $this->getUser(), TimeType::INTERVAL);
        $statisticValue = StatisticValue::fromInterval($statistic, $value, $dayRange->getStart(), $dayRange->getEnd());

        $this->persist($statisticValue, true);

        $apiModel = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());

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
}