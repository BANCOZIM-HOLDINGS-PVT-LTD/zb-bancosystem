<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'date',
        'check_in',
        'check_out',
        'tasks_completed',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
    ];

    /**
     * Get the user that owns the daily register entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user type.
     */
    public function scopeUserType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope for today's entries.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope for employees only.
     */
    public function scopeEmployeesOnly($query)
    {
        return $query->where('user_type', 'employee');
    }

    /**
     * Scope for interns only.
     */
    public function scopeInternsOnly($query)
    {
        return $query->where('user_type', 'intern');
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): ?string
    {
        return $this->check_in ? $this->check_in->format('H:i') : null;
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): ?string
    {
        return $this->check_out ? $this->check_out->format('H:i') : null;
    }

    /**
     * Calculate hours worked.
     */
    public function getHoursWorkedAttribute(): ?float
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        return round($this->check_in->diffInMinutes($this->check_out) / 60, 2);
    }
}
