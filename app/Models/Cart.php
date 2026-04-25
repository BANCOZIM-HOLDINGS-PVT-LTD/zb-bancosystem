<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'application_state_id',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function applicationState()
    {
        return $this->belongsTo(ApplicationState::class);
    }
}
