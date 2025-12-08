<?php

namespace App\Enums;

/**
 * Conversation states for WhatsApp bot
 */
enum ConversationState: string
{
    // Initial States
    case NEW = 'new';
    case MAIN_MENU = 'microbiz_main_menu';
    
    // Agent Application Flow
    case AGENT_AGE_CHECK = 'agent_age_check';
    case AGENT_UNDERAGE = 'agent_underage';
    case AGENT_PROVINCE = 'agent_province';
    case AGENT_NAME = 'agent_name';
    case AGENT_SURNAME = 'agent_surname';
    case AGENT_GENDER = 'agent_gender';
    case AGENT_AGE_RANGE = 'agent_age_range';
    case AGENT_VOICE_NUMBER = 'agent_voice_number';
    case AGENT_WHATSAPP_NUMBER = 'agent_whatsapp_number';
    case AGENT_ECOCASH_NUMBER = 'agent_ecocash_number';
    case AGENT_ID_UPLOAD = 'agent_id_upload';
    case AGENT_ID_BACK_UPLOAD = 'agent_id_back_upload';
    
    // Credit Eligibility Flow
    case EMPLOYMENT_CHECK = 'employment_check';
    case FORMAL_EMPLOYMENT_CHECK = 'formal_employment_check';
    case UNEMPLOYMENT_CATEGORY = 'unemployment_category';
    case EMPLOYER_CATEGORY = 'employer_category';
    case SME_SALARY_METHOD = 'sme_salary_method';
    case BENEFICIARY_QUESTION = 'beneficiary_question';
    case MONITORING_QUESTION = 'monitoring_question';
    case TRAINING_QUESTION = 'training_question';
    case AGENT_OFFER_AFTER_REJECTION = 'agent_offer_after_rejection';
    
    // Terminal States
    case REDIRECT_CASH = 'redirect_cash';
    case REDIRECT_CREDIT = 'redirect_credit';
    case COMPLETED = 'completed';
    
    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::REDIRECT_CASH,
            self::REDIRECT_CREDIT,
            self::AGENT_UNDERAGE,
        ]);
    }
    
    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::NEW => 'New Conversation',
            self::MAIN_MENU => 'Main Menu',
            self::AGENT_AGE_CHECK => 'Agent Application - Age Check',
            self::AGENT_PROVINCE => 'Agent Application - Province Selection',
            self::AGENT_NAME => 'Agent Application - First Name',
            self::AGENT_SURNAME => 'Agent Application - Surname',
            self::AGENT_GENDER => 'Agent Application - Gender',
            self::AGENT_AGE_RANGE => 'Agent Application - Age Range',
            self::AGENT_VOICE_NUMBER => 'Agent Application - Voice Number',
            self::AGENT_WHATSAPP_NUMBER => 'Agent Application - WhatsApp Number',
            self::AGENT_ECOCASH_NUMBER => 'Agent Application - EcoCash Number',
            self::AGENT_ID_UPLOAD => 'Agent Application - ID Front Upload',
            self::AGENT_ID_BACK_UPLOAD => 'Agent Application - ID Back Upload',
            self::EMPLOYMENT_CHECK => 'Credit Check - Employment Status',
            self::FORMAL_EMPLOYMENT_CHECK => 'Credit Check - Formal Employment',
            self::UNEMPLOYMENT_CATEGORY => 'Credit Check - Unemployment Category',
            self::EMPLOYER_CATEGORY => 'Credit Check - Employer Category',
            self::SME_SALARY_METHOD => 'Credit Check - SME Salary Method',
            self::BENEFICIARY_QUESTION => 'Credit Check - Beneficiary Question',
            self::MONITORING_QUESTION => 'Credit Check - Monitoring Question',
            self::TRAINING_QUESTION => 'Credit Check - Training Question',
            self::AGENT_OFFER_AFTER_REJECTION => 'Agent Offer After Rejection',
            self::COMPLETED => 'Completed',
            default => 'Unknown State',
        };
    }
}
