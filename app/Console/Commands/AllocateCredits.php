<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentAllocationService;
use Illuminate\Console\Command;

class AllocateCredits extends Command
{
    protected $signature = 'invoices:allocate-credits
        {--tenant= : Only reconcile this tenant id}
        {--all : Reconcile every tenant (review first — see notes)}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Roll tenant over-payments forward onto their later unpaid invoices.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $all = (bool) $this->option('all');

        if (! $tenantId && ! $all) {
            $this->error('Pass --tenant=<id> to reconcile one tenant, or --all for every tenant.');

            return self::FAILURE;
        }

        if ($all && ! $this->option('force') && ! $this->confirm('Reconcile EVERY tenant? Tenants with duplicate/mis-recorded payments could have arrears wrongly settled. Continue?')) {
            return self::FAILURE;
        }

        $query = Payment::query()->orderBy('paid_at')->orderBy('id');
        if ($tenantId) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $payments = $query->get();

        // Snapshot invoice statuses so we can report exactly what changed.
        $before = Invoice::pluck('status', 'id');

        $service = app(PaymentAllocationService::class);
        foreach ($payments as $payment) {
            $service->handle($payment);
        }

        $after = Invoice::pluck('status', 'id');

        $changed = 0;
        foreach ($after as $id => $status) {
            if (($before[$id] ?? null) !== $status) {
                $changed++;
            }
        }

        $this->info("Processed {$payments->count()} payment(s); {$changed} invoice status change(s).");

        return self::SUCCESS;
    }
}
