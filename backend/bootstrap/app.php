<?php

use App\Exceptions\ChannelDoesNotSupportMessages;
use App\Exceptions\MessageAttachmentStorageException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->trustProxies(at: 'REMOTE_ADDR')
            ->throttleWithRedis()
            ->statefulApi()
            ->redirectGuestsTo(null);
    })
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            function (ChannelDoesNotSupportMessages $exception, Request $request) {
                if (! $request->expectsJson()) {
                    return null;
                }

                return response()->json([
                    'message' => 'This channel does not accept messages.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            },
        );

        $exceptions->render(
            function (MessageAttachmentStorageException $exception, Request $request) {
                if (! $request->expectsJson()) {
                    return null;
                }

                return response()->json([
                    'message' => 'Attachment storage is temporarily unavailable.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            },
        );
    })->create();

$app->useEnvironmentPath('/dev')->loadEnvironmentFrom('null');

return $app;
