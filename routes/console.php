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

// Every morning at 12:15 AM, any tenant whose user end_date has passed
// gets flipped to previous_tenant (keeping the same tenant portal login).
// The end_date set on the user record is the source of truth — lease fields
// are irrelevant. E.g. end_date Aug 31 → flips on Sep 1.
Schedule::command('tenants:mark-previous')
    ->dailyAt('00:15')
    ->timezone(config('app.timezone'));

// Twice daily (8 AM + 5 PM): email admins about reminders whose remind date
// has arrived and are not yet completed (each reminder emails once, guarded
// by notified_at). The admin red bar shows the same due set until completed.
Schedule::command('reminders:notify-due')
    ->twiceDaily(8, 17)
    ->timezone(config('app.timezone'));
