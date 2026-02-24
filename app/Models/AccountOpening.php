<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountOpening extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_code',
        'user_identifier',
        'form_data',
        'status',
        'zb_account_number',
        'loan_eligible',
        'approved_at',
        'loan_eligible_at',
        'rejection_reason',
        'selected_product',
        'application_state_id',
        'referred_at',
        'referred_to_branch',
    ];

    protected $casts = [
        'form_data' => 'array',
        'selected_product' => 'array',
        'loan_eligible' => 'boolean',
        'approved_at' => 'datetime',
        'loan_eligible_at' => 'datetime',
        'referred_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REFERRED = 'referred';
    const STATUS_ACCOUNT_OPENED = 'account_opened';
    const STATUS_LOAN_ELIGIBLE = 'loan_eligible';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the linked loan application (if any)
     */
    public function applicationState(): BelongsTo
    {
        return $this->belongsTo(ApplicationState::class);
    }

    /**
     * Scope for pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for opened accounts
     */
    public function scopeAccountOpened($query)
    {
        return $query->where('status', self::STATUS_ACCOUNT_OPENED);
    }

    /**
     * Scope for loan eligible
     */
    public function scopeLoanEligible($query)
    {
        return $query->where('loan_eligible', true);
    }

    /**
     * Get applicant name from form data
     */
    public function getApplicantNameAttribute(): string
    {
        $firstName = $this->form_data['formResponses']['firstName'] ?? '';
        $lastName = $this->form_data['formResponses']['surname'] ?? $this->form_data['formResponses']['lastName'] ?? '';
        return trim("{$firstName} {$lastName}") ?: 'N/A';
    }

    /**
     * Get phone number from form data
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->form_data['formResponses']['mobile'] ?? $this->form_data['formResponses']['phoneNumber'] ?? null;
    }

    /**
     * Get national ID from form data
     */
    public function getNationalIdAttribute(): ?string
    {
        return $this->form_data['formResponses']['nationalIdNumber'] ?? null;
    }

    /**
     * Get branch/service centre from form data
     */
    public function getBranchAttribute(): ?string
    {
        return $this->form_data['formResponses']['serviceCenter'] ?? null;
    }

    /**
     * Mark account as opened
     */
    public function markAsOpened(string $accountNumber): void
    {
        $this->update([
            'status' => self::STATUS_ACCOUNT_OPENED,
            'zb_account_number' => $accountNumber,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as referred to branch
     */
    public function markAsReferred(string $branch): void
    {
        $this->update([
            'status' => self::STATUS_REFERRED,
            'referred_to_branch' => $branch,
            'referred_at' => now(),
        ]);
    }

    /**
     * Approve for loan credibility
     */
    public function approveForLoan(): void
    {
        $this->update([
            'status' => self::STATUS_LOAN_ELIGIBLE,
            'loan_eligible' => true,
            'loan_eligible_at' => now(),
        ]);
    }

    /**
     * Reject application
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }
}
