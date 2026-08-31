<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/stripe/connect/webhook',
            'twilio/voice',
            'twilio/gather',
            'twilio/think',
            'twilio/dial-result',
            'twilio/record',
        ]);

        $middleware->alias([
            'twilio.signature' => \App\Http\Middleware\VerifyTwilioSignature::class,
            'role.tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role.manager' => \App\Http\Middleware\ManagerMiddleware::class,
            'role.owner' => \App\Http\Middleware\OwnerMiddleware::class,
            'role.admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
