<?php

namespace App\Enums;

enum FileStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case FAILED = 'failed';
}
