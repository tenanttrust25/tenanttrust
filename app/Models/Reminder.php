<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'due_date',
        'remind_date',
        'notified_at',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'remind_date' => 'date',
        'notified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /** Reminders whose remind date has arrived and are not yet completed. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('remind_date', '<=', now()->toDateString())
            ->whereNull('completed_at');
    }

    /** Due reminders that have not yet triggered the notification email. */
    public function scopeUnnotified(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }
}
