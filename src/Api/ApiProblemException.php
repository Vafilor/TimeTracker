<?php

declare(strict_types=1);

namespace App\Api;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemException extends HttpException
{
    public function __construct(private ApiProblem $apiProblem, Exception $previous = null, array $headers = [], $code = 0)
    {
        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getApiProblem(): ApiProblem
    {
        return $this->apiProblem;
    }
}
