<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('wo:autoprocess')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'           => \App\Http\Middleware\CheckRole::class,
            'dept.superuser' => \App\Http\Middleware\CheckDeptSuperuser::class,
            'superadmin'     => \App\Http\Middleware\CheckSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->expectsJson()) return null;

            $sqlCode = $e->getCode();

            if ($sqlCode === '23000') {
                $msg = str_contains($e->getMessage(), '1062')
                    ? 'Data duplikat: nilai yang Anda masukkan sudah ada. Gunakan nilai yang berbeda.'
                    : 'Gagal menyimpan: ada data lain yang bergantung pada entri ini.';

                return back()->withInput()->with('error', $msg);
            }

            return null;
        });
    })->create();
