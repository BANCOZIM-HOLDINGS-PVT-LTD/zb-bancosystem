<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationState extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'session_id',
        'channel',
        'user_identifier',
        'current_step',
        'form_data',
        'metadata',
        'expires_at',
        'reference_code',
        'reference_code_expires_at',
        'agent_id',
        'exempt_from_auto_deletion',
        'deposit_amount',
        'deposit_paid',
        'deposit_paid_at',
        'deposit_transaction_id',
        'deposit_payment_method',
        'last_activity',
        'is_archived',
        'check_type',
        'check_status',
        'check_result',
    ];

    protected $casts = [
        'form_data' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'reference_code_expires_at' => 'datetime',
        'exempt_from_auto_deletion' => 'boolean',
        'deposit_paid' => 'boolean',
        'deposit_paid_at' => 'datetime',
        'last_activity' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function transitions(): HasMany
    {
        return $this->hasMany(StateTransition::class, 'state_id');
    }

    /**
     * Get the agent who referred this application
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the delivery tracking for this application
     */
    public function delivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeliveryTracking::class);
    }

    /**
     * Get commissions for this application
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'application_id');
    }
    /**
     * Get the formatted application number
     */
    public function getApplicationNumberAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('Y') : date('Y');
        $id = str_pad($this->id, 6, '0', STR_PAD_LEFT);
        
        $prefix = 'ZB';
        $formData = $this->form_data ?? [];
        
        // 1. Check for SSB / Government (SSBB)
        if ($this->isSSBApplication($formData)) {
            $prefix = 'SSBB';
        } 
        // 2. Check for Account Holders (ZBAH)
        elseif ($this->isAccountHolderApplication($formData)) {
            $prefix = 'ZBAH';
        }
        // 3. Check for Government Pensioners (GOZP)
        elseif ($this->isPensionerApplication($formData)) {
            $prefix = 'GOZP';
        }
        // 4. Check for RDC (Rural District Council) (AMRC)
        elseif ($this->isRDCApplication($formData)) {
            $prefix = 'AMRC';
        }
        // 5. Check for SMEs (MASE)
        elseif ($this->isSMEApplication($formData)) {
            $prefix = 'MASE';
        }
        
        return "{$prefix}{$year}{$id}";
    }

    private function isSSBApplication(array $formData): bool
    {
        $employer = strtolower($formData['employer'] ?? '');
        $employerType = $formData['formResponses']['employerType'] ?? '';
        
        if ($employer === 'government-ssb' || 
            str_contains($employer, 'civil service') || 
            str_contains($employer, 'ssb') ||
            str_contains($employer, 'government')
        ) {
            return true;
        }
        
        if (is_array($employerType) && isset($employerType['government'])) {
            return true;
        }
        
        return $employerType === 'government';
    }

    private function isAccountHolderApplication(array $formData): bool
    {
        return ($formData['hasAccount'] ?? false) === true;
    }

    private function isPensionerApplication(array $formData): bool
    {
        $employmentStatus = $formData['formResponses']['employmentStatus'] ?? '';
        $employer = strtolower($formData['employer'] ?? '');
        
        return $employmentStatus === 'pensioner' || str_contains($employer, 'pension');
    }

    private function isRDCApplication(array $formData): bool
    {
        $employer = strtolower($formData['employer'] ?? '');
        return str_contains($employer, 'rdc') || str_contains($employer, 'rural district council');
    }

    private function isSMEApplication(array $formData): bool
    {
        $formId = $formData['formId'] ?? '';
        return str_contains($formId, 'sme') || str_contains($formId, 'business');
    }
}
