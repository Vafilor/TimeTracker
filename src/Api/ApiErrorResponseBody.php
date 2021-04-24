<?php

declare(strict_types=1);

namespace App\Api;

class ApiErrorResponseBody
{
    /**
     * @var ApiError[]
     */
    public array $errors;

    public function __construct(ApiError ...$errors)
    {
        $this->errors = $errors;
    }

    public function addError(ApiError $error): static
    {
        $this->errors[] = $error;

        return $this;
    }
}
