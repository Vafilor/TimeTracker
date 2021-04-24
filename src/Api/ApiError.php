<?php

declare(strict_types=1);

namespace App\Api;

class ApiError
{
    public string $code;
    public string $message;
    public mixed $data;

    public function __construct(string $code, string $message, mixed $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}
