<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OutstandingBalanceWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.outstanding-balance-widget';
    protected static ?int $sort = 0;

    public function getViewData(): array
    {
        $user = Auth::user();

        // Match the invoice list scope: balances for everyone sharing this unit.
        $tenantIds = $user->getUnitTenantIds();
        $tenants = User::whereIn('id', $tenantIds)->get();
        $outstanding = $tenants->sum(fn (User $t) => $t->outstandingBalance());

        return [
            'outstanding' => $outstanding,
        ];
    }
}
