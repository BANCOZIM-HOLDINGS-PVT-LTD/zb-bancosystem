import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ChevronLeft, User, Building, CreditCard, Users, FileText } from 'lucide-react';
import FormField from '../components/FormField';
import AddressInput, { AddressData } from '@/components/ui/address-input';
import { formatZimbabweId } from '../utils/formatters';
import { zimbabweBanks } from '../data/zimbabweBanks';

interface SSBLoanFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
    onSaveProgress?: (rawData: any) => void;
}

const PensionerLoanForm: React.FC<SSBLoanFormProps> = ({ data, onNext, onBack, loading, onSaveProgress }) => {
    const calculateCreditFacilityDetails = () => {
        const businessName = data.business;
        const finalPrice = data.amount || 0;
        const intent = data.intent || 'hirePurchase';
        const selectedMonth = data.creditTerm;

        let facilityType = '';
        if (intent === 'hirePurchase' && businessName) {
            facilityType = `Hire Purchase Credit - ${businessName}`;
        } else if ((intent === 'microBiz' || intent === 'microBizLoan' || intent === 'smeBiz') && businessName) {
            facilityType = `Micro Biz Loan - ${businessName}`;
        } else if (businessName) {
            facilityType = `Credit Facility - ${businessName}`;
        }

        let tenure = 12;
        if (selectedMonth) {
            tenure = parseInt(selectedMonth.toString());
        } else {
            if (finalPrice <= 1000) tenure = 6;
            else if (finalPrice <= 5000) tenure = 12;
            else if (finalPrice <= 15000) tenure = 18;
            else tenure = 24;
        }

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

        const today = new Date();
        const firstOfNextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        const loanEndDate = new Date(firstOfNextMonth.getFullYear(), firstOfNextMonth.getMonth() + tenure, 0);

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

    const _saved = data._rawFormData?._formType === 'pensionerLoan' ? data._rawFormData : null;

    const [hasOtherLoans, setHasOtherLoans] = useState<string>(_saved?.hasOtherLoans ?? '');
    const [loanType, setLoanType] = useState<string>(_saved?.loanType ?? '');
    const [isCustomBranch, setIsCustomBranch] = useState<boolean>(false);
    const [spouseError, setSpouseError] = useState<string>('');
    const [accountNumberError, setAccountNumberError] = useState<string>('');
    const [sameAsCell, setSameAsCell] = useState(false);
    const [declarationError, setDeclarationError] = useState<string>('');

    // Declaration checkboxes
    const [agreedToTerms, setAgreedToTerms] = useState<boolean>(_saved?.agreedToTerms ?? false);
    const [authorizedCreditCheck, setAuthorizedCreditCheck] = useState<boolean>(_saved?.authorizedCreditCheck ?? false);
    const [authorizedDebitOrder, setAuthorizedDebitOrder] = useState<boolean>(_saved?.authorizedDebitOrder ?? false);

    const [formData, setFormData] = useState(_saved?.formData ?? {
        // Credit Facility Details (pre-populated)
        ...creditDetails,

        // Applicant Details
        title: '',
        surname: '',
        firstName: '',
        gender: '',
        dateOfBirth: '',
        maritalStatus: '',
        nationality: 'Zimbabwean',
        idNumber: data.formResponses?.nationalIdNumber || '',
        cellNumber: data.formResponses?.mobile || '',
        whatsApp: '',
        emailAddress: data.formResponses?.emailAddress || '',
        permanentAddress: { type: '', addressLine: '' } as AddressData,
        propertyOwnership: '',
        periodAtAddress: '',

        // Pension/Employment History
        responsibleMinistry: '',
        pensionNssaNumber: '',
        lengthOfService: '',
        retirementDate: '',
        monthlyPension: '',
        pensionPayDay: '',

        // Next of Kin
        spouseDetails: [
            { fullName: '', relationship: '', idNumber: '', phoneNumber: '', residentialAddress: { type: '', addressLine: '' } as AddressData }
        ],

        // Banking Details
        bankName: '',
        branch: '',
        accountNumber: '',
        mobileWalletProvider: '',
        mobileWalletNumber: '',

        // Other Loans
        qupaLoan: {
            maturityDate: '',
            monthlyInstallment: '',
        },
        otherInstitutionLoan: {
            institutionName: '',
            maturityDate: '',
            monthlyInstallment: '',
        },
        otherLoans: [
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' },
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' }
        ],

        purposeAsset: businessName ? `${businessName} - ${data.scale || 'Standard Scale'}` : ''
    });

    const handleBackWithSave = () => {
        onSaveProgress?.({ _formType: 'pensionerLoan', formData, hasOtherLoans, loanType, agreedToTerms, authorizedCreditCheck, authorizedDebitOrder });
        onBack();
    };

    const handleInputChange = (field: string, value: string) => {
        let processedValue = field.toLowerCase().includes('idnumber')
            ? formatZimbabweId(value)
            : value;

        if (field === 'accountNumber' && formData.bankName === 'ZB Bank') {
            setAccountNumberError('');
            if (value) {
                if (!/^\d{15}$/.test(value)) {
                    setAccountNumberError('ZB Bank account number must be exactly 15 digits');
                } else if (value[0] !== '4') {
                    setAccountNumberError('ZB Bank account number must start with 4');
                } else if (isZiG ? value[12] !== '2' : value[12] !== '4') {
                    setAccountNumberError(isZiG
                        ? 'Please enter your ZiG ZB Bank account number'
                        : 'Please enter your USD ZB Bank account number');
                }
            }
        }

        if (field === 'bankName' && value !== 'ZB Bank') {
            setAccountNumberError('');
        }

        setFormData(prev => {
            const updates: any = { [field]: processedValue };
            if (field === 'cellNumber' && sameAsCell) {
                updates.whatsApp = processedValue;
            }
            return { ...prev, ...updates };
        });
    };

    useEffect(() => {
        if (sameAsCell) {
            handleInputChange('whatsApp', formData.cellNumber);
        }
    }, [sameAsCell]);

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
        if (spouseError) setSpouseError('');
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

        // Validate ZB Bank account number on submit
        if (formData.bankName === 'ZB Bank' && formData.accountNumber) {
            if (!/^\d{15}$/.test(formData.accountNumber)) {
                setAccountNumberError('ZB Bank account number must be exactly 15 digits');
                document.getElementById('accountNumber')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (formData.accountNumber[0] !== '4') {
                setAccountNumberError('ZB Bank account number must start with 4');
                document.getElementById('accountNumber')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (isZiG ? formData.accountNumber[12] !== '2' : formData.accountNumber[12] !== '4') {
                setAccountNumberError(isZiG
                    ? 'Please enter your ZiG ZB Bank account number'
                    : 'Please enter your USD ZB Bank account number');
                document.getElementById('accountNumber')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }

        // Validate next of kin
        const nok = formData.spouseDetails[0];
        if (!nok || !nok.fullName.trim() || !nok.relationship.trim() || !nok.phoneNumber.trim()) {
            setSpouseError('Next of Kin details are required. Please fill in all fields.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const applicantFullName = `${formData.firstName} ${formData.surname}`.trim().toLowerCase();
        if (nok.fullName.trim().toLowerCase() === applicantFullName) {
            setSpouseError('Next of Kin cannot be the same as the applicant.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const nokPhone = nok.phoneNumber.trim();
        const personalPhone = formData.cellNumber.trim();
        if (personalPhone && nokPhone === personalPhone) {
            setSpouseError('Next of Kin phone number cannot be the same as your personal phone number.');
            document.getElementById('spouse-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        setSpouseError('');

        // Validate declarations
        if (!agreedToTerms || !authorizedCreditCheck || !authorizedDebitOrder) {
            setDeclarationError('You must agree to all declarations before submitting.');
            document.getElementById('declaration-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        setDeclarationError('');

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
        // Map pensioner fields to backend-expected keys
        formResponses.employeeNumber = formData.pensionNssaNumber;
        formResponses.ministry = formData.responsibleMinistry;
        formResponses.responsibleMinistry = formData.responsibleMinistry;
        formResponses.netSalary = formData.monthlyPension;
        formResponses.payDayRange = formData.pensionPayDay;
        formResponses.jobTitle = `Retired - ${formData.lengthOfService} years service`;
        formResponses.dateOfEmployment = formData.retirementDate;
        formResponses.loanAmount = formData.loanAmount;
        formResponses.loanTenure = formData.loanTenure;
        formResponses.residentialAddress = JSON.stringify(formData.permanentAddress);
        formResponses.permanentAddress = JSON.stringify(formData.permanentAddress);
        formResponses.checkLetter = '';
        formResponses.propertyOwnership = convertPropertyOwnership(formData.propertyOwnership);
        formResponses.periodAtAddress = convertPeriodToText(formData.periodAtAddress);
        formResponses.mobileWalletProvider = formData.mobileWalletProvider;
        formResponses.mobileWalletNumber = formData.mobileWalletNumber;
        formResponses.spouseDetails = formData.spouseDetails.map(spouse => ({
            ...spouse,
            residentialAddress: JSON.stringify(spouse.residentialAddress)
        }));

        formResponses.hasOtherLoans = hasOtherLoans;
        formResponses.loanType = loanType;
        formResponses.qupaLoan = formData.qupaLoan;
        formResponses.otherInstitutionLoan = formData.otherInstitutionLoan;
        formResponses.agreedToTerms = agreedToTerms;
        formResponses.authorizedCreditCheck = authorizedCreditCheck;
        formResponses.authorizedDebitOrder = authorizedDebitOrder;

        if (hasOtherLoans === 'no') {
            formResponses.otherLoans = [
                { institution: 'N/A', monthlyInstallment: 'N/A', currentBalance: 'N/A', maturityDate: 'N/A' },
                { institution: 'N/A', monthlyInstallment: 'N/A', currentBalance: 'N/A', maturityDate: 'N/A' }
            ];
        } else {
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
            formType: 'pensioner',
            _rawFormData: { _formType: 'pensionerLoan', formData, hasOtherLoans, loanType, agreedToTerms, authorizedCreditCheck, authorizedDebitOrder }
        };

        onNext(mappedData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Government Pensioner Loan Form</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Complete your pensioner loan application details
                </p>
                <p className="text-sm text-red-600 dark:text-red-400">
                    Fields marked with * are required
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">

                {/* Applicant Details */}
                <Card className="p-4 md:p-6">
                    <div className="flex items-center mb-4">
                        <User className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Applicant Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <Label htmlFor="title">Title</Label>
                            <Select value={formData.title} onValueChange={(value: string) => handleInputChange('title', value)}>
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

                        <FormField
                            id="idNumber"
                            label="National ID Number"
                            type="text"
                            value={formData.idNumber}
                            onChange={(value) => handleInputChange('idNumber', value)}
                            capitalizeCheckLetter={true}
                            placeholder="e.g. 08-1234567A-08"
                            title="Zimbabwe ID format: 08-1234567A-08"
                            required
                            readOnly={!!data.formResponses?.nationalIdNumber}
                        />

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
                            <Label htmlFor="gender">Gender *</Label>
                            <Select value={formData.gender} onValueChange={(value: string) => handleInputChange('gender', value)} required>
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
                            <Label htmlFor="maritalStatus">Marital Status</Label>
                            <Select value={formData.maritalStatus} onValueChange={(value: string) => handleInputChange('maritalStatus', value)}>
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
                            id="cellNumber"
                            label="Cell Number"
                            type="phone"
                            value={formData.cellNumber}
                            onChange={(value) => handleInputChange('cellNumber', value)}
                            required
                        />

                        <div>
                            <div className="flex flex-wrap items-center gap-2 mb-2">
                                <Label htmlFor="whatsApp" className="mb-0">WhatsApp Number</Label>
                                <div className="flex items-center space-x-2 ml-0 sm:ml-4">
                                    <Checkbox
                                        id="sameAsCell"
                                        checked={sameAsCell}
                                        onCheckedChange={(checked: boolean) => setSameAsCell(checked as boolean)}
                                    />
                                    <label
                                        htmlFor="sameAsCell"
                                        style={{
                                            fontWeight: 500,
                                            lineHeight: 1,
                                            fontSize: '0.875rem',
                                            cursor: 'pointer',
                                            color: '#6b7280'
                                        }}
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
                                label="Physical Home Address"
                                value={formData.permanentAddress}
                                onChange={(value) => setFormData(prev => ({ ...prev, permanentAddress: value }))}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="propertyOwnership">Accommodation Status</Label>
                            <Select value={formData.propertyOwnership} onValueChange={(value: string) => handleInputChange('propertyOwnership', value)}>
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
                    </div>
                </Card>

                {/* Pension/Employment History */}
                <Card className="p-4 md:p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Pension / Employment History</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <Label htmlFor="responsibleMinistry">Ministry or Department Previously Worked For *</Label>
                            <Select value={formData.responsibleMinistry} onValueChange={(value: string) => handleInputChange('responsibleMinistry', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Ministry / Department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Primary and Secondary Education">Primary and Secondary Education</SelectItem>
                                    <SelectItem value="Health and Child Care">Health and Child Care</SelectItem>
                                    <SelectItem value="Home Affairs and Cultural Heritage">Home Affairs and Cultural Heritage</SelectItem>
                                    <SelectItem value="Justice">Justice, Legal and Parliamentary Affairs</SelectItem>
                                    <SelectItem value="Defence and War Veterans">Defence and War Veterans</SelectItem>
                                    <SelectItem value="State Security">State Security</SelectItem>
                                    <SelectItem value="Agriculture">Lands, Agriculture, Fisheries, Water and Rural Development</SelectItem>
                                    <SelectItem value="Energy and Power Development">Energy and Power Development</SelectItem>
                                    <SelectItem value="Environment">Environment, Climate, Tourism and Hospitality Industry</SelectItem>
                                    <SelectItem value="Finance and Economic Development">Finance, Economic Development and Investment Promotion</SelectItem>
                                    <SelectItem value="Foreign Affairs and International Trade">Foreign Affairs and International Trade</SelectItem>
                                    <SelectItem value="Higher and Tertiary Education">Higher and Tertiary Education, Innovation, Science and Technology Development</SelectItem>
                                    <SelectItem value="ICT">ICT, Postal and Courier Services</SelectItem>
                                    <SelectItem value="Industry and Commerce">Industry and Commerce</SelectItem>
                                    <SelectItem value="Lands and Agriculture">Lands and Agriculture</SelectItem>
                                    <SelectItem value="Local Government">Local Government and Public Works</SelectItem>
                                    <SelectItem value="Mines and Mining Development">Mines and Mining Development Development</SelectItem>
                                    <SelectItem value="National Housing">National Housing and Social Amenities</SelectItem>
                                    <SelectItem value="Public Service">Public Service, Labour and Social Welfare</SelectItem>
                                    <SelectItem value="Tourism and Hospitality">Tourism and Hospitality</SelectItem>
                                    <SelectItem value="Transport and Infrastructure">Transport and Infrastructure</SelectItem>
                                    <SelectItem value="Women Affairs">Women Affairs, Community Development and Gender Equality</SelectItem>
                                    <SelectItem value="Youth, Sport, Arts and Recreation">Youth, Sport, Arts and Recreation</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <FormField
                            id="pensionNssaNumber"
                            label="Pension / NSSA Number"
                            type="text"
                            value={formData.pensionNssaNumber}
                            onChange={(value) => handleInputChange('pensionNssaNumber', value)}
                            placeholder="e.g. P12345678"
                            required
                        />

                        <FormField
                            id="lengthOfService"
                            label="Length of Service (Years)"
                            type="number"
                            value={formData.lengthOfService}
                            onChange={(value) => handleInputChange('lengthOfService', value)}
                            placeholder="e.g. 25"
                            required
                        />

                        <FormField
                            id="retirementDate"
                            label="Retirement Date"
                            type="dial-date"
                            value={formData.retirementDate}
                            onChange={(value) => handleInputChange('retirementDate', value)}
                            maxDate={currentDate}
                            defaultAge={0}
                            required
                            hideDay={true}
                        />

                        <div>
                            <Label htmlFor="monthlyPension">Monthly Pension Amount ({selectedCurrency}) *</Label>
                            <Select value={formData.monthlyPension} onValueChange={(value: string) => handleInputChange('monthlyPension', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select pension range" />
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

                        <div className="md:col-span-2">
                            <Label htmlFor="pensionPayDay">Monthly Pension Pay Day Range *</Label>
                            <Select value={formData.pensionPayDay} onValueChange={(value: string) => handleInputChange('pensionPayDay', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select your pension pay day range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="week1">I usually receive my pension in the first week (1st - 7th)</SelectItem>
                                    <SelectItem value="week2">I usually receive my pension in the second week (8th - 15th)</SelectItem>
                                    <SelectItem value="week3">I usually receive my pension in the third week (16th - 21st)</SelectItem>
                                    <SelectItem value="week4">I usually receive my pension after the 22nd</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </Card>

                {/* Banking Details */}
                <Card className="p-4 md:p-6">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Banking Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="bankName">Bank Name *</Label>
                            <Select
                                value={formData.bankName}
                                onValueChange={(value: string) => {
                                    handleInputChange('bankName', value);
                                    handleInputChange('branch', '');
                                    setIsCustomBranch(false);
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
                            <Label htmlFor="branch">Branch Name *</Label>
                            {!isCustomBranch ? (
                                <Select
                                    value={formData.branch}
                                    onValueChange={(value: string) => {
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
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleInputChange('branch', e.target.value)}
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
                            label="ZB Bank Account Number"
                            type="text"
                            value={formData.accountNumber}
                            onChange={(value) => handleInputChange('accountNumber', value)}
                            required
                            error={accountNumberError}
                        />

                        <div>
                            <Label htmlFor="mobileWalletProvider">Mobile Wallet Provider</Label>
                            <Select value={formData.mobileWalletProvider} onValueChange={(value: string) => handleInputChange('mobileWalletProvider', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select provider" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="EcoCash">EcoCash</SelectItem>
                                    <SelectItem value="OneMoney">OneMoney</SelectItem>
                                    <SelectItem value="InnBucks">InnBucks</SelectItem>
                                    <SelectItem value="ZimSwitch">ZimSwitch</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="md:col-span-2">
                            <FormField
                                id="mobileWalletNumber"
                                label="Mobile Wallet Number (for loan disbursement)"
                                type="phone"
                                value={formData.mobileWalletNumber}
                                onChange={(value) => handleInputChange('mobileWalletNumber', value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* Next of Kin Details */}
                <Card className="p-4 md:p-6" id="spouse-section">
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
                        if (formData.maritalStatus === 'Married') {
                            if (formData.gender === 'Male') containerLabel = "Wife's / Next of Kin Details";
                            else if (formData.gender === 'Female') containerLabel = "Husband's / Next of Kin Details";
                            else containerLabel = "Spouse / Next of Kin Details";
                        }

                        return (
                            <div key={index} className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-4 p-4 border rounded-lg">
                                <div className="md:col-span-2 lg:col-span-4 mb-2">
                                    <h4 className="text-md font-medium text-emerald-800 dark:text-emerald-400 border-b pb-1">
                                        {containerLabel}
                                    </h4>
                                </div>

                                <FormField
                                    id={`spouse-${index}-name`}
                                    label="Full Name *"
                                    type="text"
                                    value={spouse.fullName}
                                    onChange={(value) => handleSpouseChange(index, 'fullName', value)}
                                    autoCapitalize={true}
                                    required
                                />

                                <div>
                                    <Label htmlFor={`spouse-${index}-relationship`}>Relationship *</Label>
                                    <Select
                                        value={spouse.relationship}
                                        onValueChange={(value: string) => handleSpouseChange(index, 'relationship', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select relationship" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Spouse">Spouse</SelectItem>
                                            <SelectItem value="Child">Child</SelectItem>
                                            <SelectItem value="Parent">Parent</SelectItem>
                                            <SelectItem value="Relative">Relative</SelectItem>
                                            <SelectItem value="Work colleague">Work colleague</SelectItem>
                                            <SelectItem value="Friend">Friend</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <FormField
                                    id={`spouse-${index}-idNumber`}
                                    label="ID Number"
                                    type="text"
                                    value={spouse.idNumber || ''}
                                    onChange={(value) => handleSpouseChange(index, 'idNumber', formatZimbabweId(value))}
                                    placeholder="e.g. 08-1234567A-08"
                                />

                                <FormField
                                    id={`spouse-${index}-phone`}
                                    label="Contact Number *"
                                    type="phone"
                                    value={spouse.phoneNumber}
                                    onChange={(value) => handleSpouseChange(index, 'phoneNumber', value)}
                                    required
                                />

                                <div className="md:col-span-2 lg:col-span-4">
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
                                        required
                                    />
                                </div>
                            </div>
                        );
                    })}
                </Card>

                {/* Existing Loans */}
                <Card className="p-4 md:p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Existing Loans</h3>
                    </div>

                    <div className="mb-4">
                        <Label htmlFor="hasOtherLoans">Do you have a loan with any other financial institution? *</Label>
                        <Select
                            value={hasOtherLoans}
                            onValueChange={(value: string) => {
                                setHasOtherLoans(value);
                                if (value === 'no') setLoanType('');
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

                    {hasOtherLoans === 'yes' && (
                        <div className="mb-4">
                            <Label htmlFor="loanType">Is it Qupa, Other Institution, or Both? *</Label>
                            <Select value={loanType} onValueChange={(value: string) => setLoanType(value)} required>
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

                {/* Credit Application Details */}
                <Card className="p-4 md:p-6 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Credit Application Details</h3>
                    </div>

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
                            <input type="hidden" value={`${formData.interestRate}%`} readOnly />
                        </div>
                    </div>

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

                {/* Declaration & Signatures */}
                <Card className="p-4 md:p-6" id="declaration-section">
                    <div className="flex items-center mb-4">
                        <FileText className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Declaration & Signatures</h3>
                    </div>

                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        By submitting this application, I declare that all information provided is true and correct to the best of my knowledge. I further agree to the following:
                    </p>

                    {declarationError && (
                        <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p className="text-sm text-red-600 dark:text-red-400 font-medium">
                                {declarationError}
                            </p>
                        </div>
                    )}

                    <div className="space-y-4">
                        <div className="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <Checkbox
                                id="agreedToTerms"
                                checked={agreedToTerms}
                                onCheckedChange={(checked: boolean) => setAgreedToTerms(checked as boolean)}
                                className="mt-0.5"
                            />
                            <label htmlFor="agreedToTerms" className="text-sm text-gray-700 dark:text-gray-300 cursor-pointer leading-relaxed">
                                <span className="font-medium">Terms & Conditions:</span> I have read, understood, and agree to the terms and conditions governing this pensioner loan facility, including the applicable interest rates, fees, and repayment obligations.
                            </label>
                        </div>

                        <div className="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <Checkbox
                                id="authorizedCreditCheck"
                                checked={authorizedCreditCheck}
                                onCheckedChange={(checked: boolean) => setAuthorizedCreditCheck(checked as boolean)}
                                className="mt-0.5"
                            />
                            <label htmlFor="authorizedCreditCheck" className="text-sm text-gray-700 dark:text-gray-300 cursor-pointer leading-relaxed">
                                <span className="font-medium">Credit & Reference Check Authorization:</span> I authorize ZB Bank to conduct credit and reference checks on my behalf, including enquiries with the Credit Registry of Zimbabwe and any other relevant financial or reference institutions, for the purpose of assessing this loan application.
                            </label>
                        </div>

                        <div className="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <Checkbox
                                id="authorizedDebitOrder"
                                checked={authorizedDebitOrder}
                                onCheckedChange={(checked: boolean) => setAuthorizedDebitOrder(checked as boolean)}
                                className="mt-0.5"
                            />
                            <label htmlFor="authorizedDebitOrder" className="text-sm text-gray-700 dark:text-gray-300 cursor-pointer leading-relaxed">
                                <span className="font-medium">Automatic Debit Order Authorization:</span> I authorize the relevant pension authority (PSMAS / NSSA) and/or ZB Bank to make automatic debit orders against my monthly pension for loan repayment, for the duration of the loan tenure, until the outstanding balance is fully settled.
                            </label>
                        </div>
                    </div>

                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-4 italic">
                        Your electronic submission of this form constitutes your signature and agreement to the above declarations.
                    </p>
                </Card>

                <div className="flex flex-col-reverse sm:flex-row justify-between gap-4 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleBackWithSave}
                        disabled={loading}
                        className="flex items-center justify-center gap-2 w-full sm:w-auto"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back
                    </Button>

                    <Button
                        type="submit"
                        disabled={loading}
                        className="bg-emerald-600 hover:bg-emerald-700 px-8 w-full sm:w-auto"
                    >
                        {loading ? 'Submitting...' : 'Agree & Submit Application'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default PensionerLoanForm;
