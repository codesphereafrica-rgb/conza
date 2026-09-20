<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckBlockedIp;
use App\Http\Middleware\LogVisitor;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CheckBlockedIp::class,
            LogVisitor::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/payments/init',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
