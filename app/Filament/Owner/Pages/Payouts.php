<?php

namespace App\Filament\Owner\Pages;

use App\Models\Payout;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Payouts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Payout History';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.owner.payouts';

    public $payouts;
    public $pendingTotal = 0;
    public $paidTotal = 0;
    public $totalFees = 0;

    public function mount(): void
    {
        $user = Auth::user();

        $this->payouts = Payout::with(['property', 'invoice'])
            ->where('owner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->pendingTotal = Payout::where('owner_id', $user->id)
            ->where('status', 'pending')->sum('net_amount');

        $this->paidTotal = Payout::where('owner_id', $user->id)
            ->where('status', 'paid')->sum('net_amount');

        $this->totalFees = Payout::where('owner_id', $user->id)
            ->sum('management_fee');
    }
}
