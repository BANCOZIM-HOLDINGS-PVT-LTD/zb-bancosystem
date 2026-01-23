<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_type',
        'application_state_id',
        'reference_code',
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
        'redeemed_at',
        'redeemed_by',
        'redemption_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_cost' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    /**
     * Service type constants
     */
    const TYPE_VACATION = 'vacation';
    const TYPE_SCHOOL_FEES = 'school_fees';
    const TYPE_DRIVING_LICENSE = 'driving_license';
    const TYPE_FUNERAL_COVER = 'funeral_cover';
    const TYPE_OTHER = 'other';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REDEEMED = 'redeemed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the linked application state
     */
    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    /**
     * Get the booking duration in days
     */
    public function getDurationAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Scope for pending services
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved services
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for redeemed services
     */
    public function scopeRedeemed($query)
    {
        return $query->where('status', self::STATUS_REDEEMED);
    }

    /**
     * Scope by service type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Mark service as redeemed
     */
    public function markAsRedeemed(string $redeemedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REDEEMED,
            'redeemed_at' => now(),
            'redeemed_by' => $redeemedBy,
            'redemption_notes' => $notes,
        ]);
    }

    /**
     * Get service type options for forms
     */
    public static function getServiceTypeOptions(): array
    {
        return [
            self::TYPE_VACATION => 'Vacation Package',
            self::TYPE_SCHOOL_FEES => 'School Fees',
            self::TYPE_DRIVING_LICENSE => 'Driving License',
            self::TYPE_FUNERAL_COVER => 'Funeral Cover',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get status options for forms
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REDEEMED => 'Redeemed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
