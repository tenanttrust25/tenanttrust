<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_sid',
        'from_number',
        'to_number',
        'tenant_id',
        'state',
        'intent',
        'tenant_name',
        'tenant_address',
        'reference',
        'issue',
        'transcript',
        'data',
        'status',
        'ended_at',
    ];

    protected $casts = [
        'transcript' => 'array',
        'data' => 'array',
        'ended_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function appendTranscript(string $speaker, ?string $text): void
    {
        $transcript = $this->transcript ?? [];
        $transcript[] = [
            'speaker' => $speaker,
            'text' => $text ?? '',
            'ts' => now()->toIso8601String(),
        ];
        $this->transcript = $transcript;
        $this->save();
    }
}
