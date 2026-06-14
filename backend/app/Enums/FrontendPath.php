<?php

namespace App\Enums;

enum FrontendPath: string
{
    case Login = '/login';
    case AuthCallback = '/login/process';
}
