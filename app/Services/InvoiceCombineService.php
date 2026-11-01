<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Consolidates multiple unpaid invoices for the same tenant/unit into a
 * single invoice carrying the total NET outstanding balance.
 *
 * Originals are marked cancelled (kept for history). Any partial payments
 * already recorded against the originals stay attached to them and are
 * deducted from the new invoice amount, so nobody is ever overcharged.
 */
class InvoiceCombineService
{
    /**
     * Filament bulk action ready for any admin invoice table.
     */
    public static function bulkAction(): BulkAction
    {
        return BulkAction::make('combineInvoices')
            ->label('Combine into one invoice')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Combine invoices?')
            ->modalDescription('Combines the selected unpaid invoices into a single invoice for the total outstanding amount. Original invoices are cancelled (kept in history). Partial payments are deducted from the new total so the tenant is never overcharged.')
            ->modalSubmitActionLabel('Combine')
            ->action(function (Collection $records): void {
                try {
                    $invoice = self::combine($records);

                    Notification::make()
                        ->title('Invoices combined')
                        ->body('New invoice ' . $invoice->invoice_number . ' for $' . number_format((float) $invoice->amount, 2) . ' — originals cancelled.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Combine failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * @param  Collection<int, Invoice>  $records
     *
     * @throws \RuntimeException
     */
    public static function combine(Collection $records): Invoice
    {
        $invoices = $records->filter(fn ($record) => $record instanceof Invoice);

        if ($invoices->count() < 2) {
            throw new \RuntimeException('Select at least two invoices to combine.');
        }

        $unpaid = $invoices->filter(
            fn (Invoice $invoice) => in_array($invoice->status, ['pending', 'overdue'], true)
        );

        if ($unpaid->count() !== $invoices->count()) {
            throw new \RuntimeException('Only unpaid invoices (pending or overdue) can be combined.');
        }

        if ($unpaid->pluck('tenant_id')->unique()->count() !== 1) {
            throw new \RuntimeException('Selected invoices must belong to the same tenant.');
        }

        if ($unpaid->pluck('property_unit_id')->unique()->count() !== 1) {
            throw new \RuntimeException('Selected invoices must belong to the same unit.');
        }

        $first = $unpaid->first();

        $total = 0.0;
        $refs = [];
        $earliestDue = null;
        $anyOverdue = false;

        foreach ($unpaid as $invoice) {
            $paid = (float) $invoice->payments()->where('status', 'completed')->sum('amount');
            $owing = max(0.0, round((float) $invoice->amount - $paid, 2));
            $total += $owing;

            $refs[] = $invoice->invoice_number
                . ' (' . ($invoice->due_date?->format('M j, Y') ?? 'no due date') . ')';

            if ($invoice->status === 'overdue') {
                $anyOverdue = true;
            }

            if ($earliestDue === null || ($invoice->due_date && $invoice->due_date->lt($earliestDue))) {
                $earliestDue = $invoice->due_date;
            }
        }

        $total = round($total, 2);

        if ($total <= 0) {
            throw new \RuntimeException('Selected invoices carry no outstanding balance.');
        }

        $number = self::uniqueInvoiceNumber();

        $combined = DB::transaction(function () use ($first, $unpaid, $total, $refs, $earliestDue, $anyOverdue, $number) {
            $invoice = Invoice::create([
                'tenant_id' => $first->tenant_id,
                'property_id' => $first->property_id,
                'property_unit_id' => $first->property_unit_id,
                'invoice_number' => $number,
                'amount' => $total,
                'description' => 'Consolidated balance from: ' . implode(', ', $refs)
                    . '. Original invoices cancelled — see history.',
                'due_date' => $earliestDue ?? now(),
                'status' => $anyOverdue ? 'overdue' : 'pending',
            ]);

            Invoice::whereIn('id', $unpaid->pluck('id'))->update(['status' => 'cancelled']);

            return $invoice;
        });

        // Let the tenant know their balance was consolidated so the cancelled
        // originals in their portal don't come as a surprise.
        try {
            $tenant = User::find($first->tenant_id);
            if ($tenant) {
                app(PushNotificationService::class)->sendToUser(
                    $tenant,
                    'Balance consolidated',
                    'Your outstanding balance has been combined into a single invoice (' . $combined->invoice_number . ') for $' . number_format((float) $combined->amount, 2) . '. Please review and pay from your invoices page.',
                    url('/tenant/invoices')
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Combine notification failed for tenant ' . $first->tenant_id . ': ' . $e->getMessage());
        }

        return $combined;
    }

    protected static function uniqueInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . date('Y') . '-' . strtoupper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
