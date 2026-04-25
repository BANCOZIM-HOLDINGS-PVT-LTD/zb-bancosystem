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
        'qupa_admin_id',
        'assigned_branch_id',
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
        'status',
        'approved_at',
        'payment_type',
        'application_type',
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
        'approved_at' => 'datetime',
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
     * Get the Qupa Admin officer who handled this application
     */
    public function qupaAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qupa_admin_id');
    }

    /**
     * Get the branch this application is assigned to
     */
    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
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

        if ($this->isCashOrder()) {
            return "CASH{$year}{$id}";
        }

        $type = $this->getApplicationType();
        $prefix = match ($type) {
            'ssb' => 'SSB',
            'account_holder' => 'ZBAH',
            'pensioner' => 'PEN',
            'rdc' => 'RDC',
            'sme' => 'SME',
            default => 'ZB',
        };

        return "{$prefix}{$year}{$id}";
    }

    public function isCashOrder(): bool
    {
        return $this->payment_type === 'cash';
    }

    public function isSSBApplication(?array $formData = null): bool
    {
        $formData = $formData ?? $this->form_data ?? [];
        $employer = strtolower($formData['employer'] ?? '');
        $employerType = $formData['formResponses']['employerType'] ?? '';
        $formType = $formData['formType'] ?? '';

        // Explicit SSB markers
        if ($employer === 'government-ssb' || $formType === 'ssb') {
            return true;
        }

        // Check if explicitly government but NOT non-ssb
        if (($employer === 'government' || $employerType === 'government') && $employer !== 'government-non-ssb') {
             // In many cases 'government' implies SSB in this system, 
             // but we should be careful if it might be ZBAH.
             // Usually ZBAH has 'hasAccount' set to true.
             if ($this->isAccountHolderApplication($formData)) {
                 return false;
             }
             return true;
        }
        
        return false;
    }

    public function isAccountHolderApplication(?array $formData = null): bool
    {
        $formData = $formData ?? $this->form_data ?? [];
        return ($formData['hasAccount'] ?? false) === true || ($formData['formType'] ?? '') === 'account_holder_loan_application';
    }

    public function isPensionerApplication(array $formData = null): bool
    {
        $formData = $formData ?? $this->form_data ?? [];
        $employmentStatus = $formData['formResponses']['employmentStatus'] ?? '';
        $employer = strtolower($formData['employer'] ?? '');

        return $employmentStatus === 'pensioner' || str_contains($employer, 'pension');
    }

    public function isRDCApplication(array $formData = null): bool
    {
        $formData = $formData ?? $this->form_data ?? [];
        $employer = strtolower($formData['employer'] ?? '');
        return str_contains($employer, 'rdc') || str_contains($employer, 'rural district council');
    }

    public function isSMEApplication(array $formData = null): bool
    {
        $formData = $formData ?? $this->form_data ?? [];
        $formId = $formData['formId'] ?? '';
        $intent = $formData['intent'] ?? '';
        $formType = $formData['formType'] ?? '';
        $companyType = $formData['companyType'] ?? '';

        // Check intent (primary SME marker from welcome page)
        if ($intent === 'smeBiz') {
            return true;
        }

        // Check formType (set by ApplicationSummary)
        if ($formType === 'sme_business') {
            return true;
        }

        // Check if company type was selected (from CompanyTypeSelection step)
        if (!empty($companyType)) {
            return true;
        }

        // Fallback: legacy formId/employer checks
        $employer = strtolower($formData['employer'] ?? '');
        return $employer === 'sme-business'
            || str_contains($formId, 'sme')
            || str_contains($formId, 'business');
    }

    /**
     * Get the canonical application type string.
     * Uses form_data['formType'] as primary discriminator with heuristic fallback.
     */
    public function getApplicationType(): string
    {
        $formData = $this->form_data ?? [];
        $formType = $formData['formType'] ?? '';

        return match ($formType) {
            'ssb' => 'ssb',
            'account_holder_loan_application' => 'account_holder',
            'zb_account_opening' => 'zb_account_opening',
            'pensioner' => 'pensioner',
            'rdc' => 'rdc',
            'sme_business' => 'sme',
            default => $this->detectApplicationTypeFallback($formData),
        };
    }

    private function detectApplicationTypeFallback(array $formData): string
    {
        if ($this->isSSBApplication($formData)) return 'ssb';
        if ($this->isAccountHolderApplication($formData)) return 'account_holder';
        if ($this->isPensionerApplication($formData)) return 'pensioner';
        if ($this->isRDCApplication($formData)) return 'rdc';
        if ($this->isSMEApplication($formData)) return 'sme';
        return 'zb_account_opening';
    }
}
