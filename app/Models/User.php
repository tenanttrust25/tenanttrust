<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(["name", "email", "password", "role", "phone", "address", "mailing_address", "start_date", "end_date", "private_notes", "stripe_account_id", "stripe_onboarding_complete", "stripe_account_created_at"])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Send the password reset notification with an explicit tenant-panel URL.
     *
     * Filament resolves the reset URL from the *current panel*, which does not
     * exist inside a queue worker. Building the signed URL before the job is
     * queued keeps resets working under any queue driver (and stops the
     * "Route [password.reset] not defined" failure).
     */
    public function sendPasswordResetNotification($token): void
    {
        $notification = new \Filament\Notifications\Auth\ResetPassword($token);

        try {
            $notification->url = \Filament\Facades\Filament::getPanel('tenant')
                ->getResetPasswordUrl($token, $this);
        } catch (\Throwable $e) {
            $notification->url = \Illuminate\Support\Facades\URL::signedRoute(
                'filament.tenant.auth.password-reset.reset',
                [
                    'email' => $this->getEmailForPasswordReset(),
                    'token' => $token,
                ]
            );
        }

        $this->notify($notification);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    /**
     * Current outstanding balance: unpaid amounts on pending/overdue invoices,
     * net of any completed (including partial) payments.
     */
    public function outstandingBalance(): float
    {
        $invoices = $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->get();

        return max(0, $invoices->sum(
            fn ($invoice) => max(0, (float) $invoice->amount - (float) ($invoice->paid_sum ?? 0))
        ));
    }

    public function tenantFiles(): HasMany
    {
        return $this->hasMany(TenantFile::class, 'tenant_id');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'tenant_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'tenant_id');
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(TenantFile::class, 'uploaded_by');
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'tenant_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id');
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class, 'tenant_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class, 'user_id');
    }

    public function activeTenancies(): HasMany
    {
        return $this->tenancies()->where('status', 'active');
    }

    /** Get all tenant IDs sharing the same unit(s) as this user */
    public function getUnitTenantIds(): array
    {
        $unitIds = $this->activeTenancies()->pluck('property_unit_id')->filter()->toArray();
        if (empty($unitIds)) {
            return [$this->id];
        }
        return Tenancy::whereIn('property_unit_id', $unitIds)
            ->where('status', 'active')
            ->pluck('tenant_id')
            ->unique()
            ->toArray();
    }

    /** Get the first active property unit ID for this tenant */
    public function getUnitId(): ?int
    {
        return $this->activeTenancies()->value('property_unit_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'owner_id');
    }

    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }
}
