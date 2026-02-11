import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ChevronLeft, User, Building, CreditCard, Users, DollarSign } from 'lucide-react';
import FormField from '@/components/ApplicationWizard/components/FormField';
import AddressInput, { AddressData } from '@/components/ui/address-input';
import { formatZimbabweId } from '@/components/ApplicationWizard/utils/formatters';
import { zimbabweBanks } from '@/components/ApplicationWizard/data/zimbabweBanks';
import { securityCompanies } from '@/components/ApplicationWizard/data/securityCompanies';

interface AccountHoldersLoanFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const AccountHoldersLoanForm: React.FC<AccountHoldersLoanFormProps> = ({ data, onNext, onBack, loading }) => {
    // Helper function to convert property ownership values
    const convertPropertyOwnership = (value: string): string => {
        const mappings = {
            'Owned': 'Owned',
            'Employer Owned': 'Employer Owned',
            'Rented': 'Rented',
            'Mortgaged': 'Mortgaged',
            'Parents Owned': 'Parents Owned'
        };
        return mappings[value as keyof typeof mappings] || value;
    };

    // Helper function to convert period at address values
    const convertPeriodToText = (value: string): string => {
        const mappings = {
            'Less than One Year': 'Less than One Year',
            'Between 1-2 years': 'Between 1-2 years',
            'Between 2-5 years': 'Between 2-5 years',
            'More than 5 years': 'More than 5 years'
        };
        return mappings[value as keyof typeof mappings] || value;
    };

    // Calculate credit facility details from product selection
    const calculateCreditFacilityDetails = () => {
        const businessName = data.business || ''; // string from ProductSelection
        const finalPrice = data.amount || 0; // number from ProductSelection
        const intent = data.intent || 'hirePurchase';
        const selectedMonth = data.creditTerm;

        let facilityType = '';
        if (intent === 'hirePurchase' && businessName) {
            facilityType = `Hire Purchase Credit - ${businessName}`;
        } else if ((intent === 'microBiz' || intent === 'microBizLoan') && businessName) {
            facilityType = `Micro Biz Loan - ${businessName}`;
        } else if (businessName) {
            // Fallback if intent doesn't match
            facilityType = `Credit Facility - ${businessName}`;
        } else {
            facilityType = 'Credit Facility';
        }

        // Calculate tenure
        let tenure = 12; // default
        if (selectedMonth) {
            tenure = parseInt(selectedMonth.toString());
        } else {
            if (finalPrice <= 1000) tenure = 6;
            else if (finalPrice <= 5000) tenure = 12;
            else if (finalPrice <= 15000) tenure = 18;
            else tenure = 24;
        }

        // Calculate monthly payment
        let monthlyPaymentValue = 0;
        if (data.monthlyPayment) {
            monthlyPaymentValue = parseFloat(data.monthlyPayment);
        } else {
            const interestRate = 0.10;
            const monthlyInterestRate = interestRate / 12;
            monthlyPaymentValue = finalPrice > 0 ?
                (finalPrice * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, tenure)) /
                (Math.pow(1 + monthlyInterestRate, tenure) - 1) : 0;
        }

        return {
            creditFacilityType: facilityType,
            loanAmount: finalPrice.toFixed(2),
            loanTenure: tenure.toString(),
            monthlyPayment: monthlyPaymentValue.toFixed(2),
            interestRate: '10.0'
        };
    };

    const creditDetails = calculateCreditFacilityDetails();
    const currentDate = new Date().toISOString().split('T')[0];
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';

    const [hasOtherLoans, setHasOtherLoans] = useState<string>(''); // 'yes' or 'no'
    const [loanType, setLoanType] = useState<string>(''); // 'qupa' | 'other' | 'both'
    const [isCustomBranch, setIsCustomBranch] = useState<boolean>(false);
    const [accountNumberError, setAccountNumberError] = useState<string>('');
    const [employmentError, setEmploymentError] = useState<string>('');

    const [formData, setFormData] = useState({
        // Credit Facility Details (pre-populated)
        ...creditDetails,

        // Header Fields
        deliveryStatus: 'Future',
        province: '',
        agent: '',
        team: '',

        // Personal Details
        title: '',
        surname: '',
        firstName: '',
        gender: '',
        dateOfBirth: '',
        maritalStatus: '',
        nationality: 'Zimbabwean',
        idNumber: '',
        cellNumber: '',
        whatsApp: '',
        emailAddress: '',
        responsiblePaymaster: '',
        employerName: '',
        employerAddress: '',
        permanentAddress: { type: '', addressLine: '' } as AddressData,
        propertyOwnership: '',
        periodAtAddress: '',
        employmentStatus: '',
        jobTitle: '',
        dateOfEmployment: '',
        employmentNumber: '',
        headOfInstitution: '',
        headOfInstitutionCell: '',
        currentNetSalary: '',
        department: '', // Added for GOZ Non-SSB

        // Spouse and Next of Kin
        spouseDetails: [
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: { type: '', addressLine: '' } as AddressData },
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: { type: '', addressLine: '' } as AddressData }
        ],

        // Banking Details - Pre-fill ZB Bank if user has ZB account
        bankName: data.hasAccount && data.accountType === 'ZB Bank Account' ? 'ZB Bank' : '',
        branch: '',
        accountNumber: '',

        // Other Loans - Qupa (ZB) and Other Institutions
        qupaLoan: {
            maturityDate: '',
            monthlyInstallment: '',
        },
        otherInstitutionLoan: {
            institutionName: '',
            maturityDate: '',
            monthlyInstallment: '',
        },
        // Legacy format for backward compatibility
        otherLoans: [
            { institution: '', repayment: '', currentBalance: '', maturityDate: '' },
            { institution: '', repayment: '', currentBalance: '', maturityDate: '' }
        ],

        purposeAsset: data.business ? `${data.business} - ${data.scale || 'Standard Scale'}` : '',
        purposeOfLoan: data.business ? `${data.business} - ${data.scale || 'Standard Scale'}` : '',

        // Guarantor Details
        guarantor: {
            name: '',
            phoneNumber: '',
            idNumber: ''
        }
    });

    const handleInputChange = (field: string, value: string) => {
        const processedValue = field.toLowerCase().includes('idnumber')
            ? formatZimbabweId(value)
            : value;

        // Validate account number for ZB Bank
        if (field === 'accountNumber' && formData.bankName === 'ZB Bank') {
            // Clear previous error
            setAccountNumberError('');

            // Only validate if there's a value
            if (value) {
                // Check if it's exactly 13 digits
                if (!/^\d{13}$/.test(value)) {
                    setAccountNumberError('Please enter your correct account number');
                }
                // Check if it starts with 4
                else if (!value.startsWith('4')) {
                    setAccountNumberError('Please enter your correct ZB Bank account number');
                }
            }
        }

        setFormData(prev => ({
            ...prev,
            [field]: processedValue
        }));
    };

    // Auto-set spouse relation if married
    React.useEffect(() => {
        if (formData.maritalStatus === 'Married') {
            setFormData(prev => ({
                ...prev,
                spouseDetails: prev.spouseDetails.map((spouse, i) =>
                    i === 0 ? { ...spouse, relationship: 'Spouse' } : spouse
                )
            }));
        }
    }, [formData.maritalStatus]);

    const handleSpouseChange = (index: number, field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            spouseDetails: prev.spouseDetails.map((spouse, i) =>
                i === index ? { ...spouse, [field]: value } : spouse
            )
        }));
    };

    const handleLoanChange = (index: number, field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            otherLoans: prev.otherLoans.map((loan, i) =>
                i === index ? { ...loan, [field]: value } : loan
            )
        }));
    };

    const handleGuarantorChange = (field: string, value: string) => {
        const processedValue = field === 'idNumber' ? formatZimbabweId(value) : value;

        setFormData(prev => ({
            ...prev,
            guarantor: {
                ...prev.guarantor,
                [field]: processedValue
            }
        }));
    };

    // Set default values for GOZ Non-SSB
    React.useEffect(() => {
        if (data.employer === 'government-non-ssb' && !formData.employerName) {
            // Default to Ministry of Defence if not set
            setFormData(prev => ({
                ...prev,
                employerName: 'Ministry of Defence'
            }));
        }
    }, [data.employer]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate account number for ZB Bank before submission
        if (formData.bankName === 'ZB Bank' && formData.accountNumber) {
            if (!/^\d{13}$/.test(formData.accountNumber)) {
                setAccountNumberError('Account number must be exactly 13 digits');
                return;
            }
            if (!formData.accountNumber.startsWith('4')) {
                setAccountNumberError('ZB Bank account number must start with 4');
                return;
            }
        }

        // Validate Employment Number
        const employmentNumberRegex = /^\d{7}[A-Z]$/;
        if (formData.employmentNumber && !employmentNumberRegex.test(formData.employmentNumber)) {
            setEmploymentError('Employment Number must be 7 digits followed by a letter (e.g. 1234567A)');
            const el = document.getElementById('employmentNumber');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Map Account Holders form fields to match PDF template expectations
        const mappedData = {
            formResponses: {
                // Personal details - mapped to PDF template field names
                firstName: formData.firstName,
                surname: formData.surname,
                lastName: formData.surname, // PDF uses lastName in some places
                title: formData.title,
                dateOfBirth: formData.dateOfBirth,
                gender: formData.gender,
                nationalIdNumber: formData.idNumber,
                mobile: formData.cellNumber,
                whatsApp: formData.whatsApp,
                emailAddress: formData.emailAddress,
                maritalStatus: formData.maritalStatus,
                nationality: formData.nationality,

                // Address mappings - PDF template expects residentialAddress
                residentialAddress: typeof formData.permanentAddress === 'string' ? formData.permanentAddress :
                    (formData.permanentAddress && typeof formData.permanentAddress === 'object' ?
                        JSON.stringify(formData.permanentAddress) : ''),

                // Property and residence details - convert values for PDF compatibility
                propertyOwnership: convertPropertyOwnership(formData.propertyOwnership),
                periodAtAddress: convertPeriodToText(formData.periodAtAddress),
                employmentStatus: formData.employmentStatus,

                // Employment details
                employerName: formData.employerName,
                department: formData.department, // Map department field
                currentNetSalary: formData.currentNetSalary,
                jobTitle: formData.jobTitle,
                employerAddress: typeof formData.employerAddress === 'string' ? formData.employerAddress :
                    (formData.employerAddress && typeof formData.employerAddress === 'object' ?
                        JSON.stringify(formData.employerAddress) : ''),
                dateOfEmployment: formData.dateOfEmployment,
                employmentNumber: formData.employmentNumber,
                headOfInstitution: formData.headOfInstitution,
                headOfInstitutionCell: formData.headOfInstitutionCell,
                responsiblePaymaster: formData.responsiblePaymaster,

                // Banking details
                bankName: formData.bankName,
                branch: formData.branch,
                accountNumber: formData.accountNumber,

                // Loan details
                loanAmount: formData.loanAmount,
                loanTenure: formData.loanTenure,
                monthlyPayment: formData.monthlyPayment,
                interestRate: formData.interestRate,
                creditFacilityType: formData.creditFacilityType,
                purposeAsset: formData.purposeAsset,
                purposeOfLoan: formData.purposeAsset,

                // Guarantor details
                guarantor: formData.guarantor,

                // Spouse/Next of Kin details - ensure addresses are properly formatted
                spouseDetails: formData.spouseDetails.map(spouse => ({
                    ...spouse,
                    residentialAddress: typeof spouse.residentialAddress === 'string' ? spouse.residentialAddress :
                        (spouse.residentialAddress && typeof spouse.residentialAddress === 'object' ?
                            JSON.stringify(spouse.residentialAddress) : '')
                })),

                // Other loans - new structure
                hasOtherLoans: hasOtherLoans,
                loanType: loanType,
                qupaLoan: formData.qupaLoan,
                otherInstitutionLoan: formData.otherInstitutionLoan,
                // Legacy format for backward compatibility
                otherLoans: formData.otherLoans,

                // Header fields for PDF
                deliveryStatus: formData.deliveryStatus,
                province: formData.province,
                agent: formData.agent,
                team: formData.team
            },
            documents: {
                uploadedDocuments: {
                    national_id: [],
                    payslip: [],
                    bank_statement: [],
                    employment_letter: []
                },
                selfie: '',
                signature: '',
                uploadedAt: new Date().toISOString(),
                documentReferences: {},
                validationSummary: {
                    allDocumentsValid: false,
                    totalDocuments: 0,
                    completedDocuments: 0,
                    documentTypes: ['national_id', 'payslip', 'bank_statement', 'employment_letter']
                }
            },
            formType: 'account_holder_loan_application'
        };

        onNext(mappedData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Loan Application Form</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Complete your loan application details
                </p>
                <p className="text-sm text-red-600 dark:text-red-400">
                    Fields marked with * are required
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">

                {/* Personal Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <User className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Personal Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <Label htmlFor="title">Title</Label>
                            <Select value={formData.title} onValueChange={(value) => handleInputChange('title', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select title" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Mr">Mr</SelectItem>
                                    <SelectItem value="Mrs">Mrs</SelectItem>
                                    <SelectItem value="Miss">Miss</SelectItem>
                                    <SelectItem value="Dr">Dr</SelectItem>
                                    <SelectItem value="Prof">Prof</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="surname"
                                label="Surname"
                                type="text"
                                value={formData.surname}
                                onChange={(value) => handleInputChange('surname', value)}
                                required
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="firstName"
                                label="First Name"
                                type="text"
                                value={formData.firstName}
                                onChange={(value) => handleInputChange('firstName', value)}
                                required
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <Label htmlFor="gender">Gender *</Label>
                            <Select value={formData.gender} onValueChange={(value) => handleInputChange('gender', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select gender" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Male">Male</SelectItem>
                                    <SelectItem value="Female">Female</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="dateOfBirth"
                                label="Date of Birth"
                                type="dial-date"
                                value={formData.dateOfBirth}
                                onChange={(value) => handleInputChange('dateOfBirth', value)}
                                required
                                maxDate={`${new Date().getFullYear() - 18}-12-31`}
                                minDate="1930-01-01"
                                defaultAge={20}
                                showAgeValidation={true}
                            />
                        </div>

                        <div>
                            <Label htmlFor="maritalStatus">Marital Status</Label>
                            <Select value={formData.maritalStatus} onValueChange={(value) => handleInputChange('maritalStatus', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Single">Single</SelectItem>
                                    <SelectItem value="Married">Married</SelectItem>
                                    <SelectItem value="Divorced">Divorced</SelectItem>
                                    <SelectItem value="Widowed">Widowed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="idNumber"
                                label="ID Number"
                                type="text"
                                value={formData.idNumber}
                                onChange={(value) => handleInputChange('idNumber', value)}
                                required
                                capitalizeCheckLetter={true}
                                placeholder="e.g. 12-345678 A 12"
                                title="Zimbabwe ID format: 12-345678 A 12"
                            />
                        </div>

                        <div>
                            <FormField
                                id="cellNumber"
                                label="Cell Number"
                                type="phone"
                                value={formData.cellNumber}
                                onChange={(value) => handleInputChange('cellNumber', value)}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="emailAddress"
                                label="Email Address"
                                type="email"
                                value={formData.emailAddress}
                                onChange={(value) => handleInputChange('emailAddress', value)}
                                required
                            />
                        </div>
                        <div className="md:col-span-2 lg:col-span-3">
                            <AddressInput
                                id="permanentAddress"
                                label="Residential Address"
                                value={formData.permanentAddress}
                                onChange={(value) => setFormData(prev => ({ ...prev, permanentAddress: value }))}
                                required
                            />
                        </div>

                        <div className="md:col-span-2 lg:col-span-3">
                            <Label>Accommodation Status</Label>
                            <div className="flex gap-4 mt-2">
                                {['Owned', 'Employer Owned', 'Rented', 'Mortgaged', 'Parents Owned'].map((option) => (
                                    <label key={option} className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            name="propertyOwnership"
                                            value={option}
                                            checked={formData.propertyOwnership === option}
                                            onChange={(e) => handleInputChange('propertyOwnership', e.target.value)}
                                            className="text-emerald-600"
                                        />
                                        <span className="text-sm">{option}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="md:col-span-2 lg:col-span-3">
                            <Label>Period at current address</Label>
                            <div className="flex gap-4 mt-2">
                                {['Less than One Year', 'Between 1-2 years', 'Between 2-5 years', 'More than 5 years'].map((option) => (
                                    <label key={option} className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            name="periodAtAddress"
                                            value={option}
                                            checked={formData.periodAtAddress === option}
                                            onChange={(e) => handleInputChange('periodAtAddress', e.target.value)}
                                            className="text-emerald-600"
                                        />
                                        <span className="text-sm">{option}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="md:col-span-2 lg:col-span-3">
                            <Label>Status of employment</Label>
                            <div className="flex gap-4 mt-2">
                                {['Permanent', 'Contract', 'Part time'].map((option) => (
                                    <label key={option} className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            name="employmentStatus"
                                            value={option}
                                            checked={formData.employmentStatus === option}
                                            onChange={(e) => handleInputChange('employmentStatus', e.target.value)}
                                            className="text-emerald-600"
                                        />
                                        <span className="text-sm">{option}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Employment Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Employment Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="employerName">{data.employer === 'government-non-ssb' ? 'Ministry' : 'Name of Institution *'}</Label>
                            {data.employer === 'government-non-ssb' ? (
                                <Select
                                    value={formData.employerName}
                                    onValueChange={(value) => {
                                        // Reset department when ministry changes
                                        handleInputChange('employerName', value);
                                        handleInputChange('department', '');
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Ministry" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Ministry of Defence">Ministry of Defence</SelectItem>
                                        <SelectItem value="Other Security Sector">Other Security Sector</SelectItem>
                                    </SelectContent>
                                </Select>
                            ) : data.employer === 'security-company' ? (
                                <div className="space-y-2">
                                    <Select
                                        value={formData.employerName && !securityCompanies.includes(formData.employerName) ? 'Other' : formData.employerName}
                                        onValueChange={(value) => {
                                            if (value === 'Other') {
                                                handleInputChange('employerName', '');
                                            } else {
                                                handleInputChange('employerName', value);
                                            }
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Security Company" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {securityCompanies.map((company) => (
                                                <SelectItem key={company} value={company}>
                                                    {company}
                                                </SelectItem>
                                            ))}
                                            <SelectItem value="Other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {(!formData.employerName || !securityCompanies.includes(formData.employerName)) && (
                                        <Input
                                            placeholder="Specify Security Company"
                                            value={formData.employerName}
                                            onChange={(e) => handleInputChange('employerName', e.target.value)}
                                            className="mt-2"
                                        />
                                    )}
                                </div>
                            ) : (
                                <Input
                                    id="employerName"
                                    value={formData.employerName}
                                    onChange={(e) => handleInputChange('employerName', e.target.value)}
                                    required
                                />
                            )}
                        </div>

                        {data.employer === 'government-non-ssb' && (
                            <div>
                                <Label htmlFor="department">Department</Label>
                                {formData.employerName === 'Other Security Sector' ? (
                                    <Input
                                        id="department"
                                        value={formData.department}
                                        onChange={(e) => handleInputChange('department', e.target.value)}
                                        placeholder="Specify Security Sector"
                                    />
                                ) : (
                                    <Select
                                        value={formData.department}
                                        onValueChange={(value) => handleInputChange('department', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Zimbabwe National Army">Zimbabwe National Army</SelectItem>
                                            <SelectItem value="Air Force of Zimbabwe">Air Force of Zimbabwe</SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            </div>
                        )}

                        <div>
                            <FormField
                                id="employerAddress"
                                label="Institution Address"
                                type="text"
                                value={typeof formData.employerAddress === 'string' ? formData.employerAddress : ''}
                                onChange={(value) => handleInputChange('employerAddress', value)}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="jobTitle">Job Title *</Label>
                            <Input
                                id="jobTitle"
                                value={formData.jobTitle}
                                onChange={(e) => handleInputChange('jobTitle', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="dateOfEmployment"
                                label="Date of Employment"
                                type="dial-date"
                                value={formData.dateOfEmployment}
                                onChange={(value) => handleInputChange('dateOfEmployment', value)}
                                maxDate={currentDate}
                                defaultAge={0}
                                required
                                hideDay={true}
                            />
                        </div>

                        <div>
                            <Label htmlFor="employmentNumber">Employment Number</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="employmentNumber"
                                    className="flex-1"
                                    placeholder="1234567"
                                    maxLength={7}
                                    value={formData.employmentNumber && formData.employmentNumber.match(/^\d+/) ? (formData.employmentNumber.match(/^\d+/) || [''])[0] : ''}
                                    onChange={(e) => {
                                        const num = e.target.value.replace(/\D/g, '').slice(0, 7);
                                        const currentLetter = formData.employmentNumber ? formData.employmentNumber.replace(/^\d+/, '') : '';
                                        handleInputChange('employmentNumber', num + currentLetter);
                                    }}
                                />
                                <Input
                                    id="employmentCheckLetter"
                                    className="w-16 text-center"
                                    placeholder="A"
                                    maxLength={1}
                                    value={formData.employmentNumber ? formData.employmentNumber.replace(/^\d+/, '') : ''}
                                    onChange={(e) => {
                                        const letter = e.target.value.replace(/[^a-zA-Z]/g, '').slice(0, 1).toUpperCase();
                                        const currentNumMatch = formData.employmentNumber ? formData.employmentNumber.match(/^\d+/) : null;
                                        const numStr = currentNumMatch ? currentNumMatch[0] : '';
                                        handleInputChange('employmentNumber', numStr + letter);
                                    }}
                                />
                            </div>
                            {employmentError && (
                                <p className="text-sm text-red-500 mt-1">{employmentError}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="currentNetSalary">Net Pay Range ({selectedCurrency}) *</Label>
                            <Select value={formData.currentNetSalary} onValueChange={(value) => handleInputChange('currentNetSalary', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select net pay range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="100-200">{currencySymbol}100 - {currencySymbol}200</SelectItem>
                                    <SelectItem value="201-400">{currencySymbol}201 - {currencySymbol}400</SelectItem>
                                    <SelectItem value="401-600">{currencySymbol}401 - {currencySymbol}600</SelectItem>
                                    <SelectItem value="601-800">{currencySymbol}601 - {currencySymbol}800</SelectItem>
                                    <SelectItem value="801-1000">{currencySymbol}801 - {currencySymbol}1000</SelectItem>
                                    <SelectItem value="1001+">{currencySymbol}1001+</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="headOfInstitution">Name of Immediate Supervisor</Label>
                            <Input
                                id="headOfInstitution"
                                value={formData.headOfInstitution}
                                onChange={(e) => handleInputChange('headOfInstitution', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="headOfInstitutionCell"
                                label="Cell No of Head of Immediate Supervisor"
                                type="phone"
                                value={formData.headOfInstitutionCell}
                                onChange={(value) => handleInputChange('headOfInstitutionCell', value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* Spouse and Next of Kin */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Users className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">
                            {formData.spouseDetails[0]?.relationship === 'Spouse' || formData.maritalStatus === 'Married'
                                ? (formData.gender === 'Male' ? "Wife's" : formData.gender === 'Female' ? "Husband's" : "Spouse")
                                : "Next of Kin"} Details *
                        </h3>
                    </div>

                    <p className="text-xs text-gray-500 italic mb-4">
                        *this is for statistical and record keeping purposes only*
                    </p>

                    {formData.spouseDetails.map((spouse, index) => {
                        let containerLabel = "Next of Kin Details";
                        if (index === 0) {
                            if (formData.maritalStatus === 'Married') {
                                if (formData.gender === 'Male') containerLabel = "Wife's Details";
                                else if (formData.gender === 'Female') containerLabel = "Husband's Details";
                                else containerLabel = "Spouse Details";
                            }
                        }

                        return (
                            <div key={index} className="grid gap-4 md:grid-cols-4 mb-4 p-4 border rounded-lg">
                                <div className="md:col-span-4 mb-2">
                                    <h4 className="text-md font-medium text-emerald-800 dark:text-emerald-400 border-b pb-1">
                                        {containerLabel}
                                    </h4>
                                </div>

                                <div>
                                    <FormField
                                        id={`spouse-${index}-name`}
                                        label={`Full Names${index === 0 ? ' *' : ''}`}
                                        type="text"
                                        value={spouse.fullName}
                                        onChange={(value) => handleSpouseChange(index, 'fullName', value)}
                                        required={index === 0}
                                        autoCapitalize={true}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor={`spouse-${index}-relationship`}>Relationship{index === 0 && spouse.fullName ? ' *' : ''}</Label>
                                    <Select
                                        value={spouse.relationship}
                                        onValueChange={(value) => handleSpouseChange(index, 'relationship', value)}
                                        disabled={index === 0 && formData.maritalStatus === 'Married'}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select relationship" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Spouse">Spouse</SelectItem>
                                            <SelectItem value="Parent">Parent</SelectItem>
                                            <SelectItem value="Child">Child</SelectItem>
                                            <SelectItem value="Relative">Relative</SelectItem>
                                            <SelectItem value="Work colleague">Work colleague</SelectItem>
                                            <SelectItem value="Friend">Friend</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <FormField
                                        id={`spouse-${index}-phone`}
                                        label={`Phone Numbers${index === 0 && spouse.fullName ? ' *' : ''}`}
                                        type="phone"
                                        value={spouse.phoneNumber}
                                        onChange={(value) => handleSpouseChange(index, 'phoneNumber', value)}
                                        required={index === 0 && !!spouse.fullName}
                                    />
                                </div>

                                <div className="md:col-span-2">
                                    <AddressInput
                                        id={`spouse-${index}-address`}
                                        label="Residential Address"
                                        value={spouse.residentialAddress as AddressData}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseDetails: prev.spouseDetails.map((s, i) =>
                                                    i === index ? { ...s, residentialAddress: value } : s
                                                )
                                            }));
                                        }}
                                        required={index === 0}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </Card>

                {/* Banking/Mobile Account Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Banking/Mobile Account Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="bankName">
                                Bank
                                {data.hasAccount && data.accountType === 'ZB Bank Account' && (
                                    <span className="text-xs text-emerald-600 ml-2">(Pre-filled)</span>
                                )}
                            </Label>
                            <Select
                                value={formData.bankName}
                                onValueChange={(value) => {
                                    handleInputChange('bankName', value);
                                    handleInputChange('branch', ''); // Reset branch when bank changes
                                    setIsCustomBranch(false); // Reset custom branch mode
                                    setAccountNumberError(''); // Clear account number error when bank changes
                                }}
                                disabled={data.hasAccount && data.accountType === 'ZB Bank Account'}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select bank" />
                                </SelectTrigger>
                                <SelectContent>
                                    {zimbabweBanks.map((bank) => (
                                        <SelectItem key={bank.name} value={bank.name}>
                                            {bank.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="branch">Branch</Label>
                            {!isCustomBranch ? (
                                <Select
                                    value={formData.branch}
                                    onValueChange={(value) => {
                                        if (value === '__custom__') {
                                            setIsCustomBranch(true);
                                            handleInputChange('branch', '');
                                        } else {
                                            handleInputChange('branch', value);
                                        }
                                    }}
                                    disabled={!formData.bankName}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={formData.bankName ? "Select branch" : "Select bank first"} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formData.bankName && zimbabweBanks
                                            .find(bank => bank.name === formData.bankName)
                                            ?.branches.map((branch) => (
                                                <SelectItem key={branch} value={branch}>
                                                    {branch}
                                                </SelectItem>
                                            ))}
                                        {formData.bankName && (
                                            <SelectItem value="__custom__" className="text-emerald-600 font-medium">
                                                + Enter custom branch
                                            </SelectItem>
                                        )}
                                    </SelectContent>
                                </Select>
                            ) : (
                                <div className="flex gap-2">
                                    <Input
                                        id="branch"
                                        value={formData.branch}
                                        onChange={(e) => handleInputChange('branch', e.target.value)}
                                        placeholder="Enter branch name"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setIsCustomBranch(false);
                                            handleInputChange('branch', '');
                                        }}
                                        className="whitespace-nowrap"
                                    >
                                        Select from list
                                    </Button>
                                </div>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="accountNumber">
                                Account Number
                                {formData.bankName === 'ZB Bank' && (
                                    <span className="text-xs text-gray-500 ml-2">( )</span>
                                )}
                            </Label>
                            <Input
                                id="accountNumber"
                                value={formData.accountNumber}
                                onChange={(e) => handleInputChange('accountNumber', e.target.value)}
                                maxLength={formData.bankName === 'ZB Bank' ? 13 : undefined}
                                placeholder={formData.bankName === 'ZB Bank' ? 'XXXXXXXXXXXXX' : ''}
                                className={accountNumberError ? 'border-red-500' : ''}
                            />
                            {accountNumberError && (
                                <p className="text-sm text-red-500 mt-1">{accountNumberError}</p>
                            )}
                        </div>
                    </div>
                </Card>

                {/* Loans with Other Institutions */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <DollarSign className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Existing Loans</h3>
                    </div>

                    {/* Question 1: Do you have a loan? */}
                    <div className="mb-4">
                        <Label htmlFor="hasOtherLoans">Do you have a loan with any other financial institution? *</Label>
                        <Select
                            value={hasOtherLoans}
                            onValueChange={(value) => {
                                setHasOtherLoans(value);
                                if (value === 'no') {
                                    setLoanType('');
                                }
                            }}
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select an option" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="yes">Yes</SelectItem>
                                <SelectItem value="no">No</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Question 2: What type of loan? (Only if Yes) */}
                    {hasOtherLoans === 'yes' && (
                        <div className="mb-4">
                            <Label htmlFor="loanType">Is it Qupa, Other Institution, or Both? *</Label>
                            <Select value={loanType} onValueChange={setLoanType} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select loan type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="qupa">Qupa Only</SelectItem>
                                    <SelectItem value="other">Other Institution Only</SelectItem>
                                    <SelectItem value="both">Both Qupa and Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {/* Qupa Loan Details (shown for 'qupa' or 'both') */}
                    {hasOtherLoans === 'yes' && (loanType === 'qupa' || loanType === 'both') && (
                        <div className="mb-4 p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20">
                            <h4 className="text-md font-medium text-blue-800 dark:text-blue-300 mb-3">
                                Qupa Loan Details
                            </h4>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <FormField
                                        id="qupa-maturity"
                                        label="Maturity Date *"
                                        type="dial-date"
                                        value={formData.qupaLoan.maturityDate}
                                        onChange={(value) => setFormData(prev => ({
                                            ...prev,
                                            qupaLoan: { ...prev.qupaLoan, maturityDate: value }
                                        }))}
                                        minDate={currentDate}
                                        maxDate="2050-12-31"
                                        defaultAge={0}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="qupa-installment">Monthly Installment ({selectedCurrency}) *</Label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-2.5 text-gray-500">{currencySymbol}</span>
                                        <Input
                                            id="qupa-installment"
                                            type="number"
                                            value={formData.qupaLoan.monthlyInstallment}
                                            onChange={(e) => setFormData(prev => ({
                                                ...prev,
                                                qupaLoan: { ...prev.qupaLoan, monthlyInstallment: e.target.value }
                                            }))}
                                            className="pl-8"
                                            placeholder="0.00"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Other Institution Loan Details (shown for 'other' or 'both') */}
                    {hasOtherLoans === 'yes' && (loanType === 'other' || loanType === 'both') && (
                        <div className="mb-4 p-4 border rounded-lg bg-orange-50 dark:bg-orange-900/20">
                            <h4 className="text-md font-medium text-orange-800 dark:text-orange-300 mb-3">
                                Other Institution Loan Details
                            </h4>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <Label htmlFor="other-institution">Institution Name *</Label>
                                    <Input
                                        id="other-institution"
                                        value={formData.otherInstitutionLoan.institutionName}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            otherInstitutionLoan: { ...prev.otherInstitutionLoan, institutionName: e.target.value }
                                        }))}
                                        placeholder="e.g., CBZ, FBC, CABS"
                                        required
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="other-maturity"
                                        label="Maturity Date *"
                                        type="dial-date"
                                        value={formData.otherInstitutionLoan.maturityDate}
                                        onChange={(value) => setFormData(prev => ({
                                            ...prev,
                                            otherInstitutionLoan: { ...prev.otherInstitutionLoan, maturityDate: value }
                                        }))}
                                        minDate={currentDate}
                                        maxDate="2050-12-31"
                                        defaultAge={0}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="other-installment">Monthly Installment ({selectedCurrency}) *</Label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-2.5 text-gray-500">{currencySymbol}</span>
                                        <Input
                                            id="other-installment"
                                            type="number"
                                            value={formData.otherInstitutionLoan.monthlyInstallment}
                                            onChange={(e) => setFormData(prev => ({
                                                ...prev,
                                                otherInstitutionLoan: { ...prev.otherInstitutionLoan, monthlyInstallment: e.target.value }
                                            }))}
                                            className="pl-8"
                                            placeholder="0.00"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Credit Facility Details */}
                <Card className="p-6 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Credit Application Details</h3>
                    </div>

                    {/* Pre-populated readonly fields */}
                    <div className="grid gap-4 mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border">
                        <div className="text-sm text-green-600 dark:text-green-400 font-medium mb-2">
                            ✅ The following details have been automatically filled based on your product selection:
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Credit Facility Type</Label>
                                <Input
                                    value={formData.creditFacilityType}
                                    readOnly
                                    className="bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300"> Amount ({selectedCurrency})</Label>
                                <div className="relative">
                                    {isZiG ? (
                                        <span className="absolute left-3 top-2.5 text-gray-500 text-xs font-bold pt-0.5">ZiG</span>
                                    ) : (
                                        <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    )}
                                    <Input
                                        value={formData.loanAmount}
                                        readOnly
                                        className={`bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 ${isZiG ? 'pl-10' : 'pl-8'}`}
                                    />
                                </div>
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Duration (Months)</Label>
                                <Input
                                    value={`${formData.loanTenure} months`}
                                    readOnly
                                    className="bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Monthly Payment ({selectedCurrency})</Label>
                                <div className="relative">
                                    {isZiG ? (
                                        <span className="absolute left-3 top-2.5 text-gray-500 text-xs font-bold pt-0.5">ZiG</span>
                                    ) : (
                                        <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    )}
                                    <Input
                                        value={formData.monthlyPayment}
                                        readOnly
                                        className={`bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 ${isZiG ? 'pl-10' : 'pl-8'}`}
                                    />
                                </div>
                            </div>
                            {/* Interest rate field hidden from client but available in background */}
                            <input
                                type="hidden"
                                name="interestRate"
                                value={formData.interestRate}
                            />
                        </div>
                    </div>

                    {/* Editable purpose field */}
                    <div>
                        <Label htmlFor="purposeAsset">Purpose/Asset Applied For *</Label>
                        <Textarea
                            id="purposeAsset"
                            value={formData.purposeAsset}
                            onChange={(e) => handleInputChange('purposeAsset', e.target.value)}
                            rows={4}
                            required
                            placeholder="Describe the purpose and asset details..."
                        />
                    </div>
                </Card>

                {/* Guarantor Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <User className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Guarantor Details</h3>
                  <h5>Please fill in the guarantor details below</h5>  
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <FormField
                                id="guarantorName"
                                label="Guarantor Name *"
                                type="text"
                                value={formData.guarantor.name}
                                onChange={(value) => handleGuarantorChange('name', value)}
                                required
                            />
                        </div>
                        <div>
                            <FormField
                                id="guarantorPhone"
                                label="Guarantor Phone Number *"
                                type="phone"
                                value={formData.guarantor.phoneNumber}
                                onChange={(value) => handleGuarantorChange('phoneNumber', value)}
                                required
                            />
                        </div>
                        <div>
                            <FormField
                                id="guarantorId"
                                label="Guarantor ID Number *"
                                type="text"
                                value={formData.guarantor.idNumber}
                                onChange={(value) => handleGuarantorChange('idNumber', value)}
                                required
                                capitalizeCheckLetter={true}
                                placeholder="e.g. 12-345678 A 12"
                                title="Zimbabwe ID format: 12-345678 A 12"
                            />
                        </div>
                    </div>
                </Card>

                <div className="flex justify-between pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onBack}
                        disabled={loading}
                        className="flex items-center gap-2"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back
                    </Button>

                    <Button
                        type="submit"
                        disabled={loading}
                        className="bg-emerald-600 hover:bg-emerald-700 px-8"
                    >
                        {loading ? 'Submitting...' : 'Agree & Submit Application'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default AccountHoldersLoanForm;
