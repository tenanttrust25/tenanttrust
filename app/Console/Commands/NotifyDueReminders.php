<?php

namespace App\Console\Commands;

use App\Mail\RemindersDueMail;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyDueReminders extends Command
{
    protected $signature = 'reminders:notify-due';

    protected $description = 'Email admins about reminders whose remind date has arrived and are not completed.';

    public function handle(): int
    {
        $due = Reminder::query()
            ->due()
            ->unnotified()
            ->with('tenant')
            ->orderBy('remind_date')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No due reminders to notify.');

            return 0;
        }

        $admins = User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            $this->warn('No admin users found to notify.');

            return 0;
        }

        foreach ($admins as $admin) {
            if (! $admin->email) {
                continue;
            }

            try {
                Mail::mailer('smtp')->to($admin->email)->send(new RemindersDueMail($due));
                $this->info("Emailed {$admin->email} about {$due->count()} due reminder(s).");
            } catch (\Throwable $e) {
                Log::error('Due-reminder email failed', [
                    'admin' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to email {$admin->email}: {$e->getMessage()}");
            }
        }

        Reminder::whereIn('id', $due->pluck('id'))->update(['notified_at' => now()]);

        return 0;
    }
}
