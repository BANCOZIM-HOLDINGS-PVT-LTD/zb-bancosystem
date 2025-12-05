<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentApplication extends Model
{
    protected $fillable = [
        'application_number',
        'whatsapp_number',
        'session_id',
        'province',
        'first_name',
        'surname',
        'id_number',
        'gender',
        'age_range',
        'voice_number',
        'whatsapp_contact',
        'ecocash_number',
        'id_front_url',
        'id_back_url',
        'status',
        'rejection_reason',
        'agent_code',
        'referral_link',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Boot method to auto-generate application number
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($application) {
            if (empty($application->application_number)) {
                $lastId = self::max('id') ?? 0;
                $application->application_number = 'APP-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
    
    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->surname);
    }

    /**
     * Get the application status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Check if application is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if application is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Approve the application and generate agent code
     */
    public function approve(): void
    {
        $this->status = 'approved';
        $this->agent_code = $this->generateAgentCode();
        $this->referral_link = config('app.url') . '/apply?ref=' . $this->agent_code;
        $this->save();
    }

    /**
     * Reject the application
     */
    public function reject(): void
    {
        $this->status = 'rejected';
        $this->save();
    }

    /**
     * Generate unique agent code
     */
    private function generateAgentCode(): string
    {
        do {
            $code = 'AG' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('agent_code', $code)->exists());

        return $code;
    }
}
