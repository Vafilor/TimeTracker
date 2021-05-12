<?php

declare(strict_types=1);

namespace App\Api;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemException extends HttpException
{
    private ApiProblem $apiProblem;

    public function __construct(ApiProblem $apiProblem, Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->apiProblem = $apiProblem;
        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getApiProblem(): ApiProblem
    {
        return $this->apiProblem;
    }
}
