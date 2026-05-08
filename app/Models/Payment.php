<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_INSUFFICIENT_FUNDS = 'insufficient_funds';

    protected $fillable = [
        'application_state_id',
        'provider',
        'method',
        'amount',
        'currency',
        'status',
        'reference',
        'provider_reference',
        'poll_url',
        'receipt_number',
        'metadata',
        'paid_at',
        'failed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    public function cashPayment(): HasOne
    {
        return $this->hasOne(CashPayment::class);
    }

    public function markPaid(?string $providerReference = null, array $metadata = []): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'provider_reference' => $providerReference ?? $this->provider_reference,
            'receipt_number' => $this->receipt_number ?: self::generateReceiptNumber(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'paid_at' => $this->paid_at ?? now(),
            'failed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function markFailed(string $status = self::STATUS_FAILED, array $metadata = []): void
    {
        $this->update([
            'status' => $status,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'failed_at' => now(),
        ]);
    }

    public function markCancelled(array $metadata = []): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'cancelled_at' => now(),
        ]);
    }

    public static function generateReceiptNumber(): string
    {
        return 'RCT-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
