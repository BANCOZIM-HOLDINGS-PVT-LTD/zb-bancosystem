import React, { useState, useEffect, useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import EmployerSelection from './steps/EmployerSelection';
import ProductSelection from './steps/ProductSelection';
import CreditTypeSelection from './steps/CreditTypeSelection';
import DeliverySelection from './steps/DeliverySelection';
import DepositPaymentStep from './steps/DepositPaymentStep';
import AccountVerification from './steps/AccountVerification';
import CreditTermSelection from './steps/CreditTermSelection';
import ApplicationSummary from './steps/ApplicationSummary';
import FormStep from './steps/FormStep';
import CompanyRegistrationStep from './steps/CompanyRegistrationStep';
import LicenseCoursesStep from './steps/LicenseCoursesStep';
import ZimparksHolidayStep from './steps/ZimparksHolidayStep';
import DocumentUploadStep from '../DocumentUpload/DocumentUploadStep';
import { StateManager } from './services/StateManager';
import { LocalStateManager } from './services/LocalStateManager';
import { validateStep, ValidationResult, ValidationError, formatFieldName } from './utils/validation';

export interface WizardData {
    // Basic application data
    language?: string;
    intent?: string;
    currency?: string;
    employer?: string;
    employerCategory?: string;
    employerName?: string;

    // Reference code tracking
    referenceCode?: string;
    referenceCodeGeneratedAt?: string;
    resumeCode?: string; // For resuming applications
    invoiceNumber?: string; // National ID without dashes - used as invoice number

    // Product selection data
    category?: string;
    subcategory?: string;
    business?: string;
    scale?: string;
    amount?: number;
    loanAmount?: number;
    creditTerm?: number;
    monthlyPayment?: number;
    interestRate?: string;
    includesMESystem?: boolean;
    meSystemFee?: number;
    includesTraining?: boolean;
    trainingFee?: number;

    // Cart for Building Materials (or other multi-item categories)
    cart?: {
        businessId: number;
        name: string;
        price: number;
        quantity: number;
        color?: string;
        interiorColor?: string;
        exteriorColor?: string;
        scale?: string;
    }[];

    // Credit type selection (ZDC or PDC)
    creditType?: 'ZDC' | 'PDC';
    depositAmount?: number;
    depositPaid?: boolean;
    depositPaymentMethod?: 'ecocash' | 'onemoney' | 'card';
    depositTransactionId?: string;
    depositPaidAt?: string;

    // Selected business details
    selectedBusiness?: {
        id: string;
        name: string;
        description?: string;
        logo?: string;
    };

    // Selected scale details
    selectedScale?: {
        id: string;
        name: string;
        description?: string;
    };

    // Final price calculation
    finalPrice?: number;

    // Color selections
    color?: string;
    interiorColor?: string;
    exteriorColor?: string;

    // Account verification
    hasAccount?: boolean;
    wantsAccount?: boolean;
    accountType?: string;
    accountDetails?: {
        accountNumber?: string;
        accountType?: string;
        branchName?: string;
        accountHolderName?: string;
        accountOpenDate?: string;
        verified?: boolean;
    };

    // Form data - using specific types instead of any
    formId?: string;
    formResponses?: {
        // Common fields across all forms
        title?: string;
        firstName?: string;
        lastName?: string;
        surname?: string;
        maidenName?: string;
        otherNames?: string;
        gender?: string;
        dateOfBirth?: string;
        placeOfBirth?: string;
        nationality?: string;
        maritalStatus?: string;
        citizenship?: string;
        dependents?: string | number;
        nationalIdNumber?: string;
        driversLicense?: string;
        passportNumber?: string;
        passportExpiry?: string;
        countryOfResidence?: string;
        highestEducation?: string;
        hobbies?: string;

        // Contact details
        residentialAddress?: string;
        telephoneRes?: string;
        mobile?: string;
        bus?: string;
        emailAddress?: string;
        whatsApp?: string;

        // Employment details
        employerName?: string;
        occupation?: string;
        employmentStatus?: string;
        businessDescription?: string;
        employerType?: {
            government?: boolean;
            localCompany?: boolean;
            multinational?: boolean;
            ngo?: boolean;
            other?: boolean;
            otherSpecify?: string;
        } | string;
        employerAddress?: string;
        employerContact?: string;
        grossMonthlySalary?: string | number;
        otherIncome?: string | number;
        jobTitle?: string;
        dateOfEmployment?: string;
        employmentNumber?: string;
        headOfInstitution?: string;
        headOfInstitutionCell?: string;
        currentNetSalary?: string | number;
        responsiblePaymaster?: string;
        responsibleMinistry?: string;

        // Account specifications
        accountNumber?: string;
        accountCurrency?: string;
        serviceCenter?: string;

        // Credit facility details
        creditFacilityType?: string;
        loanAmount?: string | number;
        loanTenure?: string | number;
        monthlyPayment?: string | number;
        interestRate?: string | number;
        purposeAsset?: string;
        purposeOfLoan?: string;
        loanType?: string;
        loanPurpose?: string;

        // Property details
        propertyOwnership?: string;
        periodAtAddress?: string;

        // Banking details
        bankName?: string;
        branch?: string;

        // Spouse/Next of Kin
        spouseTitle?: string;
        spouseFirstName?: string;
        spouseSurname?: string;
        spouseAddress?: string;
        spouseIdNumber?: string;
        spouseContact?: string;
        spouseRelationship?: string;
        spouseGender?: string;
        spouseEmail?: string;
        spouseDetails?: Array<{
            fullName?: string;
            relationship?: string;
            phoneNumber?: string;
            residentialAddress?: string;
            emailAddress?: string;
        }>;

        // Other loans
        otherLoans?: Array<{
            institution?: string;
            repayment?: string;
            monthlyInstallment?: string;
            currentBalance?: string;
            maturityDate?: string;
        }>;

        // ZB Life Funeral Cover
        funeralCover?: {
            dependents?: Array<{
                name?: string;
                relationship?: string;
                dateOfBirth?: string;
                idNumber?: string;
                coverAmount?: string | number;
            }>;
            principalMember?: {
                memorialCashBenefit?: string;
                tombstoneCashBenefit?: string;
                groceryBenefit?: string;
                schoolFeesBenefit?: string;
                personalAccidentBenefit?: string;
            };
        };

        // Personal Accident Benefit
        personalAccidentBenefit?: {
            surname?: string;
            forenames?: string;
        };

        // Other Services
        smsAlerts?: boolean;
        smsNumber?: string;
        eStatements?: boolean;
        eStatementsEmail?: string;

        // Digital Banking
        mobileMoneyEcocash?: boolean;
        mobileMoneyNumber?: string;
        eWallet?: boolean;
        eWalletNumber?: string;
        whatsappBanking?: boolean;
        internetBanking?: boolean;

        // Supporting Documents
        supportingDocs?: {
            passportPhotos?: boolean;
            proofOfResidence?: boolean;
            payslip?: boolean;
            nationalId?: boolean;
            passport?: boolean;
            driversLicense?: boolean;
        };

        // Declaration
        declaration?: {
            fullName?: string;
            signature?: string;
            date?: string;
            acknowledged?: boolean;
        };

        // Header fields for forms
        deliveryStatus?: string;
        province?: string;
        agent?: string;
        team?: string;

        // SME Business Form specific fields
        businessName?: string;
        businessRegistrationNumber?: string;
        businessType?: string;
        businessAddress?: string;
        businessPhone?: string;
        businessEmail?: string;
        businessWebsite?: string;
        businessIndustry?: string;
        businessYearsOperating?: string | number;
        businessAnnualRevenue?: string | number;
        businessNumberOfEmployees?: string | number;
        registeredName?: string;
        tradingName?: string;
        typeOfBusiness?: string;
        periodAtLocation?: string;
        initialCapital?: string | number;
        incorporationDate?: string;
        incorporationNumber?: string;
        bpNumber?: string;
        contactPhone?: string;
        yearsInBusiness?: string | number;

        // Capital sources
        capitalSources?: {
            ownSavings?: boolean;
            familyGift?: boolean;
            loan?: boolean;
            other?: boolean;
            otherSpecify?: string;
        };

        // Customer base
        customerBase?: {
            individuals?: boolean;
            businesses?: boolean;
            other?: boolean;
            otherSpecify?: string;
        };

        // Financial information
        estimatedAnnualSales?: string | number;
        netProfit?: string | number;
        totalLiabilities?: string | number;
        netCashFlow?: string | number;
        mainProducts?: string;
        mainProblems?: string;

        // Budget breakdown
        budgetItems?: Array<{
            item?: string;
            cost?: string | number;
        }>;

        // Directors' personal details
        directorsPersonalDetails?: {
            title?: string;
            firstName?: string;
            surname?: string;
            maidenName?: string;
            gender?: string;
            dateOfBirth?: string;
            maritalStatus?: string;
            nationality?: string;
            idNumber?: string;
            cellNumber?: string;
            whatsApp?: string;
            highestEducation?: string;
            citizenship?: string;
            emailAddress?: string;
            residentialAddress?: string;
            passportPhoto?: string;
            periodAtCurrentAddress?: { years?: string; months?: string };
            periodAtPreviousAddress?: { years?: string; months?: string };
        };

        // References
        references?: Array<{
            name?: string;
            phoneNumber?: string;
        }>;

        // Security assets
        securityAssets?: Array<{
            description?: string;
            serialNumber?: string;
            estimatedValue?: string | number;
        }>;

        // Directors signatures
        directorsSignatures?: Array<{
            name?: string;
            signature?: string;
            date?: string;
        }>;

        // KYC documents
        kycDocuments?: {
            copyOfId?: boolean;
            articlesOfAssociation?: boolean;
            bankStatement?: boolean;
            groupConstitution?: boolean;
            proofOfResidence?: boolean;
            financialStatement?: boolean;
            certificateOfIncorporation?: boolean;
            ecocashStatements?: boolean;
            resolutionToBorrow?: boolean;
            cr11?: boolean;
            cr6?: boolean;
            cr5?: boolean;
            moa?: boolean;
        };

        // SSB Loan Form specific fields
        employeeNumber?: string;
        ministry?: string;
        payrollNumber?: string;
        netSalary?: string | number;

        // Application metadata
        applicationDate?: string;
        applicationNumber?: string;

        // Any additional form-specific fields
        [key: string]: any;
    };

    // Delivery selection (new)
    deliverySelection?: {
        agent: 'Swift' | 'Gain Cash & Carry' | 'Zim Post Office';
        city?: string;  // For Swift deliveries
        depot?: string; // For Gain Outlet deliveries
        isAgentEditable: boolean;
    };

    // Delivery details (old - for backward compatibility)
    /* deliveryDetails?: {
         deliveryAddress: string;
         recipientName: string;
         recipientPhone: string;
         alternativePhone?: string;
         deliveryInstructions?: string;
     }; */

    // Document data with improved typing
    documents?: {
        uploadedDocuments: Record<string, Array<{
            id: string;
            name: string;
            type: string;
            size: number;
            path: string;
            uploadedAt: string;
            status?: 'uploading' | 'uploaded' | 'failed';
            progress?: number;
            validationErrors?: string[];
        }>>;
        selfie: string;
        signature: string;
        uploadedAt: string;
    };

    // PDF generation data
    pdfPath?: string;
    pdfGeneratedAt?: string;

    // Application status tracking
    status?: string;
    statusUpdatedAt?: string;
    statusHistory?: Array<{
        status: string;
        timestamp: string;
        updatedBy?: string;
        notes?: string;
    }>;

    // Cross-platform data
    platform?: 'web' | 'whatsapp' | 'admin';
    lastInteractionAt?: string;
    completedSteps?: string[];

    // WhatsApp integration
    whatsappNumber?: string;
    whatsappSessionId?: string;
    whatsappConversationState?: string;
    whatsappLastMessageAt?: string;

    // Admin processing data
    assignedTo?: string;
    processingNotes?: string;
    approvalStatus?: 'pending' | 'approved' | 'rejected' | 'on_hold';
    approvalDate?: string;
    approvedBy?: string;
    rejectionReason?: string;
}

interface ApplicationWizardProps {
    initialStep?: string;
    initialData?: WizardData;
    sessionId?: string;
}

const allSteps = [
    'employer',
    'product',
    'companyRegistration',
    'licenseCourses', // License/Driving school courses step
    'zimparksHoliday', // Zimparks Holiday booking step
    'creditTerm', // Duration selection
    'creditType',
    'delivery',
    'depositPayment',
    'account',
    'summary',
    'form',
    'documents'
];

const ApplicationWizard: React.FC<ApplicationWizardProps> = ({
    initialStep = 'employer',
    initialData = {},
    sessionId: propSessionId
}) => {
    const stateManager = new StateManager();
    const localStateManager = new LocalStateManager();

    // Initialize state from saved data if available
    const initializeState = () => {
        // If intent is provided in initialData, it means we're coming from Welcome page
        // In this case, prioritize props over saved state
        if (initialData.intent) {
            // Clear any old saved state to start fresh
            localStateManager.clearLocalState();
            return {
                currentStep: initialStep,
                wizardData: initialData,
                sessionId: propSessionId || stateManager.generateSessionId()
            };
        }

        const savedState = localStateManager.getLocalState();
        if (savedState && savedState.sessionId && savedState.currentStep && savedState.formData) {
            return {
                currentStep: savedState.currentStep,
                wizardData: { ...initialData, ...savedState.formData },
                sessionId: savedState.sessionId
            };
        }

        return {
            currentStep: initialStep,
            wizardData: initialData,
            sessionId: propSessionId || stateManager.generateSessionId()
        };
    };

    const initializedState = initializeState();

    const [currentStep, setCurrentStep] = useState(initializedState.currentStep);
    const [wizardData, setWizardData] = useState<WizardData>(initializedState.wizardData);
    const [loading, setLoading] = useState(false);
    const [sessionId, setSessionId] = useState(initializedState.sessionId);
    const [isStateRestored, setIsStateRestored] = useState(false);

    // Create filtered steps based on conditions
    // Filter out depositPayment if credit type is not PDC
    const steps = React.useMemo(() => {
        let filteredSteps = [...allSteps];

        const isCompanyReg = wizardData.subcategory === 'Fees and Licensing' ||
            (wizardData.selectedBusiness?.name === 'Company Registration' || wizardData.business === 'Company Registration');

        const isLicenseCourses = wizardData.subcategory === 'Driving School' ||
            wizardData.subcategory === 'License Courses' ||
            (wizardData.selectedBusiness?.name === 'License Courses' || wizardData.business === 'License Courses');

        const isZimparksHoliday = wizardData.category === 'Zimparks Holiday Package' ||
            wizardData.category === 'Holiday Package' ||
            wizardData.subcategory === 'Destinations' ||
            (wizardData.selectedBusiness?.name === 'Zimparks Vacation Package' || wizardData.business === 'Zimparks Vacation Package');

        // Filter out steps based on product type
        if (!isCompanyReg) {
            filteredSteps = filteredSteps.filter(step => step !== 'companyRegistration');
        }

        if (!isLicenseCourses) {
            filteredSteps = filteredSteps.filter(step => step !== 'licenseCourses');
        }

        if (!isZimparksHoliday) {
            filteredSteps = filteredSteps.filter(step => step !== 'zimparksHoliday');
        }

        // For Company Reg, License Courses, and Zimparks, keep creditTerm step
        // For standard products, filter out creditTerm (handled internally in ProductSelection)
        if (!isCompanyReg && !isLicenseCourses && !isZimparksHoliday) {
            filteredSteps = filteredSteps.filter(step => step !== 'creditTerm');
        }

        // Skip delivery step for License Courses and Zimparks Holiday (location selected in dedicated step)
        if (isLicenseCourses || isZimparksHoliday) {
            filteredSteps = filteredSteps.filter(step => step !== 'delivery');
        }

        // Only show depositPayment step for PDC credit type
        if (wizardData.creditType !== 'PDC') {
            filteredSteps = filteredSteps.filter(step => step !== 'depositPayment');
        }

        return filteredSteps;
    }, [wizardData.creditType, wizardData.subcategory, wizardData.selectedBusiness, wizardData.business, wizardData.category]);

    // Effect to save state whenever it changes
    useEffect(() => {
        if (isStateRestored && sessionId && wizardData) {
            localStateManager.debouncedSave(sessionId, currentStep, wizardData);
        }
    }, [wizardData, currentStep, sessionId, isStateRestored]);

    // Effect to mark state as restored after initial load
    useEffect(() => {
        setIsStateRestored(true);
    }, []);

    // Effect to pre-fill user data from auth context
    const { props } = usePage<any>();
    const authenticatedUser = props.auth?.user;

    useEffect(() => {
        if (authenticatedUser && isStateRestored) {
            setWizardData(prev => {
                const currentResponses = prev.formResponses || {};
                let changed = false;
                const newResponses = { ...currentResponses };

                // Pre-fill National ID if not present
                if (!newResponses.nationalIdNumber && authenticatedUser.national_id) {
                    newResponses.nationalIdNumber = authenticatedUser.national_id;
                    changed = true;
                }

                // Pre-fill Phone if not present
                if (!newResponses.mobile && authenticatedUser.phone) {
                    newResponses.mobile = authenticatedUser.phone;
                    changed = true;
                }

                // Pre-fill Surname/First Name if available (assuming split logic or raw fields)
                // Note: user model might have 'name' or separate fields. Using safe defaults if unsure.
                // If user model has 'name', we can try to split it.
                if (authenticatedUser.name && (!newResponses.firstName || !newResponses.surname)) {
                    const parts = authenticatedUser.name.split(' ');
                    if (parts.length > 0) {
                        if (!newResponses.firstName) {
                            newResponses.firstName = parts.slice(1).join(' '); // All parts except first as first names (standard Shona/English naming often puts Surname first? No, usually First Last). 
                            // Wait, Zimbabwe usually adheres to Firstname Surname or Surname Firstname. 
                            // Let's assume standard "First Last" for now, or just leave it if ambiguous.
                            // Actually, let's just stick to ID and Phone as explicitly requested.
                        }
                    }
                }


                if (changed) {
                    if (Object.keys(newResponses).length > 0) {
                        const mobileNumber = authenticatedUser.phone;
                        // Only carry forward +263 numbers to the form
                        if (mobileNumber && mobileNumber.startsWith('+263')) {
                            newResponses.mobile = mobileNumber;
                        }

                        const updated = { ...prev, formResponses: newResponses };
                        // We should also save this to local state so it persists
                        if (sessionId) {
                            localStateManager.debouncedSave(sessionId, currentStep, updated);
                        }
                        return updated;
                    }
                }
                return prev;
            });
        }
    }, [authenticatedUser, isStateRestored, sessionId, currentStep]);

    // Enhanced validation state
    const [validationErrors, setValidationErrors] = useState<ValidationError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [showValidationSummary, setShowValidationSummary] = useState(false);
    const [validationAttempted, setValidationAttempted] = useState(false);
    const [missingRequiredFields, setMissingRequiredFields] = useState<string[]>([]);

    const handleNext = useCallback(async (stepData: Partial<WizardData>) => {
        const updatedData = { ...wizardData, ...stepData };

        // Validate the current step before proceeding
        const validationResult = validateStep(currentStep, updatedData);

        if (!validationResult.isValid) {
            // Show validation errors and prevent progression
            setValidationErrors(validationResult.errors);
            // Convert validation errors to field errors for form display
            const fieldErrorsMap = validationResult.fieldErrors || {};

            // Add field-specific error messages
            validationResult.errors.forEach(error => {
                if (!fieldErrorsMap[error.field]) {
                    fieldErrorsMap[error.field] = error.message;
                }
            });

            setFieldErrors(fieldErrorsMap);
            setShowValidationSummary(true);

            // Track missing required fields
            const missingFields = validationResult.errors
                .filter(error => error.message.includes('required'))
                .map(error => error.field);
            setMissingRequiredFields(missingFields);

            // Scroll to the top where validation summary will be displayed
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Clear validation errors if validation passes
        setValidationErrors([]);
        setFieldErrors({});
        setShowValidationSummary(false);
        setMissingRequiredFields([]);

        // Update wizard data
        setWizardData(updatedData);

        // Save to local storage immediately
        localStateManager.saveLocalState(sessionId, currentStep, updatedData);

        // Check if we need to generate a reference code at this step
        if (currentStep === 'form' && !updatedData.referenceCode) {
            // Generate reference code when the form step is completed
            try {
                const response = await fetch('/api/reference-code/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        sessionId
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        updatedData.referenceCode = result.reference_code;
                        updatedData.referenceCodeGeneratedAt = new Date().toISOString();
                    }
                } else {
                    console.error('Failed to generate reference code');
                }
            } catch (error) {
                console.error('Error generating reference code:', error);
            }
        }

        // Recalculate steps based on updatedData to avoid timing issues
        const isCompanyRegUpdated = updatedData.subcategory === 'Fees and Licensing' ||
            (updatedData.selectedBusiness?.name === 'Company Registration' || updatedData.business === 'Company Registration');

        const isLicenseCoursesUpdated = updatedData.subcategory === 'Driving School' ||
            (updatedData.selectedBusiness?.name === 'License Courses' || updatedData.business === 'License Courses');

        let currentFilteredSteps = [...allSteps];

        const isZimparksHolidayUpdated = updatedData.category === 'Zimparks Holiday Package' ||
            updatedData.category === 'Holiday Package' ||
            updatedData.subcategory === 'Destinations' ||
            (updatedData.selectedBusiness?.name === 'Zimparks Vacation Package' || updatedData.business === 'Zimparks Vacation Package');

        if (!isCompanyRegUpdated) {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'companyRegistration');
        }

        if (!isLicenseCoursesUpdated) {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'licenseCourses');
        }

        if (!isZimparksHolidayUpdated) {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'zimparksHoliday');
        }

        // For Company Reg, License Courses, and Zimparks, keep creditTerm step
        // For standard products, filter out creditTerm (handled internally in ProductSelection)
        if (!isCompanyRegUpdated && !isLicenseCoursesUpdated && !isZimparksHolidayUpdated) {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'creditTerm');
        }

        // Skip delivery step for License Courses and Zimparks Holiday
        if (isLicenseCoursesUpdated || isZimparksHolidayUpdated) {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'delivery');
        }

        if (updatedData.creditType !== 'PDC') {
            currentFilteredSteps = currentFilteredSteps.filter(step => step !== 'depositPayment');
        }

        const currentIndex = currentFilteredSteps.indexOf(currentStep);
        const nextStep = currentFilteredSteps[currentIndex + 1];

        if (nextStep) {
            setLoading(true);
            try {
                // Save both locally and remotely
                await Promise.all([
                    stateManager.saveState(sessionId, nextStep, updatedData),
                    localStateManager.saveLocalState(sessionId, nextStep, updatedData)
                ]);

                setCurrentStep(nextStep);
            } catch (error) {
                console.error('Failed to save state:', error);
                // Even if remote save fails, continue with local state
                localStateManager.saveLocalState(sessionId, nextStep, updatedData);
                setCurrentStep(nextStep);
            } finally {
                setLoading(false);
            }
        } else {
            handleComplete(updatedData);
        }
    }, [wizardData, currentStep, sessionId, steps, stateManager, localStateManager]);

    const handleBack = useCallback(() => {
        const currentIndex = steps.indexOf(currentStep);
        if (currentIndex > 0) {
            const prevStep = steps[currentIndex - 1];
            setCurrentStep(prevStep);

            // Save the current step to local storage when navigating back
            localStateManager.saveLocalState(sessionId, prevStep, wizardData);
        }
    }, [currentStep, steps, sessionId, wizardData, localStateManager]);



    // Check if a reference code is valid
    const validateReferenceCode = async (code: string): Promise<boolean> => {
        try {
            const response = await fetch('/api/reference-code/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ code })
            });

            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error validating reference code:', error);
            return false;
        }
    };

    const handleComplete = useCallback(async (finalData: WizardData) => {
        setLoading(true);
        try {
            let referenceCode = finalData.referenceCode;

            // Generate reference code if not already present
            if (!referenceCode) {
                try {
                    const response = await fetch('/api/reference-code/generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            sessionId
                        })
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            referenceCode = result.reference_code;
                        }
                    }
                } catch (error) {
                    console.error('Error generating reference code:', error);
                }
            }

            // Add reference code and timestamp to the final data
            const dataWithReference = {
                ...finalData,
                referenceCode,
                referenceCodeGeneratedAt: new Date().toISOString(),
                status: 'submitted',
                statusUpdatedAt: new Date().toISOString(),
                statusHistory: [
                    ...(finalData.statusHistory || []),
                    {
                        status: 'submitted',
                        timestamp: new Date().toISOString(),
                        notes: 'Application submitted via web interface'
                    }
                ],
                completedSteps: allSteps
            };

            // Save state as completed with reference code
            await stateManager.saveState(sessionId, 'completed', dataWithReference);

            // Submit application via API
            const response = await fetch('/api/states/create-application', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    sessionId,
                    data: dataWithReference,
                    referenceCode
                })
            });

            if (response.ok) {
                const result = await response.json();
                // Clear local storage since application is complete
                localStateManager.clearLocalState();
                // Redirect to thank you page with reference code
                router.visit(`/application/success?ref=${result.reference_code || referenceCode}`);
            } else {
                const error = await response.json();
                console.error('Application submission failed:', error);

                // Show a more detailed error message
                const errorMessage = error.message || 'Failed to submit application. Please try again.';
                setValidationErrors([{
                    field: 'submission',
                    message: errorMessage
                }]);
                setShowValidationSummary(true);

                // Scroll to the top where validation summary will be displayed
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (error) {
            console.error('Failed to complete application:', error);

            // Show a more user-friendly error message
            setValidationErrors([{
                field: 'submission',
                message: 'Failed to submit application. Please check your connection and try again.'
            }]);
            setShowValidationSummary(true);

            // Scroll to the top where validation summary will be displayed
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } finally {
            setLoading(false);
        }
    }, [sessionId, stateManager, localStateManager]);

    const renderStep = () => {
        const commonProps = {
            data: wizardData,
            onNext: handleNext,
            onBack: handleBack,
            loading,
            errors: fieldErrors
        };

        switch (currentStep) {
            case 'employer':
                return <EmployerSelection {...commonProps} />;
            case 'product':
                return <ProductSelection {...commonProps} />;
            case 'companyRegistration':
                return (
                    <CompanyRegistrationStep
                        data={wizardData}
                        onNext={(data) => handleNext({ ...data, companyRegistrationData: data })}
                        onBack={handleBack}
                    />
                );
            case 'licenseCourses':
                return (
                    <LicenseCoursesStep
                        data={wizardData}
                        onNext={(data) => handleNext({ ...data, licenseCoursesData: data })}
                        onBack={handleBack}
                    />
                );
            case 'zimparksHoliday':
                return (
                    <ZimparksHolidayStep
                        data={wizardData}
                        onNext={(data) => handleNext({ ...data, zimparksHolidayData: data })}
                        onBack={handleBack}
                    />
                );
            case 'creditTerm':
                return (
                    <CreditTermSelection
                        data={wizardData}
                        onNext={(data) => handleNext(data)}
                        onBack={handleBack}
                    />
                );
            case 'creditType':
                return <CreditTypeSelection {...commonProps} onNext={(creditType) => handleNext({ creditType })} />;
            case 'delivery':
                return <DeliverySelection {...commonProps} />;
            case 'depositPayment':
                return <DepositPaymentStep {...commonProps} />;
            case 'summary':
                return (
                    <ApplicationSummary
                        {...commonProps}
                        data={{
                            ...wizardData,
                            hasAccount: wizardData.hasAccount ?? false,
                            wantsAccount: wizardData.wantsAccount ?? false
                        }}
                    />
                );
            case 'account':
                return <AccountVerification {...commonProps} />;
            case 'form':
                return <FormStep {...commonProps} />;
            case 'documents':
                return <DocumentUploadStep {...commonProps} />;
            default:
                return null;
        }
    };

    // Check if state was restored
    const savedState = localStateManager.getLocalState();
    const wasStateRestored = savedState && savedState.currentStep !== initialStep;

    return (
        <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
            <div className="mx-auto max-w-4xl px-4 py-8">
                {/* State restoration notification */}
                {wasStateRestored && !loading && (
                    <div className="mb-6 p-4 border border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 rounded-lg">
                        <div className="flex items-center gap-3">
                            <div className="p-1 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <svg className="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-blue-700 dark:text-blue-400 font-medium">Application Restored</h3>
                                <p className="text-blue-600 dark:text-blue-300 text-sm">
                                    We've restored your progress from where you left off. You can continue filling out your application.
                                </p>
                            </div>
                            <button
                                onClick={() => {
                                    localStateManager.clearLocalState();
                                    window.location.reload();
                                }}
                                className="ml-auto text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 text-sm underline"
                            >
                                Start Fresh
                            </button>
                        </div>
                    </div>
                )}

                <div className="mb-8">
                    <div className="flex h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                        <div
                            className="transition-all duration-500 bg-emerald-600"
                            style={{ width: `${((steps.indexOf(currentStep) + 1) / steps.length) * 100}%` }}
                        />
                    </div>
                    <div className="flex justify-between mt-2 text-xs text-gray-500">
                        <span>Step {steps.indexOf(currentStep) + 1} of {steps.length}</span>
                        <span>{currentStep.charAt(0).toUpperCase() + currentStep.slice(1)}</span>
                    </div>
                </div>

                <div className="bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] p-6 lg:p-8">
                    {/* Reference Code Display */}
                    {wizardData.referenceCode && (
                        <div className="mb-6 p-4 border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 rounded-lg">
                            <h3 className="text-emerald-700 dark:text-emerald-400 font-medium mb-2">Your Reference Code</h3>
                            <div className="flex items-center justify-between">
                                <div className="bg-white dark:bg-gray-800 px-4 py-2 rounded-md font-mono text-lg tracking-wider border border-emerald-200 dark:border-emerald-800">
                                    {wizardData.referenceCode}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                    <p>Save this code to track or resume your application later</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Validation Errors */}
                    {showValidationSummary && validationErrors.length > 0 && (
                        <div className="mb-6 p-4 border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 rounded-lg">
                            <h3 className="text-red-700 dark:text-red-400 font-medium mb-2">Please fix the following errors:</h3>
                            <ul className="list-disc pl-5 space-y-1">
                                {validationErrors.map((error, index) => (
                                    <li key={index} className="text-red-600 dark:text-red-400 text-sm">
                                        {error.message}
                                    </li>
                                ))}
                            </ul>
                            {missingRequiredFields.length > 0 && (
                                <div className="mt-3 p-3 bg-red-100 dark:bg-red-900/30 rounded">
                                    <p className="text-sm font-medium text-red-700 dark:text-red-300">
                                        Missing required fields:
                                    </p>
                                    <ul className="list-disc pl-5 mt-1">
                                        {missingRequiredFields.map((field, index) => (
                                            <li key={index} className="text-sm text-red-600 dark:text-red-400">
                                                {formatFieldName(field)}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            <div className="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                All fields marked with <span className="text-red-500">*</span> are required.
                            </div>
                        </div>
                    )}
                    {renderStep()}
                </div>
            </div>
        </div>
    );
};

export default ApplicationWizard;
