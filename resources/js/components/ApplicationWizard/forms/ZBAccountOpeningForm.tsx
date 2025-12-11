import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import FormField from '@/components/ApplicationWizard/components/FormField';
import { formatZimbabweId } from '@/components/ApplicationWizard/utils/formatters';
import { ChevronLeft, User, Building, CreditCard, Users, Smartphone } from 'lucide-react';

interface ZBAccountOpeningFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

type FuneralDependent = {
    surname: string;
    forenames: string;
    relationship: string;
    dateOfBirth: string;
    idNumber: string;
    coverAmount: string;
    premium: string;
};

const createEmptyDependent = (): FuneralDependent => ({
    surname: '',
    forenames: '',
    relationship: '',
    dateOfBirth: '',
    idNumber: '',
    coverAmount: '',
    premium: ''
});

const ZBAccountOpeningForm: React.FC<ZBAccountOpeningFormProps> = ({ data, onNext, onBack, loading }) => {
    const normalizeIdValue = (fieldKey: string, rawValue: string | boolean): string | boolean => {
        if (typeof rawValue === 'string' && fieldKey.toLowerCase().includes('idnumber')) {
            return formatZimbabweId(rawValue);
        }
        return rawValue;
    };

    // Helper function to convert address objects to strings
    const convertAddressToString = (address: any): string => {
        if (typeof address === 'string') return address;
        if (typeof address === 'object' && address) {
            return JSON.stringify(address);
        }
        return '';
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
    const businessName = data.business || '';
    const currentDate = new Date().toISOString().split('T')[0];

    const [formData, setFormData] = useState({
        // Credit Facility Details (pre-populated)
        ...creditDetails,

        // Account Specifications
        accountNumber: '',
        accountType: '', // Account type selection
        accountCurrency: '',
        initialDeposit: '', // Initial deposit amount
        serviceCenter: '',

        // Personal Details
        title: '',
        firstName: '',
        surname: '',
        maidenName: '',
        otherNames: '',
        gender: '',
        dateOfBirth: '',
        placeOfBirth: '',
        nationality: '',
        maritalStatus: '',
        citizenship: '',
        dependents: '',
        nationalIdNumber: data.formResponses?.nationalIdNumber || '',
        driversLicense: '',
        passportNumber: '',
        passportExpiry: '',
        countryOfResidence: '',
        highestEducation: '',
        hobbies: '',

        // Contact Details
        residentialAddress: '',
        telephoneRes: '',
        mobile: data.formResponses?.mobile || '',
        bus: '',
        emailAddress: '',

        // Employment Details
        department: '', // Added for GOZ Non-SSB
        employerName: '',
        occupation: '',
        employmentStatus: '',
        businessDescription: '',
        employerType: {
            government: false,
            localCompany: false,
            multinational: false,
            ngo: false,
            other: false,
            otherSpecify: ''
        },
        employerAddress: '',
        employerContact: '',
        grossMonthlySalary: '',
        otherIncome: '',

        // Spouse/Next of Kin
        spouseTitle: '',
        spouseFirstName: '',
        spouseSurname: '',
        spouseAddress: '',
        spouseIdNumber: '',
        spouseContact: '',
        spouseRelationship: '',
        spouseGender: '',
        spouseEmail: '',

        // ZB Life Funeral Cash Cover
        funeralCover: {
            dependents: Array.from({ length: 8 }, () => createEmptyDependent()),
            principalMember: {
                memorialCashBenefit: '',
                tombstoneCashBenefit: '',
                groceryBenefit: '',
                schoolFeesBenefit: '',
                personalAccidentBenefit: ''
            }
        },

        // Personal Accident Benefit
        personalAccidentBenefit: {
            surname: '',
            forenames: ''
        },

        // Other Services
        smsAlerts: false,
        smsNumber: '',
        eStatements: false,
        eStatementsEmail: '',

        // Digital Banking
        mobileMoneyEcocash: false,
        mobileMoneyNumber: '',
        eWallet: false,
        eWalletNumber: '',
        whatsappBanking: false,
        internetBanking: false,

        // Supporting Documents
        supportingDocs: {
            passportPhotos: false,
            proofOfResidence: false,
            payslip: false,
            nationalId: false,
            passport: false,
            driversLicense: false
        },

        // Declaration
        declaration: {
            fullName: '',
            signature: '',
            date: '',
            acknowledged: false
        }
    });

    const handleDependentChange = (index: number, field: keyof FuneralDependent, value: string) => {
        const processedValue = normalizeIdValue(String(field), value);

        setFormData(prev => {
            const updatedDependents = [...prev.funeralCover.dependents];
            updatedDependents[index] = {
                ...updatedDependents[index],
                [field]: processedValue
            };

            return {
                ...prev,
                funeralCover: {
                    ...prev.funeralCover,
                    dependents: updatedDependents
                }
            };
        });
    };

    const handleInputChange = (field: string, value: string | boolean) => {
        setFormData(prev => {
            const pathSegments = field.split('.');
            const targetKey = pathSegments[pathSegments.length - 1];
            const processedValue = normalizeIdValue(targetKey, value);

            if (pathSegments.length === 1) {
                return {
                    ...prev,
                    [field]: processedValue
                };
            }

            const updatedData = { ...prev };
            let currentLevel: any = updatedData;


            for (let i = 0; i < pathSegments.length - 1; i++) {
                const segment = pathSegments[i];
                const nextValue = currentLevel[segment];

                if (typeof nextValue === 'object' && nextValue !== null) {
                    currentLevel[segment] = Array.isArray(nextValue) ? [...nextValue] : { ...nextValue };
                } else {
                    currentLevel[segment] = {};
                }

                currentLevel = currentLevel[segment];
            }

            currentLevel[targetKey] = processedValue;

            return updatedData;
        });
    };

    // Set default values for GOZ Non-SSB
    React.useEffect(() => {
        if (data.employer === 'government-non-ssb' && !formData.employerName) {
            setFormData(prev => ({
                ...prev,
                employerName: 'Ministry of Defence'
            }));
        }
    }, [data.employer]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Map ZB form fields to match validation expectations
        const mappedData = {
            formResponses: {
                // Personal details (required by validation)
                firstName: formData.firstName,
                surname: formData.surname,
                dateOfBirth: formData.dateOfBirth,
                gender: formData.gender,
                nationalIdNumber: formData.nationalIdNumber,
                mobile: formData.mobile,
                residentialAddress: convertAddressToString(formData.residentialAddress),
                maritalStatus: formData.maritalStatus,
                nationality: formData.nationality,
                countryOfResidence: formData.countryOfResidence,
                accountCurrency: formData.accountCurrency,
                serviceCenter: formData.serviceCenter,

                // Spouse/Next of Kin details (map to spouseDetails array)
                spouseDetails: [
                    {
                        fullName: formData.spouseFirstName,
                        relationship: formData.spouseRelationship,
                        phoneNumber: formData.spouseContact,
                        residentialAddress: convertAddressToString(formData.spouseAddress),
                        idNumber: formData.spouseIdNumber,
                        gender: formData.spouseGender,
                        email: formData.spouseEmail,
                        emailAddress: formData.spouseEmail  // PDF template expects both email and emailAddress
                    }
                ],

                // Declaration
                declaration: {
                    fullName: formData.declaration.fullName,
                    signature: formData.declaration.signature,
                    date: formData.declaration.date,
                    acknowledged: formData.declaration.acknowledged
                },

                // Additional form fields (avoid duplicates by omitting already mapped fields)
                title: formData.title,
                maidenName: formData.maidenName,
                otherNames: formData.otherNames,
                placeOfBirth: formData.placeOfBirth,
                citizenship: formData.citizenship,
                dependents: formData.dependents,
                driversLicense: formData.driversLicense,
                passportNumber: formData.passportNumber,
                passportExpiry: formData.passportExpiry,
                highestEducation: formData.highestEducation,
                hobbies: formData.hobbies,
                telephoneRes: formData.telephoneRes,
                bus: formData.bus,
                emailAddress: formData.emailAddress,
                employerName: formData.employerName,
                occupation: formData.occupation,
                employmentStatus: formData.employmentStatus,
                businessDescription: formData.businessDescription,
                employerType: formData.employerType,
                employerAddress: convertAddressToString(formData.employerAddress),
                employerContact: formData.employerContact,
                grossMonthlySalary: formData.grossMonthlySalary,
                otherIncome: formData.otherIncome,
                department: formData.department, // Map department field
                accountNumber: formData.accountNumber,
                accountType: formData.accountType,
                initialDeposit: formData.initialDeposit,
                funeralCover: formData.funeralCover,
                personalAccidentBenefit: formData.personalAccidentBenefit,
                smsAlerts: formData.smsAlerts,
                smsNumber: formData.smsNumber,
                eStatements: formData.eStatements,
                eStatementsEmail: formData.eStatementsEmail,
                mobileMoneyEcocash: formData.mobileMoneyEcocash,
                mobileMoneyNumber: formData.mobileMoneyNumber,
                eWallet: formData.eWallet,
                eWalletNumber: formData.eWalletNumber,
                whatsappBanking: formData.whatsappBanking,
                internetBanking: formData.internetBanking,
                supportingDocs: formData.supportingDocs,
                // Credit facility details
                creditFacilityType: formData.creditFacilityType,
                loanAmount: formData.loanAmount,
                loanTenure: formData.loanTenure,
                monthlyPayment: formData.monthlyPayment,
                interestRate: formData.interestRate
            },
            documents: {
                uploadedDocuments: {
                    national_id: [],
                    passport_photo: []
                },
                selfie: '',
                signature: '',
                uploadedAt: new Date().toISOString(),
                documentReferences: {},
                validationSummary: {
                    allDocumentsValid: false,
                    totalDocuments: 0,
                    completedDocuments: 0,
                    documentTypes: ['national_id', 'passport_photo']
                }
            },
            formType: 'zb_account_opening',
            formId: 'individual_account_opening.json'
        };

        onNext(mappedData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">New ZB Account Opening Application</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Complete your account opening application
                </p>
                <p className="text-sm text-red-600 dark:text-red-400">
                    Fields marked with * are required
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Credit Facility Application Details */}
                <Card className="p-6 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Hire Purchase Application Details</h3>
                    </div>

                    {/* Pre-populated readonly fields */}
                    <div className="grid gap-4 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg border">
                        <div className="text-sm text-green-600 dark:text-green-400 font-medium mb-2">
                            ✅ The following details have been automatically filled based on your product selection:
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Hire Purchase Facility Type</Label>
                                <Input
                                    value={formData.creditFacilityType}
                                    readOnly
                                    className="border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Amount (USD)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <Input
                                        value={formData.loanAmount}
                                        readOnly
                                        className="pl-8 border-gray-200 dark:border-gray-600"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Duration (Months)</Label>
                                <Input
                                    value={`${formData.loanTenure} months`}
                                    readOnly
                                    className="border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Monthly Payment (USD)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <Input
                                        value={formData.monthlyPayment}
                                        readOnly
                                        className="pl-8 border-gray-200 dark:border-gray-600"
                                    />
                                </div>
                            </div>
                            <Input
                                value={`${formData.interestRate}%`}
                                readOnly
                                type="hidden"
                            />
                        </div>
                    </div>
                </Card>

                {/* Account Specifications */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <CreditCard className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Account Specifications</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="accountNumber">Account Number (Pre-Official Use Only)</Label>
                            <div className="flex gap-1">
                                {Array(4).fill(0).map((_, i) => (
                                    <Input key={i} className="w-12 text-center" maxLength={1} />
                                ))}
                                <span className="mx-2">-</span>
                                {Array(6).fill(0).map((_, i) => (
                                    <Input key={i + 4} className="w-12 text-center" maxLength={1} />
                                ))}
                                <span className="mx-2">-</span>
                                {Array(2).fill(0).map((_, i) => (
                                    <Input key={i + 10} className="w-12 text-center" maxLength={1} />
                                ))}
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="accountType">Account Type</Label>
                            <Select value={formData.accountType} onValueChange={(value) => handleInputChange('accountType', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select account type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="savings">Savings Account</SelectItem>
                                    <SelectItem value="current">Current Account</SelectItem>
                                    <SelectItem value="fixed_deposit">Fixed Deposit</SelectItem>
                                    <SelectItem value="youth">Youth Account</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="initialDeposit">Initial Deposit Amount (USD)</Label>
                            <Input
                                id="initialDeposit"
                                type="number"
                                value={formData.initialDeposit}
                                onChange={(e) => handleInputChange('initialDeposit', e.target.value)}
                                placeholder="Enter initial deposit amount"
                                min="0"
                                step="0.01"
                            />
                        </div>

                        <div>
                            <Label htmlFor="serviceCenter">Service Centre for Card Collection *</Label>
                            <Input
                                id="serviceCenter"
                                value={formData.serviceCenter}
                                onChange={(e) => handleInputChange('serviceCenter', e.target.value)}
                                placeholder="Enter service centre"
                                required
                            />
                        </div>
                    </div>

                    <div className="mt-4">
                        <Label>Currency of Account (Please mark (X) the appropriate boxes) *</Label>
                        <div className="flex gap-4 mt-2">
                            {['ZWL$', 'USD', 'ZAR', 'BWP', 'EURO', 'OTHER (Indicate)'].map((currency) => (
                                <label key={currency} className="flex items-center space-x-2">
                                    <Checkbox
                                        checked={formData.accountCurrency === currency}
                                        onCheckedChange={(checked) => checked && handleInputChange('accountCurrency', currency)}
                                    />
                                    <span className="text-sm">{currency}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                </Card>

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
                                    <SelectItem value="Ms">Ms</SelectItem>
                                    <SelectItem value="Dr">Dr</SelectItem>
                                    <SelectItem value="Prof">Prof</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="firstName"
                                name="firstName"
                                label="First Name *"
                                type="text"
                                value={formData.firstName}
                                onChange={(value) => handleInputChange('firstName', value)}
                                autoCapitalize={true}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="surname"
                                name="surname"
                                label="Surname *"
                                type="text"
                                value={formData.surname}
                                onChange={(value) => handleInputChange('surname', value)}
                                autoCapitalize={true}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="maidenName"
                                name="maidenName"
                                label="Maiden Name"
                                type="text"
                                value={formData.maidenName}
                                onChange={(value) => handleInputChange('maidenName', value)}
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
                                name="dateOfBirth"
                                label="Date of Birth *"
                                type="dial-date"
                                value={formData.dateOfBirth}
                                onChange={(value) => handleInputChange('dateOfBirth', value)}
                                required
                                maxDate={`${new Date().getFullYear() - 18}-12-31`}
                                minDate="1930-01-01"
                                defaultAge={20}
                            />
                        </div>

                        <div>
                            <Label htmlFor="placeOfBirth">Place of Birth</Label>
                            <Input
                                id="placeOfBirth"
                                value={formData.placeOfBirth}
                                onChange={(e) => handleInputChange('placeOfBirth', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="nationality">Nationality *</Label>
                            <Input
                                id="nationality"
                                value={formData.nationality}
                                onChange={(e) => handleInputChange('nationality', e.target.value)}
                                placeholder="e.g., Zimbabwean"
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="maritalStatus">Marital Status *</Label>
                            <Select value={formData.maritalStatus} onValueChange={(value) => handleInputChange('maritalStatus', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Single">Single</SelectItem>
                                    <SelectItem value="Married">Married</SelectItem>
                                    <SelectItem value="Other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="nationalIdNumber"
                                name="nationalIdNumber"
                                label="National ID Number *"
                                type="text"
                                value={formData.nationalIdNumber}
                                onChange={(value) => handleInputChange('nationalIdNumber', value)}
                                capitalizeCheckLetter={true}
                                placeholder="e.g. 12-345678 A 12"
                                title="Zimbabwe ID format: 12-345678 A 12"
                                required
                                readOnly={!!data.formResponses?.nationalIdNumber}

                            />
                        </div>

                        <div>
                            <Label htmlFor="driversLicense">Driver's License No</Label>
                            <Input
                                id="driversLicense"
                                value={formData.driversLicense}
                                onChange={(e) => handleInputChange('driversLicense', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="passportNumber">Passport Number (if applicable)</Label>
                            <Input
                                id="passportNumber"
                                value={formData.passportNumber}
                                onChange={(e) => handleInputChange('passportNumber', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="passportExpiry"
                                name="passportExpiry"
                                label="Expiry Date"
                                type="dial-date"
                                value={formData.passportExpiry}
                                onChange={(value) => handleInputChange('passportExpiry', value)}
                                minDate={currentDate}
                                maxDate="2055-12-31"
                                defaultAge={0}
                            />
                        </div>

                        <div>
                            <Label htmlFor="countryOfResidence">Country of Residence *</Label>
                            <Input
                                id="countryOfResidence"
                                value={formData.countryOfResidence}
                                onChange={(e) => handleInputChange('countryOfResidence', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="highestEducation">Highest Educational Qualification</Label>
                            <Input
                                id="highestEducation"
                                value={formData.highestEducation}
                                onChange={(e) => handleInputChange('highestEducation', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="hobbies">Hobbies</Label>
                            <Input
                                id="hobbies"
                                value={formData.hobbies}
                                onChange={(e) => handleInputChange('hobbies', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="citizenship">Citizenship</Label>
                            <Input
                                id="citizenship"
                                value={formData.citizenship}
                                onChange={(e) => handleInputChange('citizenship', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="dependents">Dependents</Label>
                            <Input
                                id="dependents"
                                type="number"
                                value={formData.dependents}
                                onChange={(e) => handleInputChange('dependents', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="otherNames"
                                name="otherNames"
                                label="Other Names"
                                type="text"
                                value={formData.otherNames}
                                onChange={(value) => handleInputChange('otherNames', value)}
                                autoCapitalize={true}
                            />
                        </div>
                    </div>
                </Card>

                {/* Contact Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Smartphone className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Contact Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <FormField
                                id="residentialAddress"
                                name="residentialAddress"
                                label="Residential Address *"
                                type="address"
                                value={formData.residentialAddress}
                                onChange={(value) => handleInputChange('residentialAddress', value)}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="telephoneRes"
                                name="telephoneRes"
                                label="Telephone (Res)"
                                type="phone"
                                value={formData.telephoneRes}
                                onChange={(value) => handleInputChange('telephoneRes', value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="mobile"
                                name="mobile"
                                label="Mobile (+263-) *"
                                type="phone"
                                value={formData.mobile}
                                onChange={(value) => handleInputChange('mobile', value)}
                                placeholder="+263-"
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="emailAddress">Email Address</Label>
                            <Input
                                id="emailAddress"
                                type="email"
                                value={formData.emailAddress}
                                onChange={(e) => handleInputChange('emailAddress', e.target.value)}
                            />
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
                            ) : (
                                <Input
                                    id="employerName"
                                    value={formData.employerName}
                                    onChange={(e) => handleInputChange('employerName', e.target.value)}
                                    required
                                    readOnly={false}
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
                            <Label htmlFor="occupation">Occupation</Label>
                            <Input
                                id="occupation"
                                value={formData.occupation}
                                onChange={(e) => handleInputChange('occupation', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="employmentStatus">Employment Status</Label>
                            <Select value={formData.employmentStatus} onValueChange={(value) => handleInputChange('employmentStatus', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Permanent">Permanent</SelectItem>
                                    <SelectItem value="Contract">Contract</SelectItem>
                                    <SelectItem value="Pensioner">Pensioner</SelectItem>
                                    <SelectItem value="Unemployed">Unemployed</SelectItem>
                                    <SelectItem value="Self-Employed">Self-Employed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="grossMonthlySalary">Net Pay Range (USD)</Label>
                            <Select value={formData.grossMonthlySalary} onValueChange={(value) => handleInputChange('grossMonthlySalary', value)}>
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
                    </div>
                </Card>

                {/* Spouse/Next of Kin */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Users className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">
                            {formData.gender === 'Male' ? "Wife's" : formData.gender === 'Female' ? "Husband's" : "Spouse"}/Next of Kin
                        </h3>
                    </div>
                    <p className="text-xs text-gray-500 italic mb-4">
                        *this is for statistical and record keeping purposes only*
                    </p>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <Label htmlFor="spouseTitle">Title</Label>
                            <Select value={formData.spouseTitle} onValueChange={(value) => handleInputChange('spouseTitle', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select title" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Mr">Mr</SelectItem>
                                    <SelectItem value="Mrs">Mrs</SelectItem>
                                    <SelectItem value="Ms">Ms</SelectItem>
                                    <SelectItem value="Dr">Dr</SelectItem>
                                    <SelectItem value="Prof">Prof</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <FormField
                                id="spouseFirstName"
                                name="spouseFirstName"
                                label="Full Name *"
                                type="text"
                                value={formData.spouseFirstName}
                                onChange={(value) => handleInputChange('spouseFirstName', value)}
                                autoCapitalize={true}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="spouseRelationship">Nature of Relationship</Label>
                            <Select value={formData.spouseRelationship} onValueChange={(value) => handleInputChange('spouseRelationship', value)}>
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
                                id="spouseIdNumber"
                                name="spouseIdNumber"
                                label="National ID No"
                                type="text"
                                value={formData.spouseIdNumber}
                                onChange={(value) => handleInputChange('spouseIdNumber', value)}
                                capitalizeCheckLetter={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="spouseContact"
                                name="spouseContact"
                                label="Contact Number"
                                type="phone"
                                value={formData.spouseContact}
                                onChange={(value) => handleInputChange('spouseContact', value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* ZB Life Funeral Cash Cover */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">H - ZB LIFE FUNERAL CASH COVER</h3>
                        <p className="text-sm text-emerald-700">
                            Details of dependents to be covered by this application is up to eight (8) dependents.
                            <em>Please tick (√) the appropriate box to show supplementary benefits to be included.</em>
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr className="bg-gray-50">
                                    <th className="border border-gray-300 p-2 text-left">Surname</th>
                                    <th className="border border-gray-300 p-2 text-left">Forename(s)</th>
                                    <th className="border border-gray-300 p-2 text-left">Relationship</th>
                                    <th className="border border-gray-300 p-2 text-left">Date of Birth</th>
                                    <th className="border border-gray-300 p-2 text-left">Birth Entry/National ID No.</th>
                                    <th className="border border-gray-300 p-2 text-left">Cover Amount Per Dependant $</th>
                                    <th className="border border-gray-300 p-2 text-left">Premium Per Month $</th>
                                </tr>
                            </thead>
                            <tbody>
                                {formData.funeralCover.dependents.map((dependent, index) => (
                                    <tr key={index}>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                className="w-full"
                                                placeholder="Surname"
                                                value={dependent.surname}
                                                onChange={(e) => handleDependentChange(index, 'surname', e.target.value)}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                className="w-full"
                                                placeholder="Forename(s)"
                                                value={dependent.forenames}
                                                onChange={(e) => handleDependentChange(index, 'forenames', e.target.value)}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                className="w-full"
                                                placeholder="Relationship"
                                                value={dependent.relationship}
                                                onChange={(e) => handleDependentChange(index, 'relationship', e.target.value)}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <FormField
                                                id={`dependent-${index}-dob`}
                                                label=""
                                                type="dial-date"
                                                value={dependent.dateOfBirth}
                                                onChange={(value) => handleDependentChange(index, 'dateOfBirth', value)}
                                                maxDate={currentDate}
                                                defaultAge={0}
                                                className="space-y-1"
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                className="w-full"
                                                placeholder="ID Number"
                                                value={dependent.idNumber}
                                                onChange={(e) => handleDependentChange(index, 'idNumber', e.target.value)}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                type="number"
                                                className="w-full"
                                                placeholder="Amount"
                                                value={dependent.coverAmount}
                                                onChange={(e) => handleDependentChange(index, 'coverAmount', e.target.value)}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input
                                                type="number"
                                                className="w-full"
                                                placeholder="Premium"
                                                value={dependent.premium}
                                                onChange={(e) => handleDependentChange(index, 'premium', e.target.value)}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <h4 className="font-semibold mb-2">Principal Member</h4>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>Memorial Cash Benefit:</span>
                                    <Input className="w-20" placeholder="Amount" />
                                </div>
                                <div className="flex justify-between">
                                    <span>Tombstone Cash Benefit:</span>
                                    <Input className="w-20" placeholder="Amount" />
                                </div>
                                <div className="flex justify-between">
                                    <span>Grocery Benefit:</span>
                                    <Input className="w-20" placeholder="Amount" />
                                </div>
                                <div className="flex justify-between">
                                    <span>School Fees Benefit:</span>
                                    <Input className="w-20" placeholder="Amount" />
                                </div>
                                <div className="flex justify-between">
                                    <span>Personal Accident Benefit:</span>
                                    <Input className="w-20" placeholder="Amount" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 className="font-semibold mb-2">Supplementary Benefits (Tick (√) appropriate box)</h4>
                            <div className="space-y-2 text-sm">
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="memorialCash" />
                                    <Label htmlFor="memorialCash">Memorial Cash Benefit: Amount of Cover Per Person</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="tombstoneCash" />
                                    <Label htmlFor="tombstoneCash">Tombstone Cash Benefit: Amount of Cover Per Person</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="groceryBenefit" />
                                    <Label htmlFor="groceryBenefit">Grocery Benefit: Amount of Cover</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="schoolFees" />
                                    <Label htmlFor="schoolFees">School Fees Benefit: Amount of Cover</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="personalAccident" />
                                    <Label htmlFor="personalAccident">Personal Accident Benefit: Please supply details below</Label>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Personal Accident Benefit */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">I - PERSONAL ACCIDENT BENEFIT</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="accidentSurname">Surname</Label>
                            <Input
                                id="accidentSurname"
                                value={formData.personalAccidentBenefit.surname}
                                onChange={(e) => handleInputChange('personalAccidentBenefit.surname', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="accidentForenames">Forename(s)</Label>
                            <Input
                                id="accidentForenames"
                                value={formData.personalAccidentBenefit.forenames}
                                onChange={(e) => handleInputChange('personalAccidentBenefit.forenames', e.target.value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* Digital Banking Services */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Smartphone className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Digital Banking Services</h3>
                    </div>

                    <div className="space-y-4">
                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="mobileMoneyEcocash"
                                checked={formData.mobileMoneyEcocash}
                                onCheckedChange={(checked) => handleInputChange('mobileMoneyEcocash', checked)}
                            />
                            <Label htmlFor="mobileMoneyEcocash">Mobile money e.g. Ecocash Services</Label>
                        </div>

                        {formData.mobileMoneyEcocash && (
                            <div className="ml-6">
                                <FormField
                                    id="mobileMoneyNumber"
                                    name="mobileMoneyNumber"
                                    label="Mobile Number"
                                    type="phone"
                                    value={formData.mobileMoneyNumber}
                                    onChange={(value) => handleInputChange('mobileMoneyNumber', value)}
                                    placeholder="263..."
                                />
                            </div>
                        )}

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="whatsappBanking"
                                checked={formData.whatsappBanking}
                                onCheckedChange={(checked) => handleInputChange('whatsappBanking', checked)}
                            />
                            <Label htmlFor="whatsappBanking">WhatsApp Banking</Label>
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="internetBanking"
                                checked={formData.internetBanking}
                                onCheckedChange={(checked) => handleInputChange('internetBanking', checked)}
                            />
                            <Label htmlFor="internetBanking">Internet Banking</Label>
                        </div>
                    </div>
                </Card>

                {/* Supporting KYC Checklist */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">SUPPORTING KYC CHECKLIST</h3>
                        <p className="text-sm text-emerald-700">Please attach certified copies of the following and indicate by marking:</p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-3">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="passportPhotos"
                                    checked={formData.supportingDocs.passportPhotos}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.passportPhotos', checked)}
                                />
                                <Label htmlFor="passportPhotos">(i) Two (2) recent passport-sized photos</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="proofOfResidence"
                                    checked={formData.supportingDocs.proofOfResidence}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.proofOfResidence', checked)}
                                />
                                <Label htmlFor="proofOfResidence">(ii) Proof of residence (within 3-months)</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="payslip"
                                    checked={formData.supportingDocs.payslip}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.payslip', checked)}
                                />
                                <Label htmlFor="payslip">(iii) Payslip (where applicable)</Label>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <p className="font-medium">Current Identification Documents: (mark applicable):</p>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="nationalIdCard"
                                    checked={formData.supportingDocs.nationalId}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.nationalId', checked)}
                                />
                                <Label htmlFor="nationalIdCard">National ID Card</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="passportDoc"
                                    checked={formData.supportingDocs.passport}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.passport', checked)}
                                />
                                <Label htmlFor="passportDoc">Passport</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="driversLicenseDoc"
                                    checked={formData.supportingDocs.driversLicense}
                                    onCheckedChange={(checked) => handleInputChange('supportingDocs.driversLicense', checked)}
                                />
                                <Label htmlFor="driversLicenseDoc">Drivers' License</Label>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Declaration */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">K - DECLARATION</h3>
                        <p className="text-sm text-emerald-700">
                            I confirm that to the best of my knowledge, the above information is true and correct and that all the persons registered above are not on medication for any disease or illness. Should anything change, I undertake to advise ZB Bank immediately.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="declarationName">Full Name</Label>
                            <Input
                                id="declarationName"
                                value={formData.declaration.fullName}
                                onChange={(e) => handleInputChange('declaration.fullName', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="declarationSignature">Applicant's Signature</Label>
                            <div className="h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-500">
                                Signature Area
                            </div>
                        </div>

                        <div>
                            <FormField
                                id="declarationDate"
                                name="declaration.date"
                                label="Date"
                                type="dial-date"
                                value={formData.declaration.date}
                                onChange={(value) => handleInputChange('declaration.date', value)}
                                maxDate={currentDate}
                                defaultAge={0}
                            />
                        </div>
                    </div>

                    <div className="mt-4">
                        <FormField
                            id="declarationAcknowledged"
                            name="declaration.acknowledged"
                            label="I acknowledge and agree to the terms and conditions stated in the declaration above. *"
                            type="checkbox"
                            checkboxVariant="prominent"
                            checked={formData.declaration.acknowledged || false}
                            onChange={(checked) => handleInputChange('declaration.acknowledged', checked)}
                            required
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

export default ZBAccountOpeningForm;
