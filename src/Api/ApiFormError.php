<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;

class ApiFormError extends ApiProblem
{
    public static function formatPropertyPath(string $name): string
    {
        if (str_starts_with($name, 'children[')) {
            // 9 is for the length of "children[
            // We subtract 10 for "children[]" we don't want the ending ].
            return substr($name, 9, strlen($name) - 10);
        }

        return $name;
    }

    public function __construct(FormErrorIterator $formErrorIterator)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR);

        $errors = [];

        foreach ($formErrorIterator as $error) {
            $cause = $error->getCause();

            if ($cause instanceof ConstraintViolation) {
                $property = ApiFormError::formatPropertyPath($cause->getPropertyPath());
                $errors[] = [
                    'code' => ApiProblem::TYPE_VALIDATION_ERROR,
                    'message' => $cause->getMessage(),
                    'property' => $property,
                ];
            }
        }

        $this->set('errors', $errors);
    }
}
