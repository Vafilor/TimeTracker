<?php

declare(strict_types=1);

namespace App\Traits;

use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Entity\StatisticValue;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValue;
use App\Manager\StatisticManager;
use App\Repository\StatisticValueRepository;
use App\Util\TimeType;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait StatisticsValueController
{
    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;
    abstract public function getJsonBody(Request $request, array $default = null): array;
    abstract public function persistAndFlush(mixed $obj): void;
    abstract public function removeAndFlush(mixed $obj): void;
    abstract public function jsonNoNulls($data, int $status = 200, array $headers = [], array $context = []): JsonResponse;

    public function addStatisticValueRequest(
        Request $request,
        StatisticManager $statisticManager,
        User $assignedTo,
        Timestamp|TimeEntry $resource)
    {
        $timeType = TimeType::instant;
        if ($resource instanceof TimeEntry) {
            $timeType = TimeType::interval;
        }

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
        $name = $data->getStatisticName();
        $value = $data->getValue();

        $statistic = $statisticManager->findOrCreateByName($data->getCanonicalStatisticName(), $assignedTo, $timeType);
        $statisticValue = StatisticValue::fromResource($statistic, $value, $resource);

        $this->persistAndFlush($statisticValue);

        $apiModel = ApiStatisticValue::fromEntity($statisticValue);

        return $this->jsonNoNulls($apiModel, Response::HTTP_CREATED);
    }

    public function removeStatisticValueRequest(
        StatisticValueRepository $statisticValueRepository,
        string $statisticId
    )
    {
        $statisticValue = $statisticValueRepository->findOrException($statisticId);

        $this->removeAndFlush($statisticValue);

        return $this->jsonNoContent();
    }
}
