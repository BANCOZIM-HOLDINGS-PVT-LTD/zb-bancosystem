<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentLinkVisit extends Model
{
    protected $fillable = [
        'agent_code',
        'product_id',
        'ip_address',
        'user_agent',
    ];
}
