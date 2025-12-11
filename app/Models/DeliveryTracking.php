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
        // Post Office fields
        'post_office_tracking_number',
        'post_office_vehicle_registration',
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($tracking) {
            if ($tracking->wasChanged('status') && $tracking->status === 'dispatched') {
                try {
                    $smsService = app(\App\Services\SMSService::class);
                    $mobile = $tracking->recipient_phone;
                    
                    if ($mobile) {
                        $reference = $tracking->applicationState ? $tracking->applicationState->reference_code : 'N/A';
                        $message = "Your BancoZim order ({$reference}) has been dispatched via {$tracking->courier_type}.";
                        
                        if ($tracking->courier_type === 'Zim Post Office') {
                             $message .= " You will be notified when it is ready for collection at your nearest post office.";
                        } elseif ($tracking->courier_type === 'Gain Cash & Carry') {
                             $message .= " Collection at Gain Depot: {$tracking->delivery_depot}.";
                        } else {
                             $message .= " Track delivery for updates.";
                        }

                        $smsService->sendSMS($mobile, $message);
                        \Illuminate\Support\Facades\Log::info("Dispatch SMS sent to {$mobile} for tracking ID {$tracking->id}");
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send dispatch SMS for tracking ID {$tracking->id}: " . $e->getMessage());
                }
            }
        });
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
