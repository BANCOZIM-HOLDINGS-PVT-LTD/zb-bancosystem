// Application State Types
export interface ApplicationState {
    id?: number;
    session_id: string;
    channel: ApplicationChannel;
    user_identifier: string;
    current_step: ApplicationStep;
    form_data: FormData;
    metadata?: ApplicationMetadata;
    expires_at?: string;
    reference_code?: string;
    reference_code_expires_at?: string;
    created_at?: string;
    updated_at?: string;
}

export type ApplicationChannel = 'web' | 'whatsapp' | 'ussd' | 'mobile_app';

export type ApplicationStep = 
    | 'language' 
    | 'intent' 
    | 'employer' 
    | 'product' 
    | 'account' 
    | 'summary' 
    | 'form' 
    | 'documents' 
    | 'completed' 
    | 'in_review' 
    | 'approved' 
    | 'rejected' 
    | 'pending_documents' 
    | 'processing';

// Form Data Types
export interface FormData {
    language?: Language;
    intent?: Intent;
    employer?: string;
    employerCategory?: string;
    hasAccount?: boolean;
    amount?: number;
    creditTerm?: number;
    interestRate?: string;
    formId?: string;
    formResponses?: FormResponses;
    selectedBusiness?: BusinessProduct;
    selectedScale?: BusinessScale;
    category?: string;
    subcategory?: string;
    business?: string;
    scale?: string;
    finalPrice?: number;
    documents?: DocumentData;
    completedSteps?: string[];
    platform?: string;
    status?: ApplicationStatus;
    statusUpdatedAt?: string;
    statusHistory?: StatusHistoryEntry[];
}

export type Language = 'en' | 'sn' | 'nd';
export type Intent = 'loan' | 'account';
export type ApplicationStatus = 'draft' | 'pending' | 'in_review' | 'approved' | 'rejected' | 'completed';

// Form Responses Types
export interface FormResponses {
    // Personal Information
    firstName?: string;
    lastName?: string;
    surname?: string;
    middleName?: string;
    dateOfBirth?: string;
    nationalIdNumber?: string;
    passportNumber?: string;
    passportExpiry?: string;
    maritalStatus?: MaritalStatus;
    gender?: Gender;
    
    // Contact Information
    emailAddress?: string;
    mobile?: string;
    telephoneRes?: string;
    residentialAddress?: string;
    postalAddress?: string;
    city?: string;
    province?: string;
    country?: string;
    
    // Employment Information
    employerName?: string;
    employerAddress?: string;
    employerContact?: string;
    jobTitle?: string;
    employmentStartDate?: string;
    employmentType?: EmploymentType;
    workStation?: string;
    
    // Financial Information
    grossMonthlySalary?: number;
    netSalary?: number;
    currentNetSalary?: number;
    otherIncome?: number;
    monthlyExpenses?: number;
    existingLoans?: number;
    
    // Loan Information
    loanAmount?: number;
    loanTenure?: number;
    loanPurpose?: string;
    purposeOfLoan?: string;
    creditFacilityType?: string;
    monthlyPayment?: number;
    
    // Business Information (for SME)
    businessName?: string;
    businessAddress?: string;
    businessPhone?: string;
    businessRegistrationNumber?: string;
    businessType?: string;
    businessDescription?: string;
    businessAnnualRevenue?: number;
    businessStartDate?: string;
    numberOfEmployees?: number;
    
    // Spouse Information
    spouseName?: string;
    spouseIdNumber?: string;
    spouseContact?: string;
    spouseEmployer?: string;
    
    // Next of Kin
    nextOfKinName?: string;
    nextOfKinRelationship?: string;
    nextOfKinContact?: string;
    nextOfKinAddress?: string;
    
    // Banking Information
    bankName?: string;
    accountNumber?: string;
    branchName?: string;
    accountType?: string;
    
    // Additional Fields
    pensionNumber?: string;
    socialSecurityNumber?: string;
    taxNumber?: string;
    
    [key: string]: any; // Allow additional dynamic fields
}

export type MaritalStatus = 'single' | 'married' | 'divorced' | 'widowed' | 'separated';
export type Gender = 'male' | 'female' | 'other' | 'prefer_not_to_say';
export type EmploymentType = 'permanent' | 'contract' | 'temporary' | 'self_employed' | 'unemployed';

// Business Product Types
export interface BusinessProduct {
    id: string;
    name: string;
    description: string;
    category: string;
    subcategory: string;
    basePrice: number;
    features: string[];
    requirements: string[];
}

export interface BusinessScale {
    id: string;
    name: string;
    description: string;
    multiplier: number;
    minAmount: number;
    maxAmount: number;
}

// Document Types
export interface DocumentData {
    selfie?: string;
    signature?: string;
    uploadedAt?: string;
    uploadedDocuments?: Record<DocumentType, UploadedDocument[]>;
}

export type DocumentType = 
    | 'national_id'
    | 'passport'
    | 'drivers_license'
    | 'proof_of_residence'
    | 'payslip'
    | 'bank_statement'
    | 'employment_letter'
    | 'business_registration'
    | 'tax_clearance'
    | 'financial_statements'
    | 'other';

export interface UploadedDocument {
    id?: string;
    name: string;
    path: string;
    type: string;
    size: number;
    uploadedAt: string;
    metadata?: DocumentMetadata;
}

export interface DocumentMetadata {
    originalName?: string;
    mimeType?: string;
    checksum?: string;
    validated?: boolean;
    validationErrors?: string[];
}

// Application Metadata
export interface ApplicationMetadata {
    ip_address?: string;
    user_agent?: string;
    browser?: string;
    device?: string;
    platform?: string;
    referrer?: string;
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    session_start?: string;
    last_activity?: string;
    page_views?: number;
    time_spent?: number;
    interactions?: InteractionEvent[];
}

export interface InteractionEvent {
    type: string;
    timestamp: string;
    data?: Record<string, any>;
}

// Status History
export interface StatusHistoryEntry {
    status: ApplicationStatus;
    timestamp: string;
    reason?: string;
    updatedBy?: string;
    notes?: string;
}

// API Response Types
export interface ApiResponse<T = any> {
    success: boolean;
    data?: T;
    message?: string;
    errors?: Record<string, string[]>;
}

export interface ValidationError {
    field: string;
    message: string;
    code?: string;
}

// Wizard Step Types
export interface WizardStep {
    id: string;
    name: string;
    title: string;
    description?: string;
    component: string;
    isCompleted: boolean;
    isActive: boolean;
    isAccessible: boolean;
    validationRules?: ValidationRule[];
    dependencies?: string[];
}

export interface ValidationRule {
    field: string;
    rules: string[];
    message?: string;
}

// Form Configuration Types
export interface FormConfig {
    id: string;
    name: string;
    version: string;
    steps: WizardStep[];
    validationSchema: Record<string, ValidationRule[]>;
    submitEndpoint: string;
    autoSave: boolean;
    autoSaveInterval: number;
}

// Error Types
export interface ApplicationError {
    code: string;
    message: string;
    field?: string;
    context?: Record<string, any>;
}

// PDF Generation Types
export interface PDFGenerationRequest {
    sessionId: string;
    options?: PDFGenerationOptions;
}

export interface PDFGenerationOptions {
    template?: string;
    includeDocuments?: boolean;
    watermark?: boolean;
    encryption?: boolean;
    metadata?: Record<string, any>;
}

export interface PDFGenerationResponse {
    success: boolean;
    pdfUrl?: string;
    filename?: string;
    size?: number;
    generatedAt?: string;
    error?: string;
}
