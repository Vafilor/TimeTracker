<?php

declare(strict_types=1);

namespace App\Api;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ApiProblem
{
    public const TYPE_VALIDATION_ERROR = 'validator_error';
    public const TYPE_INVALID_REQUEST_BODY = 'invalid_body_format';
    public const TYPE_INVALID_ACTION = 'invalid_action';

    private static array $titles = [
        self::TYPE_VALIDATION_ERROR => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY => 'Invalid JSON format',
        self::TYPE_INVALID_ACTION => 'Invalid action',
    ];
    private ?string $type;
    private string $title;
    private array $extraData;

    public static function missingKeysInBody(string ...$keys): ApiProblem
    {
        $errors = [];
        foreach ($keys as $key) {
            $errors[] = ApiError::missingProperty($key);
        }

        return self::withErrors(
            Response::HTTP_BAD_REQUEST,
            ApiProblem::TYPE_INVALID_REQUEST_BODY,
            ...$errors
        );
    }

    public static function invalidAction(string $code, string $message, array $extra = []): ApiProblem
    {
        return self::withErrors(
            Response::HTTP_BAD_REQUEST,
            self::TYPE_INVALID_ACTION,
            new ApiError($code, $message, $extra)
        );
    }

    public static function withErrors(int $statusCode, string $type, ApiError ...$apiErrors): ApiProblem
    {
        $problem = new ApiProblem($statusCode, $type);

        foreach ($apiErrors as $apiError) {
            $problem->addError($apiError);
        }

        return $problem;
    }

    public function __construct(private int $statusCode, string $type = null)
    {
        $this->extraData = [];

        if (is_null($type)) {
            // no type? The default is about:blank and the title should be
            // the standard status code message
            $type = 'about:blank';
            $title = Response::$statusTexts[$statusCode] ?? "Unknown Status Code $statusCode";
        } else {
            if (!isset(static::$titles[$type])) {
                throw new InvalidArgumentException("No title for type $type");
            }

            $title = static::$titles[$type];
        }

        $this->type = $type;
        $this->title = $title;
    }

    public function toArray()
    {
        $data = array_merge(
            $this->extraData,
            [
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title,
            ]
        );

        return $data;
    }

    public function set($name, $value): self
    {
        $this->extraData[$name] = $value;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function addError(ApiError $apiError): self
    {
        if (!array_key_exists('errors', $this->extraData)) {
            $this->extraData['errors'] = [];
        }

        $this->extraData['errors'][] = $apiError->toArray();

        return $this;
    }
}
