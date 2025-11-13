<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateTransition extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'state_id',
        'from_step',
        'to_step',
        'channel',
        'transition_data',
    ];
    
    protected $casts = [
        'transition_data' => 'array',
    ];
    
    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class, 'state_id');
    }
}
