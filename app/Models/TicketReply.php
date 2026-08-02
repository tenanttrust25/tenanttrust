<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    protected $fillable = ["maintenance_ticket_id", "user_id", "message"];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, "maintenance_ticket_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
