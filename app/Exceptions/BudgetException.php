<?php

namespace App\Exceptions;

use Exception;

class BudgetException extends Exception
{
    protected int $status = 422;

    public function status(): int
    {
        return $this->status;
    }
}