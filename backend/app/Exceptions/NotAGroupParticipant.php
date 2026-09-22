<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class NotAGroupParticipant extends GroupOperationException
{
    protected $message = 'Not a participant of this group.';

    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
