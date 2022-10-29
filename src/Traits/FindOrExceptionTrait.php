<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait FindOrExceptionTrait
{
    abstract public function find($id, $lockMode = null, $lockVersion = null);

    abstract public function findOneBy(array $criteria, array $orderBy = null);

    public function findOrException($id, $lockMode = null, $lockVersion = null)
    {
        $result = $this->find($id, $lockMode, $lockVersion);

        if (is_null($result)) {
            throw new NotFoundHttpException();
        }

        return $result;
    }

    public function findOneByOrException(array $criteria, array $orderBy = null)
    {
        $result = $this->findOneBy($criteria, $orderBy);

        if (is_null($result)) {
            throw new NotFoundHttpException();
        }

        return $result;
    }
}
