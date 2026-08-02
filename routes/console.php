<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rent is due the 1st. At 12:00 AM on the 2nd, any invoice still unpaid
// (pending) and past its due date gets marked overdue.
Schedule::command('invoices:mark-overdue')
    ->monthlyOn(2, '00:00')
    ->timezone(config('app.timezone'));
