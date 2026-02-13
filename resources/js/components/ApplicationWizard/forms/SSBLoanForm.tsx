import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ChevronLeft, User, Building, CreditCard, Users } from 'lucide-react';
import FormField from '../components/FormField';
import AddressInput, { AddressData } from '@/components/ui/address-input';
import { formatZimbabweId } from '../utils/formatters';
import { zimbabweBanks } from '../data/zimbabweBanks';

interface SSBLoanFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const SSBLoanForm: React.FC<SSBLoanFormProps> = ({ data, onNext, onBack, loading }) => {
    // Calculate credit facility details from product selection
    const calculateCreditFacilityDetails = () => {
        const businessName = data.business; // string from ProductSelection
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

        // Note 7: SSB Loan starts first of next month and ends on last day of start date + loan period
        const today = new Date();
        const firstOfNextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        const loanEndDate = new Date(firstOfNextMonth.getFullYear(), firstOfNextMonth.getMonth() + tenure, 0); // Last day of the end month

        return {
            creditFacilityType: facilityType,
            loanAmount: finalPrice.toFixed(2),
            loanTenure: tenure.toString(),
            monthlyPayment: monthlyPaymentValue.toFixed(2),
            interestRate: '10.0',
            loanStartDate: firstOfNextMonth.toISOString().split('T')[0],
            loanEndDate: loanEndDate.toISOString().split('T')[0],
            firstPaymentDate: firstOfNextMonth.toISOString().split('T')[0]
        };
    };

    const creditDetails = calculateCreditFacilityDetails();
    const businessName = data.business;
    const currentDate = new Date().toISOString().split('T')[0];
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';

    const [hasOtherLoans, setHasOtherLoans] = useState<string>(''); // 'yes' or 'no'
    const [loanType, setLoanType] = useState<string>(''); // 'qupa' | 'other' | 'both'
    const [isCustomBranch, setIsCustomBranch] = useState<boolean>(false);
    const [spouseError, setSpouseError] = useState<string>(''); // Error message for spouse/next of kin validation
    const [employmentError, setEmploymentError] = useState<string>(''); // Error message for employment validation
    const [sameAsCell, setSameAsCell] = useState(false);

    const [formData, setFormData] = useState({
        // Credit Facility Details (pre-populated)
        ...creditDetails,

        // Personal Details
        title: '',
        surname: '',
        firstName: '',
        gender: '',
        dateOfBirth: '', // Will be set by dial-date picker with defaultAge
        maritalStatus: '',
        nationality: 'Zimbabwean',
        idNumber: data.formResponses?.nationalIdNumber || '',
        cellNumber: data.formResponses?.mobile || '',
        whatsApp: '',
        emailAddress: data.formResponses?.emailAddress || '',
        responsibleMinistry: '',
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

        // Spouse and Next of Kin
        spouseDetails: [
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: { type: '', addressLine: '' } as AddressData },
            { fullName: '', relationship: '', phoneNumber: '', residentialAddress: { type: '', addressLine: '' } as AddressData }
        ],

        // Banking Details
        bankName: '',
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
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' },
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' }
        ],

        // Purpose/Asset (auto-populated from product selection)
        purposeAsset: businessName ? `${businessName} - ${data.scale || 'Standard Scale'}` : ''
    });

    const handleInputChange = (field: string, value: string) => {
        let processedValue = field.toLowerCase().includes('idnumber')
            ? formatZimbabweId(value)
            : value;

        if (field === 'employmentNumber') {
            // No specific processing here as we handle combination in onChange events
            processedValue = value;
        }

        setFormData(prev => {
            const updates: any = { [field]: processedValue };

            // Auto-update WhatsApp if same as cell is checked
            if (field === 'cellNumber' && sameAsCell) {
                updates.whatsApp = processedValue;
            }

            return { ...prev, ...updates };
        });
    };

    // Update WhatsApp when checkbox changes
    useEffect(() => {
        if (sameAsCell) {
            handleInputChange('whatsApp', formData.cellNumber);
        }
    }, [sameAsCell]);

    // Auto-set spouse relation if married
    useEffect(() => {
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
        // Clear error when user starts filling in spouse details
        if (spouseError) {
            setSpouseError('');
        }

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

    // Helper methods for data conversion
    const convertPropertyOwnership = (ownership: string) => {
        const mapping: Record<string, string> = {
            'Owned': 'Owned',
            'Rented': 'Rented',
            'Family Property': 'Parents Owned',
            'Company Housing': 'Employer Owned'
        };
        return mapping[ownership] || ownership;
    };

    const convertPeriodToText = (years: string | number) => {
        const numYears = parseInt(years?.toString() || '0');
        if (numYears <= 1) return 'Less than One Year';
        if (numYears <= 2) return 'Between 1-2 years';
        if (numYears <= 5) return 'Between 2-5 years';
        return 'More than 5 years';
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate Employment Number
        const employmentNumberRegex = /^\d{7}[A-Z]$/;
        if (!employmentNumberRegex.test(formData.employmentNumber)) {
            setEmploymentError('Employment Number must be 7 digits followed by a letter (e.g. 1234567A)');
            document.getElementById('employmentNumber')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        } else {
            setEmploymentError('');
        }

        // Validate that BOTH spouse/next of kin entries are filled in
        const allSpousesValid = formData.spouseDetails.every(spouse => {
            const addr = spouse.residentialAddress as AddressData;
            const hasAddress = addr && addr.type && addr.addressLine?.trim();
            return spouse.fullName.trim() !== '' &&
                spouse.relationship.trim() !== '' &&
                spouse.phoneNumber.trim() !== '' &&
                hasAddress;
        });

        if (!allSpousesValid) {
            setSpouseError('Both Spouse/Next of Kin entries are required. Please fill in all fields for both contacts.');
            // Scroll to the spouse section
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Validate that next of kin don't have the same name
        if (formData.spouseDetails[0].fullName.trim().toLowerCase() === formData.spouseDetails[1].fullName.trim().toLowerCase()) {
            setSpouseError('Both Spouse/Next of Kin cannot have the same name. Please provide different contacts.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Validate that next of kin don't have the same phone number
        if (formData.spouseDetails[0].phoneNumber.trim() === formData.spouseDetails[1].phoneNumber.trim()) {
            setSpouseError('Both Spouse/Next of Kin cannot have the same phone number. Please provide different contacts.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Validate that supervisor phone is different from next of kin phones
        const supervisorPhone = formData.headOfInstitutionCell.trim();
        const nextOfKinPhones = formData.spouseDetails.map(s => s.phoneNumber.trim());

        if (supervisorPhone && nextOfKinPhones.includes(supervisorPhone)) {
            setSpouseError('Supervisor phone number cannot be the same as Next of Kin phone numbers.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Validate that personal cell phone is different from next of kin phones (WhatsApp can be same)
        const personalPhone = formData.cellNumber.trim();

        if (personalPhone && nextOfKinPhones.includes(personalPhone)) {
            setSpouseError('Your personal phone number cannot be the same as Next of Kin phone numbers.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Clear any existing error
        setSpouseError('');

        // Map SSB form fields to match validation expectations
        const formResponses: Record<string, any> = {
            ...formData,
        };

        formResponses.firstName = formData.firstName;
        formResponses.surname = formData.surname;
        formResponses.dateOfBirth = formData.dateOfBirth;
        formResponses.gender = formData.gender;
        formResponses.nationalIdNumber = formData.idNumber;
        formResponses.mobile = formData.cellNumber;
        formResponses.emailAddress = formData.emailAddress;
        formResponses.employeeNumber = formData.employmentNumber;
        formResponses.ministry = formData.responsibleMinistry;
        formResponses.netSalary = formData.currentNetSalary;
        formResponses.responsiblePaymaster = formData.headOfInstitution;
        formResponses.responsibleMinistry = formData.responsibleMinistry;
        formResponses.loanAmount = formData.loanAmount;
        formResponses.loanTenure = formData.loanTenure;
        // Serialize address to JSON for PDF template
        formResponses.residentialAddress = JSON.stringify(formData.permanentAddress);
        formResponses.permanentAddress = JSON.stringify(formData.permanentAddress);
        formResponses.checkLetter = '';
        formResponses.propertyOwnership = convertPropertyOwnership(formData.propertyOwnership);
        formResponses.periodAtAddress = convertPeriodToText(formData.periodAtAddress);
        // Serialize spouse addresses to JSON for PDF template
        formResponses.spouseDetails = formData.spouseDetails.map(spouse => ({
            ...spouse,
            residentialAddress: JSON.stringify(spouse.residentialAddress)
        }));

        // Add hasOtherLoans flag and loan data
        formResponses.hasOtherLoans = hasOtherLoans;
        formResponses.loanType = loanType;
        formResponses.qupaLoan = formData.qupaLoan;
        formResponses.otherInstitutionLoan = formData.otherInstitutionLoan;

        if (hasOtherLoans === 'no') {
            formResponses.otherLoans = [
                { institution: 'N/A', monthlyInstallment: 'N/A', currentBalance: 'N/A', maturityDate: 'N/A' },
                { institution: 'N/A', monthlyInstallment: 'N/A', currentBalance: 'N/A', maturityDate: 'N/A' }
            ];
        } else {
            // Map new loan structure to legacy format for backward compatibility
            formResponses.otherLoans = formData.otherLoans;
        }

        const mappedData = {
            formResponses,
            documents: {
                uploadedDocuments: {
                    national_id: [],
                    payslip: [],
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
                    documentTypes: ['national_id', 'payslip', 'employment_letter']
                }
            },
            formType: 'ssb'
        };

        onNext(mappedData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">SSB Loan Application Form</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Complete your SSB loan application details
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

                        <FormField
                            id="surname"
                            label="Surname"
                            type="text"
                            value={formData.surname}
                            onChange={(value) => handleInputChange('surname', value)}
                            autoCapitalize={true}
                            required
                        />

                        <FormField
                            id="firstName"
                            label="First Name"
                            type="text"
                            value={formData.firstName}
                            onChange={(value) => handleInputChange('firstName', value)}
                            autoCapitalize={true}
                            required
                        />

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

                        <FormField
                            id="dateOfBirth"
                            label="Date of Birth"
                            type="dial-date"
                            value={formData.dateOfBirth}
                            onChange={(value) => handleInputChange('dateOfBirth', value)}
                            maxDate={`${new Date().getFullYear() - 18}-12-31`}
                            minDate="1930-01-01"
                            defaultAge={20}
                            showAgeValidation={false}
                            required
                        />

                        <div>
                            <Label htmlFor="maritalStatus">Marital Status</Label>
                            <Select value={formData.maritalStatus} onValueChange={(value) => handleInputChange('maritalStatus', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select marital status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Single">Single</SelectItem>
                                    <SelectItem value="Married">Married</SelectItem>
                                    <SelectItem value="Divorced">Divorced</SelectItem>
                                    <SelectItem value="Widowed">Widowed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <FormField
                            id="nationality"
                            label="Nationality"
                            type="text"
                            value={formData.nationality}
                            onChange={(value) => handleInputChange('nationality', value)}
                            autoCapitalize={true}
                            required
                        />

                        <FormField
                            id="idNumber"
                            label="ID Number"
                            type="text"
                            value={formData.idNumber}
                            onChange={(value) => handleInputChange('idNumber', value)}
                            capitalizeCheckLetter={true}
                            placeholder="e.g. 12-345678 A 12"
                            title="Zimbabwe ID format: 12-345678 A 12"
                            required
                            readOnly={!!data.formResponses?.nationalIdNumber}

                        />

                        <FormField
                            id="cellNumber"
                            label="Cell Number"
                            type="phone"
                            value={formData.cellNumber}
                            onChange={(value) => handleInputChange('cellNumber', value)}
                            required
                        />

                        <div>
                            <div className="flex items-center space-x-2 mb-2">
                                <Label htmlFor="whatsApp" className="mb-0">WhatsApp Number</Label>
                                <div className="flex items-center space-x-2 ml-4">
                                    <Checkbox
                                        id="sameAsCell"
                                        checked={sameAsCell}
                                        onCheckedChange={(checked) => setSameAsCell(checked as boolean)}
                                    />
                                    <label
                                        htmlFor="sameAsCell"
                                        className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-500"
                                    >
                                        Same as Cell Number
                                    </label>
                                </div>
                            </div>
                            <FormField
                                id="whatsApp"
                                label=""
                                type="phone"
                                value={formData.whatsApp}
                                onChange={(value) => {
                                    handleInputChange('whatsApp', value);
                                    if (sameAsCell && value !== formData.cellNumber) {
                                        setSameAsCell(false);
                                    }
                                }}
                            />
                        </div>

                        <FormField
                            id="emailAddress"
                            label="Email Address"
                            type="email"
                            value={formData.emailAddress}
                            onChange={(value) => handleInputChange('emailAddress', value)}
                            required
                        />

                        <div className="md:col-span-2 lg:col-span-3">
                            <AddressInput
                                id="permanentAddress"
                                label="Residential Address"
                                value={formData.permanentAddress}
                                onChange={(value) => setFormData(prev => ({ ...prev, permanentAddress: value }))}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="propertyOwnership">Accommodation Status</Label>
                            <Select value={formData.propertyOwnership} onValueChange={(value) => handleInputChange('propertyOwnership', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select accommodation" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Owned">Owned</SelectItem>
                                    <SelectItem value="Rented">Rented</SelectItem>
                                    <SelectItem value="Family Property">Family Property</SelectItem>
                                    <SelectItem value="Company Housing">Company Housing</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <FormField
                            id="periodAtAddress"
                            label="Period at Address (Years)"
                            type="number"
                            value={formData.periodAtAddress}
                            onChange={(value) => handleInputChange('periodAtAddress', value)}
                        />

                        <div className="md:col-span-2 lg:col-span-3">
                            <Label htmlFor="responsibleMinistry">You are employed by which Ministry *</Label>
                            <Select value={formData.responsibleMinistry} onValueChange={(value) => handleInputChange('responsibleMinistry', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Ministry" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Primary and Secondary Education">Primary and Secondary Education</SelectItem>
                                    <SelectItem value="Health and Child Care">Health and Child Care</SelectItem>
                                    <SelectItem value="Home Affairs and Cultural Heritage">Home Affairs and Cultural Heritage</SelectItem>
                                    <SelectItem value="Justice">Justice</SelectItem>
                                    <SelectItem value="Agriculture">Agriculture</SelectItem>
                                    <SelectItem value="Energy and Power Development">Energy and Power Development</SelectItem>
                                    <SelectItem value="Environment">Environment</SelectItem>
                                    <SelectItem value="Finance and Economic Development">Finance and Economic Development</SelectItem>
                                    <SelectItem value="Foreign Affairs and International Trade">Foreign Affairs and International Trade</SelectItem>
                                    <SelectItem value="Higher and Tertiary Education">Higher and Tertiary Education</SelectItem>
                                    <SelectItem value="ICT">ICT</SelectItem>
                                    <SelectItem value="Industry and Commerce">Industry and Commerce</SelectItem>
                                    <SelectItem value="Lands and Agriculture">Lands and Agriculture</SelectItem>
                                    <SelectItem value="Local Government">Local Government</SelectItem>
                                    <SelectItem value="Mines and Mining Development">Mines and Mining Development</SelectItem>
                                    <SelectItem value="National Housing">National Housing</SelectItem>
                                    <SelectItem value="Public Service">Public Service</SelectItem>
                                    <SelectItem value="Tourism and Hospitality">Tourism and Hospitality</SelectItem>
                                    <SelectItem value="Transport and Infrastructure">Transport and Infrastructure</SelectItem>
                                    <SelectItem value="Women Affairs">Women Affairs</SelectItem>
                                    <SelectItem value="Youth, Sport, Arts and Recreation">Youth, Sport, Arts and Recreation</SelectItem>
                

                                </SelectContent>
                            </Select>
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
                        <FormField
                            id="employerName"
                            label="Name of Institution"
                            type="text"
                            value={formData.employerName}
                            onChange={(value) => handleInputChange('employerName', value)}
                            autoCapitalize={true}
                            required
                        />

                        <FormField
                            id="employerAddress"
                            label="Institution Address"
                            type="text"
                            value={typeof formData.employerAddress === 'string' ? formData.employerAddress : ''}
                            onChange={(value) => handleInputChange('employerAddress', value)}
                            required
                        />

                        <div>
                            <Label htmlFor="employmentStatus">Employment Status</Label>
                            <Select value={formData.employmentStatus} onValueChange={(value) => handleInputChange('employmentStatus', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select employment status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Permanent">Permanent</SelectItem>
                                    <SelectItem value="Contract">Contract</SelectItem>
                                    <SelectItem value="Temporary">Temporary</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <FormField
                            id="jobTitle"
                            label="Job Title"
                            type="text"
                            value={formData.jobTitle}
                            onChange={(value) => handleInputChange('jobTitle', value)}
                            autoCapitalize={true}
                            required
                        />

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

                        <div>
                            <Label htmlFor="employmentNumber">Employment Number *</Label>
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
                                    required
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
                                    required
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
                                    <SelectItem value="10-50">{currencySymbol}10 - {currencySymbol}50</SelectItem>
                                    <SelectItem value="51-100">{currencySymbol}51 - {currencySymbol}100</SelectItem>
                                    <SelectItem value="101-200">{currencySymbol}101 - {currencySymbol}200</SelectItem>
                                    <SelectItem value="201-300">{currencySymbol}201 - {currencySymbol}300</SelectItem>
                                    <SelectItem value="300+">{currencySymbol}300+</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <FormField
                            id="headOfInstitution"
                            label="Name of Immediate Supervisor"
                            type="text"
                            value={formData.headOfInstitution}
                            onChange={(value) => handleInputChange('headOfInstitution', value)}
                            autoCapitalize={true}
                            required
                        />

                        <FormField
                            id="headOfInstitutionCell"
                            label="Cell No of Immediate Supervisor"
                            type="phone"
                            value={formData.headOfInstitutionCell}
                            onChange={(value) => handleInputChange('headOfInstitutionCell', value)}
                            required
                        />
                    </div>
                </Card>

                {/* Spouse and Next of Kin */}
                <Card className="p-6" id="spouse-section">
                    <div className="flex items-center mb-4">
                        <Users className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">
                            Next of Kin Details *
                        </h3>
                    </div>
                    <p className="text-xs text-gray-500 italic mb-4">
                        *this is for statistical and record keeping purposes only*
                    </p>

                    {spouseError && (
                        <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p className="text-sm text-red-600 dark:text-red-400 font-medium">
                                {spouseError}
                            </p>
                        </div>
                    )}

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
                                <FormField
                                    id={`spouse-${index}-name`}
                                    label={`Full Name${index === 0 ? ' *' : ''}`}
                                    type="text"
                                    value={spouse.fullName}
                                    onChange={(value) => handleSpouseChange(index, 'fullName', value)}
                                    autoCapitalize={true}
                                    required={index === 0}
                                />

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

                                <FormField
                                    id={`spouse-${index}-phone`}
                                    label={`Phone Number${index === 0 && spouse.fullName ? ' *' : ''}`}
                                    type="phone"
                                    value={spouse.phoneNumber}
                                    onChange={(value) => handleSpouseChange(index, 'phoneNumber', value)}
                                    required={index === 0 && !!spouse.fullName}
                                />

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

                {/* Banking Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Banking Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="bankName">Bank Name *</Label>
                            <Select
                                value={formData.bankName}
                                onValueChange={(value) => {
                                    handleInputChange('bankName', value);
                                    handleInputChange('branch', ''); // Reset branch when bank changes
                                    setIsCustomBranch(false); // Reset custom branch mode
                                }}
                                required
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
                            <Label htmlFor="branch">Branch *</Label>
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
                                    required
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
                                        required
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

                        <FormField
                            id="accountNumber"
                            label="Account Number"
                            type="text"
                            value={formData.accountNumber}
                            onChange={(value) => handleInputChange('accountNumber', value)}
                            required
                        />
                    </div>
                </Card>

                {/* Loans with Other Institutions */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-emerald-600 mr-3" />
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
                                    className="border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Price/Applied Amount ({selectedCurrency})</Label>
                                <div className="relative">
                                    {isZiG ? (
                                        <span className="absolute left-3 top-2.5 text-gray-500 text-xs font-bold pt-0.5">ZiG</span>
                                    ) : (
                                        <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    )}
                                    <Input
                                        value={formData.loanAmount}
                                        readOnly
                                        className={`border-gray-200 dark:border-gray-600 ${isZiG ? 'pl-10' : 'pl-8'}`}
                                    />
                                </div>
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Period (Months)</Label>
                                <Input
                                    value={`${formData.loanTenure} months`}
                                    readOnly
                                    className="border-gray-200 dark:border-gray-600"
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
                                        className={`border-gray-200 dark:border-gray-600 ${isZiG ? 'pl-10' : 'pl-8'}`}
                                    />
                                </div>
                            </div>
                            {/* Hidden Interest Rate */}
                            <input
                                type="hidden"
                                value={`${formData.interestRate}%`}
                                readOnly
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
                        {loading ? 'Submitting...' : 'Agree & Submit Application'}
                    </Button>
                </div>
            </form>
        </div >
    );
};

export default SSBLoanForm;
