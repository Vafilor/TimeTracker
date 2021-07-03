<?php

declare(strict_types=1);

namespace App\Api;

class ApiError
{
    private string $code;
    private string $message;
    private array $extraData;

    public static function missingProperty(string $property): ApiError
    {
        return ApiError::propertyError(ApiProblem::TYPE_VALIDATION_ERROR, 'Missing value', $property);
    }

    public static function invalidPropertyValue(string $property): ApiError
    {
        return ApiError::propertyError(ApiProblem::TYPE_VALIDATION_ERROR, 'Invalid value', $property);
    }

    public static function propertyError(string $code, string $message, string $property, array $extraData = []): ApiError
    {
        $extra = array_merge(['property' => $property], $extraData);
        return new ApiError($code, $message, $extra);
    }

    public function __construct(string $code, string $message, array $extraData = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->extraData = $extraData;
    }

    public function toArray(): array
    {
        return array_merge($this->extraData, [
            'code' => $this->code,
            'message' => $this->message
        ]);
    }
}