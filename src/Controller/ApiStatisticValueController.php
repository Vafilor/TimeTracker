<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Entity\TimeEntry;
use App\Entity\User;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValue;
use App\Manager\StatisticManager;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TimestampRepository;
use App\Util\DateRange;
use App\Util\TimeType;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiStatisticValueController extends BaseController
{
    const CODE_DAY_TAKEN = 'code_day_taken';

    #[Route('/api/record', name: 'api_statistic_value_create', methods: ["POST"])]
    #[Route('/json/record', name: 'json_statistic_value_create', methods: ["POST"])]
    public function addForDay(
        Request $request,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository
    ): JsonResponse
    {
        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValue(), [
            'csrf_protection' => false,
            'timezone'=> $this->getUser()->getTimezone(),
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

        if (!$day) {
            $day = new DateTime('now', new DateTimeZone($this->getUser()->getTimezone()));
        } else {
            // The form converts the Date from the User's timezone to UTC
            // We're about to get a start/end of day from it, so we actually need it in the user's timezone, then convert to UTC
            // Otherwise, we get wrong start/end dates
            $day->setTimezone(new DateTimeZone($this->getUser()->getTimezone()));
        }

        $dayRange = DateRange::dayFromDateTime($day);
        $statistic = $statisticRepository->findOneBy([
          'canonicalName' => Statistic::canonicalizeName($data->getStatisticName()),
          'assignedTo' => $this->getUser(),
          'timeType' => TimeType::INTERVAL,
        ]);

        if (is_null($statistic)) {
            $statistic = new Statistic($this->getUser(), $data->getStatisticName(), TimeType::INTERVAL);
            $this->persist($statistic);
        } else {
            try {
                $statisticValue = $statisticValueRepository->findForDay($statistic, $dayRange);
            } catch (NonUniqueResultException $exception) {
                // If there's more than one, treat it as a conflict.
                $statisticValue = ""; // fake not null object.
            }

            if (!is_null($statisticValue)) {
                $date = $day->format($this->getUser()->getDateFormat());
                $problem = ApiProblem::withErrors(
                    Response::HTTP_CONFLICT,
                    ApiProblem::TYPE_INVALID_ACTION,
                    new ApiError(
                        self::CODE_DAY_TAKEN,
                        "Record for statistic '{$statistic->getName()}' already exists for $date"
                    )
                );

                throw new ApiProblemException($problem);
            }
        }

        // StatisticValue converts the start/end to UTC before setting
        $statisticValue = StatisticValue::fromInterval($statistic, $value, $dayRange->getStart(), $dayRange->getEnd());

        $this->persist($statisticValue, true);

        $apiModel = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'statisticValue' => $apiModel,
                'view' => $this->renderView('statistic_value/partials/_statistic-value.html.twig', ['value' => $statisticValue])
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
        Request $request,
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
