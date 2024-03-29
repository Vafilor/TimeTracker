<?php

declare(strict_types=1);

namespace App\Error;

use Throwable;

class StatisticValueDayConflict extends ApplicationError
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
