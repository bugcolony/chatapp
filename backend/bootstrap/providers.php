<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Laravel\Horizon\Horizon;

$providers = [
    AppServiceProvider::class,
];

if (class_exists(Horizon::class)) {
    $providers[] = HorizonServiceProvider::class;
}

return $providers;
