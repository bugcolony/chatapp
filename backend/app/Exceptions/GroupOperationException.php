<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

abstract class GroupOperationException extends Exception
{
    public function status(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
