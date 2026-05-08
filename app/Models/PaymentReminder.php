<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReminder extends Model
{
    protected $fillable = [
        'application_state_id',
        'reminder_type',
        'reminder_stage',
        'channel',
        'delivery_status',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function applicationState()
    {
        return $this->belongsTo(ApplicationState::class);
    }
}
