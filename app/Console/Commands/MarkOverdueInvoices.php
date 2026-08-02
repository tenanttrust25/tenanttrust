<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark all unpaid invoices past their due date as overdue.';

    public function handle(): int
    {
        // Rent is due the 1st. Invoices become overdue only once their due date
        // is strictly BEFORE today — so an invoice due Aug 1 stays pending until
        // the job fires at 00:00 on Aug 2 (when "today" is the 2nd). Running this
        // manually at any time of day can never prematurely flag same-day invoices.
        $invoices = Invoice::query()
            ->where('status', 'pending')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        $count = $invoices->count();

        if ($count > 0) {
            Invoice::whereIn('id', $invoices->pluck('id'))
                ->update(['status' => 'overdue']);

            // Notify each affected tenant
            $tenants = User::whereIn('id', $invoices->pluck('tenant_id')->unique())->get();

            foreach ($tenants as $tenant) {
                try {
                    app(PushNotificationService::class)->sendToUser(
                        $tenant,
                        'Rent payment overdue',
                        'Your rent payment is now overdue. Please log in to make a payment.',
                        url('/tenant/invoices')
                    );
                } catch (\Exception $e) {
                    Log::warning("Push failed for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
