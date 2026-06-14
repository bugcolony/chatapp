<?php

namespace App\Enums;

enum AuthProvider: string
{
    case GitHub = 'github';
    case Google = 'google';
}
