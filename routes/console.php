<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep PR -> PO tracking fresh without anyone needing to check manually.
Schedule::command('qad:sync-po-numbers')->everyTenMinutes()->withoutOverlapping();
