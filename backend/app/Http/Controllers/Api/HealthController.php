<?php

namespace App\Http\Controllers\Api;

use App\Actions\Health\RunHealthChecks;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(RunHealthChecks $runHealthChecks): JsonResponse
    {
        $report = $runHealthChecks->handle();

        return response()->json([
            'status' => $report['status'],
            'checks' => $report['checks'],
        ], $report['status'] === 'ok' ? 200 : 503);
    }
}
