<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkSMSCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'bulk_sms_campaigns';

    protected $fillable = [
        'name',
        'type',
        'message_template',
        'recipient_filters',
        'scheduled_at',
        'sent_at',
        'status',
        'recipients_count',
        'sent_count',
        'failed_count',
        'recipient_list',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'recipient_filters' => 'array',
        'recipient_list' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    const TYPE_BIRTHDAY = 'birthday';
    const TYPE_HOLIDAY = 'holiday';
    const TYPE_INSTALLMENT_DUE = 'installment_due';
    const TYPE_INCOMPLETE_APPLICATION = 'incomplete_application';
    const TYPE_CUSTOM = 'custom';

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    public static function getTypes(): array
    {
        return [
            self::TYPE_BIRTHDAY => '🎂 Happy Birthday',
            self::TYPE_HOLIDAY => '🎉 National Holiday',
            self::TYPE_INSTALLMENT_DUE => '💳 Installments Due',
            self::TYPE_INCOMPLETE_APPLICATION => '📝 Incomplete Applications',
            self::TYPE_CUSTOM => '✉️ Custom Message',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_SENDING => 'Sending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get default message template based on type
     */
    public static function getDefaultTemplate(string $type): string
    {
        return match($type) {
            self::TYPE_BIRTHDAY => "🎂 Happy Birthday {name}! Wishing you a wonderful day from your friends at BancoZim. May this year bring you prosperity!",
            self::TYPE_HOLIDAY => "🎉 Happy {holiday}! BancoZim wishes you and your loved ones joy and celebration. Enjoy the holiday!",
            self::TYPE_INSTALLMENT_DUE => "💳 Reminder: Your BancoZim installment of {amount} is due on {date}. Reference: {reference}. Contact us if you have any questions.",
            self::TYPE_INCOMPLETE_APPLICATION => "👋 Hi {name}! Your BancoZim application is waiting for you. Complete it now at bancosystem.fly.dev to get your product delivered!",
            default => "",
        };
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending campaigns
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    /**
     * Check if campaign can be sent
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SCHEDULED => 'warning',
            self::STATUS_SENDING => 'info',
            self::STATUS_SENT => 'success',
            self::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }
}
