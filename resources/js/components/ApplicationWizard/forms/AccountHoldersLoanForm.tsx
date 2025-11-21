import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ChevronLeft, User, Building, CreditCard, Users, DollarSign } from 'lucide-react';
import FormField from '@/components/ApplicationWizard/components/FormField';
import { formatZimbabweId } from '@/components/ApplicationWizard/utils/formatters';
import { zimbabweBanks } from '@/components/ApplicationWizard/data/zimbabweBanks';

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
        
        // Calculate tenure based on amount
        let tenure = 12; // default
        if (finalPrice <= 1000) tenure = 6;
        else if (finalPrice <= 5000) tenure = 12;
        else if (finalPrice <= 15000) tenure = 18;
        else tenure = 24;
        
        // Calculate monthly payment (10% annual interest)
        const interestRate = 0.10;
        const monthlyInterestRate = interestRate / 12;
        const monthlyPayment = finalPrice > 0 ? 
            (finalPrice * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, tenure)) /
            (Math.pow(1 + monthlyInterestRate, tenure) - 1) : 0;
        
        return {
            creditFacilityType: facilityType,
            loanAmount: finalPrice.toFixed(2),
            loanTenure: tenure.toString(),
            monthlyPayment: monthlyPayment.toFixed(2),
            interestRate: '10.0'
        };
    };
    
    const creditDetails = calculateCreditFacilityDetails();
    const currentDate = new Date().toISOString().split('T')[0];

    const [hasOtherLoans, setHasOtherLoans] = useState<string>(''); // 'yes' or 'no'
    const [isCustomBranch, setIsCustomBranch] = useState<boolean>(false);
    const [accountNumberError, setAccountNumberError] = useState<string>('');

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
        permanentAddress: '',
        propertyOwnership: '',
        periodAtAddress: '',
        employmentStatus: '',
        jobTitle: '',
        dateOfEmployment: '',
        employmentNumber: '',
        headOfInstitution: '',
        headOfInstitutionCell: '',
        currentNetSalary: '',

        // Spouse and Next of Kin
        spouseDetails: [
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: '' },
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: '' }
        ],

        // Banking Details - Pre-fill ZB Bank if user has ZB account
        bankName: data.hasAccount && data.accountType === 'ZB Bank Account' ? 'ZB Bank' : '',
        branch: '',
        accountNumber: '',
        
        // Other Loans
        otherLoans: [
            { institution: '', repayment: '', currentBalance: '', maturityDate: '' },
            { institution: '', repayment: '', currentBalance: '', maturityDate: '' }
        ],
        
        // Purpose/Asset (auto-populated from product selection)
        purposeAsset: data.business ? `${data.business} - ${data.scale || 'Standard Scale'}` : '',
        purposeOfLoan: data.business ? `${data.business} - ${data.scale || 'Standard Scale'}` : ''
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
                    setAccountNumberError('Account number must be exactly 13 digits');
                }
                // Check if it starts with 4
                else if (!value.startsWith('4')) {
                    setAccountNumberError('ZB Bank account number must start with 4');
                }
            }
        }

        setFormData(prev => ({
            ...prev,
            [field]: processedValue
        }));
    };

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
                
                // Spouse/Next of Kin details - ensure addresses are properly formatted
                spouseDetails: formData.spouseDetails.map(spouse => ({
                    ...spouse,
                    residentialAddress: typeof spouse.residentialAddress === 'string' ? spouse.residentialAddress : 
                        (spouse.residentialAddress && typeof spouse.residentialAddress === 'object' ? 
                            JSON.stringify(spouse.residentialAddress) : '')
                })),
                
                // Other loans
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
                            <FormField
                                id="permanentAddress"
                                label="Permanent Address"
                                type="address"
                                value={formData.permanentAddress || '{}'}
                                onChange={(value) => handleInputChange('permanentAddress', value)}
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
                            <Label htmlFor="employerName">Name of Institution *</Label>
                            <Input
                                id="employerName"
                                value={formData.employerName}
                                onChange={(e) => handleInputChange('employerName', e.target.value)}
                                required
                            />
                        </div>
                        
                        <div>
                            <FormField
                                id="employerAddress"
                                label="Institution Address"
                                type="address"
                                value={formData.employerAddress || '{}'}
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
                            />
                        </div>
                        
                        <div>
                            <Label htmlFor="employmentNumber">Employment Number</Label>
                            <Input
                                id="employmentNumber"
                                value={formData.employmentNumber}
                                onChange={(e) => handleInputChange('employmentNumber', e.target.value)}
                            />
                        </div>
                        
                        <div>
                            <Label htmlFor="currentNetSalary">Net Pay Range (USD) *</Label>
                            <Select value={formData.currentNetSalary} onValueChange={(value) => handleInputChange('currentNetSalary', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select net pay range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="100-200">$100 - $200</SelectItem>
                                    <SelectItem value="201-400">$201 - $400</SelectItem>
                                    <SelectItem value="401-600">$401 - $600</SelectItem>
                                    <SelectItem value="601-800">$601 - $800</SelectItem>
                                    <SelectItem value="801-1000">$801 - $1000</SelectItem>
                                    <SelectItem value="1001+">$1001+</SelectItem>
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
                        <h3 className="text-lg font-semibold">Spouse and Next of Kin Details *</h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        At least one next of kin is required
                    </p>
                    
                    {formData.spouseDetails.map((spouse, index) => (
                        <div key={index} className="grid gap-4 md:grid-cols-4 mb-4 p-4 border rounded-lg">
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
                                <Select value={spouse.relationship} onValueChange={(value) => handleSpouseChange(index, 'relationship', value)}>
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
                            
                            <div>
                                <FormField
                                    id={`spouse-${index}-address`}
                                    label="Residential address"
                                    type="address"
                                    value={spouse.residentialAddress || '{}'}
                                    onChange={(value) => handleSpouseChange(index, 'residentialAddress', value)}
                                />
                            </div>
                        </div>
                    ))}
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
                                    <span className="text-xs text-gray-500 ml-2">(13 digits, starts with 4)</span>
                                )}
                            </Label>
                            <Input
                                id="accountNumber"
                                value={formData.accountNumber}
                                onChange={(e) => handleInputChange('accountNumber', e.target.value)}
                                maxLength={formData.bankName === 'ZB Bank' ? 13 : undefined}
                                placeholder={formData.bankName === 'ZB Bank' ? '4XXXXXXXXXXXX' : ''}
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
                        <h3 className="text-lg font-semibold">Loans with Other Institutions (Also Include Qupa Loan)</h3>
                    </div>

                    <div className="mb-4">
                        <Label htmlFor="hasOtherLoans">Do you have loans with other institutions? *</Label>
                        <Select value={hasOtherLoans} onValueChange={setHasOtherLoans} required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select an option" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="yes">Yes</SelectItem>
                                <SelectItem value="no">No</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {hasOtherLoans === 'yes' && (
                        <>
                            {formData.otherLoans.map((loan, index) => (
                                <div key={index} className="grid gap-4 md:grid-cols-4 mb-4 p-4 border rounded-lg">
                                    <div>
                                        <Label htmlFor={`loan-${index}-institution`}>Institution</Label>
                                        <Input
                                            id={`loan-${index}-institution`}
                                            value={loan.institution}
                                            onChange={(e) => handleLoanChange(index, 'institution', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor={`loan-${index}-repayment`}>Repayment</Label>
                                        <Input
                                            id={`loan-${index}-repayment`}
                                            value={loan.repayment}
                                            onChange={(e) => handleLoanChange(index, 'repayment', e.target.value)}
                                        />
                                    </div>

                                    <div style={{ display: 'none' }}>
                                        <Label htmlFor={`loan-${index}-balance`}>Current Loan Balance</Label>
                                        <Input
                                            id={`loan-${index}-balance`}
                                            value={loan.currentBalance}
                                            onChange={(e) => handleLoanChange(index, 'currentBalance', e.target.value)}
                                        />
                                    </div>

                                    <div style={{ display: 'none' }}>
                                        <FormField
                                            id={`loan-${index}-maturity`}
                                            label="Maturity Date"
                                            type="dial-date"
                                            value={loan.maturityDate}
                                            onChange={(value) => handleLoanChange(index, 'maturityDate', value)}
                                            minDate={currentDate}
                                            maxDate="2050-12-31"
                                            defaultAge={0}
                                        />
                                    </div>
                                </div>
                            ))}
                        </>
                    )}
                </Card>

                {/* Credit Facility Details */}
                <Card className="p-6 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Hire Purchase Application Details</h3>
                    </div>
                    
                    {/* Pre-populated readonly fields */}
                    <div className="grid gap-4 mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border">
                        <div className="text-sm text-green-600 dark:text-green-400 font-medium mb-2">
                            ✅ The following details have been automatically filled based on your product selection:
                        </div>
                        
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Hire Purchase Facility Type</Label>
                                <Input
                                    value={formData.creditFacilityType}
                                    readOnly
                                    className="bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300"> Amount (USD)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <Input
                                        value={formData.loanAmount}
                                        readOnly
                                        className="pl-8 bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600"
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
                                <Label className="text-gray-700 dark:text-gray-300">Monthly Payment (USD)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <Input
                                        value={formData.monthlyPayment}
                                        readOnly
                                        className="pl-8 bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600"
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
                        {loading ? 'Submitting...' : 'Submit Application'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default AccountHoldersLoanForm;
