<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentActivityLog extends Model
{
    protected $fillable = [
        'agent_id',
        'agent_type',
        'activity_type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
