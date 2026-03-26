<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentReward extends Model
{
    protected $fillable = [
        'agent_id',
        'agent_type',
        'reward_type',
        'status',
        'sent_at',
        'notes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the parent agent model.
     */
    public function agent(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('agent', 'agent_type', 'agent_id');
    }
}
