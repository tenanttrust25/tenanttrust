<?php

namespace App\Console\Commands;

use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Console\Command;

class MarkPreviousTenants extends Command
{
    protected $signature = 'tenants:mark-previous';

    protected $description = 'Flip tenants to previous_tenant once their user end_date has passed (regardless of lease field).';

    public function handle(): int
    {
        // The end_date set on the user record is the source of truth.
        // A tenant with end_date Aug 31 flips on Sep 1 (end_date < today).
        $expiredUsers = User::query()
            ->where('role', 'tenant')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->startOfDay())
            ->get();

        $usersFlipped = 0;
        $tenanciesFlipped = 0;

        foreach ($expiredUsers as $user) {
            $user->update(['role' => 'previous_tenant']);
            $usersFlipped++;

            // Keep occupancy stats honest: expire any still-active tenancies,
            // backfilling move-out date from the user's end date if unknown.
            $activeTenancies = Tenancy::where('tenant_id', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($activeTenancies as $tenancy) {
                $tenancy->update([
                    'status' => 'expired',
                    'move_out_date' => $tenancy->move_out_date ?? $user->end_date,
                ]);
                $tenanciesFlipped++;
            }
        }

        $this->info("Flipped {$usersFlipped} user(s) to previous tenant; expired {$tenanciesFlipped} tenancy(ies).");

        return self::SUCCESS;
    }
}
