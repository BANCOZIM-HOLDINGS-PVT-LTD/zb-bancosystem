<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTracking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_state_id',
        'status',
        'product_type',
        'product_serial_number',
        'outlet_voucher_number',
        'swift_tracking_number',
        'courier_service',
        'courier_type',
        'delivery_address',
        'recipient_name',
        'recipient_phone',
        'dispatched_at',
        'estimated_delivery_date',
        'delivered_at',
        'admin_notes',
        'delivery_notes',
        'delivery_signature',
        'delivery_note',
        'delivery_photo',
        'status_history',
        'assigned_to',
        // Gain Outlet fields
        'gain_voucher_number',
        'gain_depot_location',
        // Bus Courier fields
        'bus_registration_number',
        'bus_driver_name',
        'bus_driver_phone',
        // Bancozim fields
        'bancozim_agent_name',
        'bancozim_agent_phone',
        'bancozim_location',
        // Client information
        'client_national_id',
        'delivery_depot',
    ];

    protected $casts = [
        'status_history' => 'array',
        'dispatched_at' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the application state
     */
    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    /**
     * Get the admin user assigned to this delivery
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Add a status update to history
     */
    public function addStatusUpdate(string $status, ?string $notes = null, ?array $metadata = []): void
    {
        $history = $this->status_history ?? [];

        $history[] = [
            'status' => $status,
            'notes' => $notes,
            'metadata' => $metadata,
            'updated_by' => auth()->id(),
            'updated_at' => now()->toISOString(),
        ];

        $this->update([
            'status' => $status,
            'status_history' => $history,
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'processing' => 'blue',
            'dispatched' => 'indigo',
            'in_transit' => 'purple',
            'out_for_delivery' => 'yellow',
            'delivered' => 'green',
            'failed' => 'red',
            'returned' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'dispatched' => 'Dispatched',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'returned' => 'Returned',
            default => ucfirst($this->status),
        };
    }
}
