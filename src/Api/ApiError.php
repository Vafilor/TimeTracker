<?php

declare(strict_types=1);

namespace App\Api;

class ApiError
{
    private string $code;
    private string $message;
    private array $extraData;

    public function __construct(string $code, string $message, array $extraData)
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