<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // you can add middleware config here later
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // you can customize exception handling here later
    })
    ->withSchedule(function (Schedule $schedule): void {
        // run your campaigns command every minute
        $schedule->command('campaigns:run')->everyMinute();
    })
    ->create();


