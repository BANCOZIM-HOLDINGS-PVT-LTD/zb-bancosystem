<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    protected $fillable = [
        'application_state_id',
        'user_id',
        'recipient',
        'mailable',
        'subject',
        'status',
        'attempts',
        'error',
        'metadata',
        'sent_at',
        'failed_at',
        'bounced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
