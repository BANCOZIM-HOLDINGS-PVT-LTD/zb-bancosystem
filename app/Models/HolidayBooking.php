<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HolidayBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_name',
        'national_id',
        'phone',
        'destination',
        'start_date',
        'end_date',
        'total_cost',
        'deposit_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_cost' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    /**
     * Get the booking duration in days
     */
    public function getDurationAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }
}
