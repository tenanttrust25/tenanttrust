<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class YearEnd extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $title = 'Year End';
    protected static ?string $navigationLabel = 'Year End';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.tenant.year-end';

    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = (int) request('year', now()->year);
    }

    public function getViewData(): array
    {
        $user = Auth::user();
        $tenantIds = $user->getUnitTenantIds();
        $year = $this->selectedYear;

        $paidInvoices = Invoice::whereIn('tenant_id', $tenantIds)
            ->where('status', 'paid')
            ->whereYear('due_date', $year)
            ->orderBy('due_date')
            ->get();

        $totalPaid = $paidInvoices->sum('amount');

        return [
            'user' => $user,
            'selectedYear' => $year,
            'paidInvoices' => $paidInvoices,
            'totalPaid' => $totalPaid,
            'years' => range(now()->year - 2, now()->year),
        ];
    }

    public function getReceiptUrl(): string
    {
        return route('tenant.year-end.receipt', ['year' => $this->selectedYear]);
    }
}
