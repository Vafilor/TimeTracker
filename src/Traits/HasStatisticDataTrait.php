<?php

declare(strict_types=1);

namespace App\Traits;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Controller\StatisticController;
use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValue;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Util\TimeType;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait HasStatisticDataTrait
{
    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;
    abstract public function getJsonBody(Request $request, array $default = null): array;
    abstract public function persist(mixed $obj, bool $flush = false): void;
    abstract public function doctrineRemove(mixed $obj, bool $flush = false): void;
    abstract public function jsonNoNulls($data, int $status = 200, array $headers = [], array $context = []): JsonResponse;

    public function addStatisticValueRequest(
        Request $request,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        User $assignedTo,
        Timestamp|TimeEntry $resource
    )
    {
        $timeType = TimeType::INSTANT;
        if ($resource instanceof TimeEntry) {
            $timeType = TimeType::INTERVAL;
        }

        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValue(), [
            'csrf_protection' => false,
            'timezone' => $assignedTo->getTimezone(),
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

        $statistic = $statisticRepository->findWithUserNameCanonical($assignedTo, $data->getCanonicalStatisticName());
        if (is_null($statistic)) {
            $statistic = new Statistic($assignedTo, $data->getStatisticName(), $timeType);
            $this->persist($statistic);
        } else {
            $existingStatisticValue = $statisticValueRepository->findForStatisticResource($statistic, $resource);
            if (!is_null($existingStatisticValue)) {
                $name = $statistic->getName();
                $problem = ApiProblem::withErrors(
                    Response::HTTP_CONFLICT,
                    ApiProblem::TYPE_INVALID_ACTION,
                    new ApiError(StatisticController::CODE_NAME_TAKEN, "Statistic '$name' already exists")
                );

                throw new ApiProblemException($problem);
            }
        }

        $statisticValue = StatisticValue::fromResource($statistic, $value, $resource);

        $this->persist($statisticValue, true);

        $apiModel = ApiStatisticValue::fromEntity($statisticValue, $assignedTo);

        return $this->jsonNoNulls($apiModel, Response::HTTP_CREATED);
    }

    public function removeStatisticValueRequest(
        StatisticValueRepository $statisticValueRepository,
        string $statisticId
    ): JsonResponse {
        $statisticValue = $statisticValueRepository->findOrException($statisticId);

        $this->doctrineRemove($statisticValue, true);

        return $this->jsonNoContent();
    }
}
