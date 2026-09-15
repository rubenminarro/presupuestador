<?php

namespace App\Exceptions;

use Exception;

class WorkOrderException extends Exception
{
    protected int $status = 422;

    public function status(): int
    {
        return $this->status;
    }
}