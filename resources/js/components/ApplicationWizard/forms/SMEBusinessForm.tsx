import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { ChevronLeft, Building, DollarSign, Users, Shield } from 'lucide-react';
import FormField from '../components/FormField';
import { formatZimbabweId } from '../utils/formatters';

interface SMEBusinessFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const SMEBusinessForm: React.FC<SMEBusinessFormProps> = ({ data, onNext, onBack, loading }) => {
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

        return {
            creditFacilityType: facilityType,
            loanAmount: finalPrice.toFixed(2),
            loanTenure: tenure.toString(),
            monthlyPayment: monthlyPaymentValue.toFixed(2),
            interestRate: '10.0'
        };
    };

    const creditDetails = calculateCreditFacilityDetails();
    const businessName = data.business;
    const currentDate = new Date().toISOString().split('T')[0];
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';

    const [formData, setFormData] = useState<Record<string, any>>({
        // Credit Facility Details (pre-populated)
        ...creditDetails,

        // Business Type
        businessType: '', // Company, PBC, Informal body
        loanType: '',

        // Business Information
        registeredName: '',
        tradingName: '',
        businessName: '', // For compatibility with PDF template
        businessRegistration: '', // Business registration number for bank requirements
        typeOfBusiness: '',
        businessAddress: JSON.stringify({}),
        periodAtLocation: '',
        initialCapital: '',
        incorporationDate: '',
        incorporationNumber: '',
        bpNumber: '',
        contactPhone: '',
        emailAddress: '',
        yearsInBusiness: '',
        dateEstablished: '',
        businessCity: '',
        businessProvince: '',
        postalAddress: '',
        monthlyTurnover: '',
        positionInBusiness: 'Director',

        // Capital Sources
        capitalSources: {
            ownSavings: false,
            familyGift: false,
            loan: false,
            other: false,
            otherSpecify: ''
        },

        // Customer Base
        customerBase: {
            individuals: false,
            businesses: false,
            other: false,
            otherSpecify: ''
        },

        // Financial Information
        estimatedAnnualSales: '',
        netProfit: '',
        payDayRange: '',
        totalLiabilities: '',
        netCashFlow: '',
        mainProducts: '',
        mainProblems: '',

        // Other Business Interests
        otherBusinessName: '',
        otherBusinessAddress: '',
        otherBusinessPhone: '',
        numberOfEmployees: {
            fullTime: '',
            partTime: '',
            nonPaid: '',
            total: ''
        },
        customerLocation: {
            neighborhood: false,
            thisTown: false,
            other: false,
            otherSpecify: ''
        },

        // Purpose of loan (auto-populated from product selection)
        purposeOfLoan: businessName ? `${businessName} - ${data.scale || 'Standard Scale'}` : '',

        // Budget Breakdown
        budgetItems: [
            { item: '', cost: '' },
            { item: '', cost: '' },
            { item: '', cost: '' }
        ],

        // Directors' Personal Details
        directorsPersonalDetails: {
            title: '',
            firstName: '',
            surname: '',
            maidenName: '',
            gender: '',
            dateOfBirth: '',
            maritalStatus: '',
            nationality: '',
            idNumber: '',
            cellNumber: '',
            whatsApp: '',
            highestEducation: '',
            citizenship: '',
            emailAddress: '',
            residentialAddress: JSON.stringify({}),
            passportPhoto: '',
            periodAtCurrentAddress: { years: '', months: '' },
            periodAtPreviousAddress: { years: '', months: '' }
        },

        // Spouse and Next of Kin
        spouseAndNextOfKin: {
            spouse: {
                fullName: '',
                phoneNumber: '',
                emailAddress: '',
                address: JSON.stringify({})
            },
            nextOfKin1: {
                fullName: '',
                relationship: '',
                phoneNumber: '',
                emailAddress: '',
                address: JSON.stringify({})
            },
            nextOfKin2: {
                fullName: '',
                relationship: '',
                phoneNumber: '',
                emailAddress: '',
                address: JSON.stringify({})
            }
        },

        // Employment Details
        employmentDetails: {
            businessEmployerName: '',
            jobTitle: '',
            businessEmployerAddress: JSON.stringify({}),
            dateOfEmployment: '',
            immediateManager: '',
            phoneNumberOfManager: ''
        },

        // Property Ownership
        propertyOwnership: '',

        // Banking Details
        bankingDetails: {
            bank: '',
            branch: '',
            accountNumber: ''
        },

        // Loans with Other Institutions
        otherLoans: [
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' },
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' }
        ],

        // References
        references: [
            { name: '', phoneNumber: '' },
            { name: '', phoneNumber: '' },
            { name: '', phoneNumber: '' }
        ],

        // Security (Assets Pledged)
        securityAssets: [
            { description: '', serialNumber: '', estimatedValue: '' },
            { description: '', serialNumber: '', estimatedValue: '' },
            { description: '', serialNumber: '', estimatedValue: '' }
        ],

        // Declaration
        declaration: {
            acknowledged: false
        },

        // Directors Signatures
        directorsSignatures: [
            { name: '', signature: '', date: '' },
            { name: '', signature: '', date: '' },
            { name: '', signature: '', date: '' },
            { name: '', signature: '', date: '' },
            { name: '', signature: '', date: '' }
        ],

        // KYC Documents
        kycDocuments: {
            copyOfId: false,
            articlesOfAssociation: false,
            bankStatement: false,
            groupConstitution: false,
            financialStatement: false,
            certificateOfIncorporation: false,
            ecocashStatements: false,
            resolutionToBorrow: false,
            cr11: false,
            cr6: false,
            cr5: false,
            moa: false
        }
    });

    const handleInputChange = (field: string, value: string) => {
        const processedValue = field.toLowerCase().includes('idnumber')
            ? formatZimbabweId(value)
            : value;

        setFormData(prev => ({
            ...prev,
            [field]: processedValue
        }));
    };

    const handleNestedChange = (section: string, field: string, value: string | boolean) => {
        const processedValue =
            typeof value === 'string' && field.toLowerCase().includes('idnumber')
                ? formatZimbabweId(value)
                : value;

        setFormData(prev => {
            const sectionData = (prev[section] ?? {}) as Record<string, any>;
            return {
                ...prev,
                [section]: {
                    ...sectionData,
                    [field]: processedValue
                }
            };
        });
    };

    const handleArrayChange = (section: string, index: number, field: string, value: string) => {
        setFormData(prev => {
            const sectionArray = Array.isArray(prev[section])
                ? (prev[section] as Array<Record<string, any>>)
                : [];
            const updatedSection = sectionArray.map((item: Record<string, any>, i: number) =>
                i === index ? { ...item, [field]: value } : item
            );

            return {
                ...prev,
                [section]: updatedSection
            };
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Map SME form fields to match PDF template expectations
        const directors = formData.directorsPersonalDetails || {};
        const references = Array.isArray(formData.references) ? formData.references : [];
        const businessAddress =
            typeof formData.businessAddress === 'string'
                ? formData.businessAddress
                : JSON.stringify(formData.businessAddress || {});
        const residentialAddress =
            typeof directors.residentialAddress === 'string'
                ? directors.residentialAddress
                : JSON.stringify(directors.residentialAddress || {});
        const computedEmployeeTotal =
            typeof formData.numberOfEmployees === 'object' && formData.numberOfEmployees !== null
                ? formData.numberOfEmployees.total ||
                (
                    parseInt(formData.numberOfEmployees.fullTime || '0', 10) +
                    parseInt(formData.numberOfEmployees.partTime || '0', 10) +
                    parseInt(formData.numberOfEmployees.nonPaid || '0', 10)
                ).toString()
                : formData.numberOfEmployees;

        const normalizedFormResponses = {
            ...formData,
            firstName: directors.firstName || '',
            surname: directors.surname || '',
            lastName: directors.surname || '',
            dateOfBirth: directors.dateOfBirth || '',
            gender: directors.gender || '',
            nationalIdNumber: directors.idNumber || '',
            mobile: directors.cellNumber || '',
            emailAddress: directors.emailAddress || formData.emailAddress || '',
            businessName: formData.registeredName || formData.tradingName || formData.businessName || '',
            registeredName: formData.registeredName || '',
            tradingName: formData.tradingName || '',
            businessRegistrationNumber: formData.businessRegistration || '',
            businessType: formData.businessType || '',
            businessAddress,
            businessPhone: formData.contactPhone || '',
            businessEmail: formData.emailAddress || '',
            businessIndustry: formData.typeOfBusiness || '',
            typeOfBusiness: formData.typeOfBusiness || '',
            businessYearsOperating: formData.yearsInBusiness || '',
            yearsInBusiness: formData.yearsInBusiness || '',
            businessAnnualRevenue: formData.estimatedAnnualSales || '',
            estimatedAnnualSales: formData.estimatedAnnualSales || '',
            annualTurnover: formData.estimatedAnnualSales || '',
            payDayRange: formData.payDayRange || '',
            contactPhone: formData.contactPhone || '',
            directorsPersonalDetails: {
                ...directors,
                firstName: directors.firstName || '',
                surname: directors.surname || '',
                idNumber: directors.idNumber || '',
                dateOfBirth: directors.dateOfBirth || '',
                gender: directors.gender || '',
                cellNumber: directors.cellNumber || '',
                emailAddress: directors.emailAddress || '',
            },
            references,
            businessRegistration: formData.businessRegistration || '',
            dateEstablished: formData.dateEstablished || '',
            businessCity: formData.businessCity || '',
            businessProvince: formData.businessProvince || '',
            postalAddress: formData.postalAddress || '',
            businessTelephone: formData.contactPhone || '',
            businessMobile: directors.cellNumber || '',
            industrySector: formData.typeOfBusiness || '',
            numberOfEmployees: computedEmployeeTotal || '',
            monthlyTurnover: formData.monthlyTurnover || '',
            title: directors.title || 'Mr',
            nationality: directors.nationality || 'Zimbabwean',
            positionInBusiness: formData.positionInBusiness || 'Director',
            whatsApp: directors.whatsApp || directors.cellNumber || '',
            residentialAddress,
        };

        const mappedData = {
            formResponses: normalizedFormResponses,
            documents: {
                uploadedDocuments: {
                    national_id: [],
                    business_registration: [],
                    financial_statements: [],
                    director_id: []
                },
                selfie: '',
                signature: '',
                uploadedAt: new Date().toISOString(),
                documentReferences: {},
                validationSummary: {
                    allDocumentsValid: false,
                    totalDocuments: 0,
                    completedDocuments: 0,
                    documentTypes: ['national_id', 'business_registration', 'financial_statements', 'director_id']
                }
            },
            formType: 'sme_business',
            formId: 'smes_business_account_opening.json'
        };

        onNext(mappedData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">SME Business Application Form</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Complete your business loan application
                </p>
                <p className="text-sm text-red-600 dark:text-red-400">
                    Fields marked with * are required
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Business Type */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Business Type</h3>
                    </div>

                    <div className="mb-4">
                        <Label className="text-base font-medium mb-3 block">Business Type *</Label>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="company"
                                    checked={formData.businessType === 'company'}
                                    onCheckedChange={(checked) => checked && handleInputChange('businessType', 'company')}
                                />
                                <Label htmlFor="company">Company</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="pbc"
                                    checked={formData.businessType === 'pbc'}
                                    onCheckedChange={(checked) => checked && handleInputChange('businessType', 'pbc')}
                                />
                                <Label htmlFor="pbc">PBC</Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="informal"
                                    checked={formData.businessType === 'informal'}
                                    onCheckedChange={(checked) => checked && handleInputChange('businessType', 'informal')}
                                />
                                <Label htmlFor="informal">Informal body</Label>
                            </div>
                        </div>

                        <div className="md:col-span-3">
                            <Label htmlFor="loanType">Loan Type</Label>
                            <Input
                                id="loanType"
                                value={formData.loanType}
                                onChange={(e) => handleInputChange('loanType', e.target.value)}
                                placeholder="Enter loan type"
                            />
                        </div>
                    </div>
                </Card>

                {/* Business Information */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Business Information</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <FormField
                                id="registeredName"
                                label="Registered Name"
                                type="text"
                                value={formData.registeredName}
                                onChange={(value) => handleInputChange('registeredName', value)}
                                required
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="tradingName"
                                label="Trading Name"
                                type="text"
                                value={formData.tradingName}
                                onChange={(value) => handleInputChange('tradingName', value)}
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <Label htmlFor="businessRegistration">Business Registration Number *</Label>
                            <Input
                                id="businessRegistration"
                                value={formData.businessRegistration}
                                onChange={(e) => handleInputChange('businessRegistration', e.target.value)}
                                placeholder="Enter registration number"
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="typeOfBusiness">Type of Business *</Label>
                            <Input
                                id="typeOfBusiness"
                                value={formData.typeOfBusiness}
                                onChange={(e) => handleInputChange('typeOfBusiness', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="businessAddress"
                                label="Business Address"
                                type="text"
                                value={typeof formData.businessAddress === 'string' ? formData.businessAddress : ''}
                                onChange={(value) => handleInputChange('businessAddress', value)}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="periodAtLocation">Period at Current Business Location</Label>
                            <Input
                                id="periodAtLocation"
                                value={formData.periodAtLocation}
                                onChange={(e) => handleInputChange('periodAtLocation', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="initialCapital">Amount of Initial Capital</Label>
                            <Input
                                id="initialCapital"
                                type="number"
                                value={formData.initialCapital}
                                onChange={(e) => handleInputChange('initialCapital', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="incorporationDate"
                                label="Incorporation Date"
                                type="dial-date"
                                value={formData.incorporationDate}
                                onChange={(value) => handleInputChange('incorporationDate', value)}
                                maxDate={currentDate}
                                defaultAge={0}
                            />
                        </div>

                        <div>
                            <Label htmlFor="incorporationNumber">Certificate of Incorporation Number</Label>
                            <Input
                                id="incorporationNumber"
                                value={formData.incorporationNumber}
                                onChange={(e) => handleInputChange('incorporationNumber', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="contactPhone"
                                label="Contact Phone Number"
                                type="phone"
                                value={formData.contactPhone}
                                onChange={(value) => handleInputChange('contactPhone', value)}
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
                            />
                        </div>

                        <div>
                            <Label htmlFor="yearsInBusiness">Number of Years in Business *</Label>
                            <Input
                                id="yearsInBusiness"
                                type="number"
                                value={formData.yearsInBusiness}
                                onChange={(e) => handleInputChange('yearsInBusiness', e.target.value)}
                                required
                            />
                        </div>
                    </div>
                </Card>

                {/* Sources of Capital */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <DollarSign className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Sources of Capital</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="ownSavings"
                                checked={formData.capitalSources.ownSavings}
                                onCheckedChange={(checked) => handleNestedChange('capitalSources', 'ownSavings', checked)}
                            />
                            <Label htmlFor="ownSavings">Own Savings</Label>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="familyGift"
                                checked={formData.capitalSources.familyGift}
                                onCheckedChange={(checked) => handleNestedChange('capitalSources', 'familyGift', checked)}
                            />
                            <Label htmlFor="familyGift">Family Gift</Label>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="loan"
                                checked={formData.capitalSources.loan}
                                onCheckedChange={(checked) => handleNestedChange('capitalSources', 'loan', checked)}
                            />
                            <Label htmlFor="loan">Loan</Label>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="capitalOther"
                                checked={formData.capitalSources.other}
                                onCheckedChange={(checked) => handleNestedChange('capitalSources', 'other', checked)}
                            />
                            <Label htmlFor="capitalOther">Other</Label>
                        </div>

                        {formData.capitalSources.other && (
                            <div className="md:col-span-2">
                                <Label htmlFor="capitalOtherSpecify">Please specify</Label>
                                <Input
                                    id="capitalOtherSpecify"
                                    value={formData.capitalSources.otherSpecify}
                                    onChange={(e) => handleNestedChange('capitalSources', 'otherSpecify', e.target.value)}
                                />
                            </div>
                        )}
                    </div>
                </Card>

                {/* Financial Information */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <DollarSign className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Financial Information</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="estimatedAnnualSales">Estimated Annual Sales *</Label>
                            <Input
                                id="estimatedAnnualSales"
                                type="number"
                                value={formData.estimatedAnnualSales}
                                onChange={(e) => handleInputChange('estimatedAnnualSales', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="netProfit">Net Pay Range ({selectedCurrency})</Label>
                            <Select value={formData.netProfit} onValueChange={(value) => handleInputChange('netProfit', value)}>
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

                        <div className="md:col-span-2">
                            <Label htmlFor="payDayRange">Monthly Pay Day Range *</Label>
                            <Select value={formData.payDayRange} onValueChange={(value) => handleInputChange('payDayRange', value)} required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select your pay day range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="week1">I usually get paid in the first week (1st - 7th)</SelectItem>
                                    <SelectItem value="week2">I usually get paid in the second week (8th - 15th)</SelectItem>
                                    <SelectItem value="week3">I usually get paid in the third week (16th - 21st)</SelectItem>
                                    <SelectItem value="week4">I usually get paid after the 22nd</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="totalLiabilities">Total Liabilities</Label>
                            <Input
                                id="totalLiabilities"
                                type="number"
                                value={formData.totalLiabilities}
                                onChange={(e) => handleInputChange('totalLiabilities', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="netCashFlow">Net Cash Flow</Label>
                            <Input
                                id="netCashFlow"
                                type="number"
                                value={formData.netCashFlow}
                                onChange={(e) => handleInputChange('netCashFlow', e.target.value)}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <Label htmlFor="mainProducts">Main Product/Services</Label>
                            <Textarea
                                id="mainProducts"
                                value={formData.mainProducts}
                                onChange={(e) => handleInputChange('mainProducts', e.target.value)}
                                rows={3}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <Label htmlFor="mainProblems">Main Problems Faced by Business</Label>
                            <Textarea
                                id="mainProblems"
                                value={formData.mainProblems}
                                onChange={(e) => handleInputChange('mainProblems', e.target.value)}
                                rows={3}
                            />
                        </div>
                    </div>
                </Card>

                {/* Credit Facility Application Details */}
                <Card className="p-6 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
                    <div className="flex items-center mb-4">
                        <DollarSign className="h-6 w-6 text-green-600 mr-3" />
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
                                    className="bg-transparent border-gray-200 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Amount ({selectedCurrency})</Label>
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
                                    className="bg-transparent border-gray-200 dark:border-gray-600"
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
                            <div>
                                <Label className="text-gray-700 dark:text-gray-300">Interest Rate (%)</Label>
                                <Input
                                    value={`${formData.interestRate}%`}
                                    readOnly
                                    type="hidden"
                                    className="bg-transparent border-gray-200 dark:border-gray-600"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Editable purpose field */}
                    <div>
                        <Label htmlFor="purposeOfLoan">Purpose/Asset Applied For</Label>
                        <Textarea
                            id="purposeOfLoan"
                            value={formData.purposeOfLoan}
                            onChange={(e) => handleInputChange('purposeOfLoan', e.target.value)}
                            rows={3}
                            required
                            placeholder="Describe the purpose and asset details..."
                        />
                    </div>
                </Card>

                {/* References */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Users className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">References</h3>
                    </div>

                    {Array.isArray(formData.references) && formData.references.map((reference: Record<string, any>, index: number) => (
                        <div key={index} className="grid gap-4 md:grid-cols-2 mb-4 p-4 border rounded-lg">
                            <div>
                                <FormField
                                    id={`ref-${index}-name`}
                                    label={`Name${index === 0 ? '' : ''}`}
                                    type="text"
                                    value={reference.name}
                                    onChange={(value) => handleArrayChange('references', index, 'name', value)}
                                    required={index === 0}
                                    autoCapitalize={true}
                                />
                            </div>

                            <div>
                                <FormField
                                    id={`ref-${index}-phone`}
                                    label={`Phone Number${index === 0 ? '' : ''}`}
                                    type="phone"
                                    value={reference.phoneNumber}
                                    onChange={(value) => handleArrayChange('references', index, 'phoneNumber', value)}
                                    required={index === 0}
                                />
                            </div>
                        </div>
                    ))}
                </Card>

                {/* Directors' Personal Details */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">DIRECTORS' PERSONAL DETAILS</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <Label htmlFor="directorTitle">Title (Mr./Mrs./Dr/Prof)</Label>
                            <Input
                                id="directorTitle"
                                value={formData.directorsPersonalDetails.title}
                                onChange={(e) => handleNestedChange('directorsPersonalDetails', 'title', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorFirstName"
                                label="First Name"
                                type="text"
                                value={formData.directorsPersonalDetails.firstName}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'firstName', value)}
                                required
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorSurname"
                                label="Surname"
                                type="text"
                                value={formData.directorsPersonalDetails.surname}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'surname', value)}
                                required
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorMaidenName"
                                label="Maiden Name"
                                type="text"
                                value={formData.directorsPersonalDetails.maidenName}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'maidenName', value)}
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <Label htmlFor="directorGender">Gender *</Label>
                            <Select value={formData.directorsPersonalDetails.gender} onValueChange={(value) => handleNestedChange('directorsPersonalDetails', 'gender', value)}>
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
                                id="directorDateOfBirth"
                                label="Date Of Birth"
                                type="dial-date"
                                value={formData.directorsPersonalDetails.dateOfBirth}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'dateOfBirth', value)}
                                required
                                maxDate={`${new Date().getFullYear() - 18}-12-31`}
                                minDate="1930-01-01"
                                defaultAge={20}
                            />
                        </div>

                        <div>
                            <Label htmlFor="directorMaritalStatus">Marital Status</Label>
                            <Input
                                id="directorMaritalStatus"
                                value={formData.directorsPersonalDetails.maritalStatus}
                                onChange={(e) => handleNestedChange('directorsPersonalDetails', 'maritalStatus', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="directorNationality">Nationality</Label>
                            <Input
                                id="directorNationality"
                                value={formData.directorsPersonalDetails.nationality}
                                onChange={(e) => handleNestedChange('directorsPersonalDetails', 'nationality', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorIdNumber"
                                label="ID Number"
                                type="text"
                                value={formData.directorsPersonalDetails.idNumber}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'idNumber', value)}
                                required
                                capitalizeCheckLetter={true}
                                placeholder="e.g. 12-345678 A 12"
                                title="Zimbabwe ID format: 12-345678 A 12"
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorCellNumber"
                                label="Cell Number"
                                type="phone"
                                value={formData.directorsPersonalDetails.cellNumber}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'cellNumber', value)}
                                required
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorWhatsApp"
                                label="WhatsApp"
                                type="phone"
                                value={formData.directorsPersonalDetails.whatsApp}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'whatsApp', value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="directorEducation">Highest Educational Qualification</Label>
                            <Input
                                id="directorEducation"
                                value={formData.directorsPersonalDetails.highestEducation}
                                onChange={(e) => handleNestedChange('directorsPersonalDetails', 'highestEducation', e.target.value)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="directorCitizenship">Citizenship</Label>
                            <Input
                                id="directorCitizenship"
                                value={formData.directorsPersonalDetails.citizenship}
                                onChange={(e) => handleNestedChange('directorsPersonalDetails', 'citizenship', e.target.value)}
                            />
                        </div>

                        <div>
                            <FormField
                                id="directorEmail"
                                label="Email Address"
                                type="email"
                                value={formData.directorsPersonalDetails.emailAddress}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'emailAddress', value)}
                            />
                        </div>

                        <div className="lg:col-span-2">
                            <FormField
                                id="directorAddress"
                                label="Residential Address"
                                type="address"
                                value={formData.directorsPersonalDetails.residentialAddress}
                                onChange={(value) => handleNestedChange('directorsPersonalDetails', 'residentialAddress', value)}
                            />
                        </div>

                        <div>
                            <Label>Passport Photo</Label>
                            <div className="h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-500">
                                Photo Area
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Period at Current Address</Label>
                                <div className="flex gap-2">
                                    <Input placeholder="Years" />
                                    <Input placeholder="Months" />
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Period at Previous Address</Label>
                                <div className="flex gap-2">
                                    <Input placeholder="Years" />
                                    <Input placeholder="Months" />
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Spouse and Next of Kin Details */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">
                            {formData.directorsPersonalDetails.maritalStatus === 'Married'
                                ? (formData.directorsPersonalDetails.gender === 'Male' ? "Wife's" : formData.directorsPersonalDetails.gender === 'Female' ? "Husband's" : "Spouse")
                                : "Next of Kin"} and Next of Kin Details
                        </h3>
                    </div>
                    <p className="text-xs text-gray-500 italic mb-4">
                        *this is for statistical and record keeping purposes only*
                    </p>

                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Spouse Details */}
                        <div>
                            <h4 className="font-semibold mb-3 text-emerald-700">
                                {formData.directorsPersonalDetails.maritalStatus === 'Married'
                                    ? (formData.directorsPersonalDetails.gender === 'Male' ? "Wife's Details" : formData.directorsPersonalDetails.gender === 'Female' ? "Husband's Details" : "Spouse Details")
                                    : "Next of Kin Details"}
                            </h4>
                            <div className="space-y-3">
                                <div>
                                    <FormField
                                        id="spouseFullName"
                                        label="Full Name"
                                        type="text"
                                        value={formData.spouseAndNextOfKin.spouse.fullName}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    spouse: {
                                                        ...prev.spouseAndNextOfKin.spouse,
                                                        fullName: value
                                                    }
                                                }
                                            }));
                                        }}
                                        autoCapitalize={true}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="spousePhone"
                                        label="Phone Number"
                                        type="phone"
                                        value={formData.spouseAndNextOfKin.spouse.phoneNumber}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    spouse: {
                                                        ...prev.spouseAndNextOfKin.spouse,
                                                        phoneNumber: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="spouseEmail"
                                        label="Email Address"
                                        type="email"
                                        value={formData.spouseAndNextOfKin.spouse.emailAddress}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    spouse: {
                                                        ...prev.spouseAndNextOfKin.spouse,
                                                        emailAddress: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="spouseAddress"
                                        label="Address"
                                        type="text"
                                        value={typeof formData.spouseAndNextOfKin.spouse.address === 'string' ? formData.spouseAndNextOfKin.spouse.address : ''}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    spouse: {
                                                        ...prev.spouseAndNextOfKin.spouse,
                                                        address: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Next of Kin 1 */}
                        <div>
                            <h4 className="font-semibold mb-3 text-emerald-700">NEXT OF KIN 1</h4>
                            <div className="space-y-3">
                                <div>
                                    <FormField
                                        id="nextOfKin1Name"
                                        label="Full Name"
                                        type="text"
                                        value={formData.spouseAndNextOfKin.nextOfKin1.fullName}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    nextOfKin1: {
                                                        ...prev.spouseAndNextOfKin.nextOfKin1,
                                                        fullName: value
                                                    }
                                                }
                                            }));
                                        }}
                                        autoCapitalize={true}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="nextOfKin1Relationship"
                                        label="Relationship"
                                        type="select"
                                        value={formData.spouseAndNextOfKin.nextOfKin1.relationship}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    nextOfKin1: {
                                                        ...prev.spouseAndNextOfKin.nextOfKin1,
                                                        relationship: value
                                                    }
                                                }
                                            }));
                                        }}
                                        options={[
                                            { value: "Spouse", label: "Spouse" },
                                            { value: "Parent", label: "Parent" },
                                            { value: "Child", label: "Child" },
                                            { value: "Relative", label: "Relative" },
                                            { value: "Work colleague", label: "Work colleague" },
                                            { value: "Friend", label: "Friend" }
                                        ]}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="nextOfKin1Phone"
                                        label="Phone Number"
                                        type="phone"
                                        value={formData.spouseAndNextOfKin.nextOfKin1.phoneNumber}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    nextOfKin1: {
                                                        ...prev.spouseAndNextOfKin.nextOfKin1,
                                                        phoneNumber: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="nextOfKin1Email"
                                        label="Email Address"
                                        type="email"
                                        value={formData.spouseAndNextOfKin.nextOfKin1.emailAddress}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    nextOfKin1: {
                                                        ...prev.spouseAndNextOfKin.nextOfKin1,
                                                        emailAddress: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                                <div>
                                    <FormField
                                        id="nextOfKin1Address"
                                        label="Address"
                                        type="text"
                                        value={typeof formData.spouseAndNextOfKin.nextOfKin1.address === 'string' ? formData.spouseAndNextOfKin.nextOfKin1.address : ''}
                                        onChange={(value) => {
                                            setFormData(prev => ({
                                                ...prev,
                                                spouseAndNextOfKin: {
                                                    ...prev.spouseAndNextOfKin,
                                                    nextOfKin1: {
                                                        ...prev.spouseAndNextOfKin.nextOfKin1,
                                                        address: value
                                                    }
                                                }
                                            }));
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Next of Kin 2 */}
                    <div className="mt-6">
                        <h4 className="font-semibold mb-3 text-emerald-700">NEXT OF KIN 2</h4>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <FormField
                                    id="nextOfKin2Name"
                                    label="Full Name"
                                    type="text"
                                    value={formData.spouseAndNextOfKin.nextOfKin2.fullName}
                                    onChange={(value) => {
                                        setFormData(prev => ({
                                            ...prev,
                                            spouseAndNextOfKin: {
                                                ...prev.spouseAndNextOfKin,
                                                nextOfKin2: {
                                                    ...prev.spouseAndNextOfKin.nextOfKin2,
                                                    fullName: value
                                                }
                                            }
                                        }));
                                    }}
                                    autoCapitalize={true}
                                />
                            </div>
                            <div>
                                <FormField
                                    id="nextOfKin2Relationship"
                                    label="Relationship"
                                    type="select"
                                    value={formData.spouseAndNextOfKin.nextOfKin2.relationship}
                                    onChange={(value) => {
                                        setFormData(prev => ({
                                            ...prev,
                                            spouseAndNextOfKin: {
                                                ...prev.spouseAndNextOfKin,
                                                nextOfKin2: {
                                                    ...prev.spouseAndNextOfKin.nextOfKin2,
                                                    relationship: value
                                                }
                                            }
                                        }));
                                    }}
                                    options={[
                                        { value: "Spouse", label: "Spouse" },
                                        { value: "Parent", label: "Parent" },
                                        { value: "Child", label: "Child" },
                                        { value: "Relative", label: "Relative" },
                                        { value: "Work colleague", label: "Work colleague" },
                                        { value: "Friend", label: "Friend" }
                                    ]}
                                />
                            </div>
                            <div>
                                <FormField
                                    id="nextOfKin2Phone"
                                    label="Phone Number"
                                    type="phone"
                                    value={formData.spouseAndNextOfKin.nextOfKin2.phoneNumber}
                                    onChange={(value) => {
                                        setFormData(prev => ({
                                            ...prev,
                                            spouseAndNextOfKin: {
                                                ...prev.spouseAndNextOfKin,
                                                nextOfKin2: {
                                                    ...prev.spouseAndNextOfKin.nextOfKin2,
                                                    phoneNumber: value
                                                }
                                            }
                                        }));
                                    }}
                                />
                            </div>
                            <div>
                                <FormField
                                    id="nextOfKin2Email"
                                    label="Email Address"
                                    type="email"
                                    value={formData.spouseAndNextOfKin.nextOfKin2.emailAddress}
                                    onChange={(value) => {
                                        setFormData(prev => ({
                                            ...prev,
                                            spouseAndNextOfKin: {
                                                ...prev.spouseAndNextOfKin,
                                                nextOfKin2: {
                                                    ...prev.spouseAndNextOfKin.nextOfKin2,
                                                    emailAddress: value
                                                }
                                            }
                                        }));
                                    }}
                                />
                            </div>
                        </div>
                        <div className="mt-3">
                            <FormField
                                id="nextOfKin2Address"
                                label="Address"
                                type="text"
                                value={typeof formData.spouseAndNextOfKin.nextOfKin2.address === 'string' ? formData.spouseAndNextOfKin.nextOfKin2.address : ''}
                                onChange={(value) => {
                                    setFormData(prev => ({
                                        ...prev,
                                        spouseAndNextOfKin: {
                                            ...prev.spouseAndNextOfKin,
                                            nextOfKin2: {
                                                ...prev.spouseAndNextOfKin.nextOfKin2,
                                                address: value
                                            }
                                        }
                                    }));
                                }}
                            />
                        </div>
                    </div>
                </Card>

                {/* Employment Details */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">EMPLOYMENT DETAILS</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <FormField
                                id="businessEmployerName"
                                label="Business/Employer's Name"
                                type="text"
                                value={formData.employmentDetails.businessEmployerName}
                                onChange={(value) => handleNestedChange('employmentDetails', 'businessEmployerName', value)}
                                autoCapitalize={true}
                            />
                        </div>
                        <div>
                            <FormField
                                id="jobTitleEmployment"
                                label="Job Title"
                                type="text"
                                value={formData.employmentDetails.jobTitle}
                                onChange={(value) => handleNestedChange('employmentDetails', 'jobTitle', value)}
                                autoCapitalize={true}
                            />
                        </div>
                        <div>
                            <FormField
                                id="businessEmployerAddress"
                                label="Business/Employer's Address"
                                type="address"
                                value={formData.employmentDetails.businessEmployerAddress}
                                onChange={(value) => handleNestedChange('employmentDetails', 'businessEmployerAddress', value)}
                            />
                        </div>
                        <div>
                            <FormField
                                id="dateOfEmploymentDirector"
                                label="Date of Employment"
                                type="dial-date"
                                value={formData.employmentDetails.dateOfEmployment}
                                onChange={(value) => handleNestedChange('employmentDetails', 'dateOfEmployment', value)}
                                maxDate={currentDate}
                                defaultAge={0}
                            />
                        </div>
                        <div>
                            <FormField
                                id="immediateManager"
                                label="Name of Immediate Manager"
                                type="text"
                                value={formData.employmentDetails.immediateManager}
                                onChange={(value) => handleNestedChange('employmentDetails', 'immediateManager', value)}
                                autoCapitalize={true}
                            />
                        </div>
                        <div>
                            <FormField
                                id="managerPhone"
                                label="Phone Number of Immediate Manager"
                                type="phone"
                                value={formData.employmentDetails.phoneNumberOfManager}
                                onChange={(value) => handleNestedChange('employmentDetails', 'phoneNumberOfManager', value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* Property Ownership */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">PROPERTY OWNERSHIP</h3>
                    </div>

                    <div className="flex gap-4">
                        {['Rented', 'Employer Owned', 'Mortgaged', 'Owned Without Mortgage', 'Parents owned'].map((option) => (
                            <label key={option} className="flex items-center space-x-2">
                                <Checkbox
                                    checked={formData.propertyOwnership === option}
                                    onCheckedChange={(checked) => checked && handleInputChange('propertyOwnership', option)}
                                />
                                <span className="text-sm">{option}</span>
                            </label>
                        ))}
                    </div>
                </Card>

                {/* Banking/Mobile Account Details */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">BANKING/MOBILE ACCOUNT DETAILS</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <FormField
                                id="bankName"
                                label="Bank"
                                type="text"
                                value={formData.bankingDetails.bank}
                                onChange={(value) => handleNestedChange('bankingDetails', 'bank', value)}
                                autoCapitalize={true}
                            />
                        </div>
                        <div>
                            <FormField
                                id="bankBranch"
                                label="Branch"
                                type="text"
                                value={formData.bankingDetails.branch}
                                onChange={(value) => handleNestedChange('bankingDetails', 'branch', value)}
                                autoCapitalize={true}
                            />
                        </div>
                        <div>
                            <FormField
                                id="bankAccountNumber"
                                label="Account Number"
                                type="text"
                                value={formData.bankingDetails.accountNumber}
                                onChange={(value) => handleNestedChange('bankingDetails', 'accountNumber', value)}
                            />
                        </div>
                    </div>
                </Card>

                {/* Loans with Other Institutions */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">LOANS WITH OTHER INSTITUTIONS (ALSO INCLUDE QUPA LOAN)</h3>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr className="bg-gray-50">
                                    <th className="border border-gray-300 p-2 text-left">INSTITUTION</th>
                                    <th className="border border-gray-300 p-2 text-left">MONTHLY INSTALLMENT</th>
                                    <th className="border border-gray-300 p-2 text-left">CURRENT LOAN BALANCE</th>
                                    <th className="border border-gray-300 p-2 text-left">MATURITY DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                {[...Array(3)].map((_, index) => (
                                    <tr key={index}>
                                        <td className="border border-gray-300 p-2">
                                            <FormField
                                                id={`loan-${index}-institution`}
                                                label=""
                                                type="text"
                                                value={formData.otherLoans[index]?.institution || ''}
                                                onChange={(value) => handleArrayChange('otherLoans', index, 'institution', value)}
                                                autoCapitalize={true}
                                            />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input className="w-full" type="number" />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <Input className="w-full" type="number" />
                                        </td>
                                        <td className="border border-gray-300 p-2">
                                            <FormField
                                                id={`loan-${index}-maturity`}
                                                label=""
                                                type="dial-date"
                                                value={formData.otherLoans[index]?.maturityDate || ''}
                                                onChange={(value) => handleArrayChange('otherLoans', index, 'maturityDate', value)}
                                                minDate={currentDate}
                                                maxDate="2050-12-31"
                                                defaultAge={0}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                {/* Other Business Interests */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">OTHER BUSINESS INTERESTS</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <FormField
                                id="otherBusinessName"
                                label="Business Name"
                                type="text"
                                value={formData.otherBusinessName}
                                onChange={(value) => handleInputChange('otherBusinessName', value)}
                                autoCapitalize={true}
                            />
                        </div>

                        <div>
                            <FormField
                                id="otherBusinessPhone"
                                label="Phone Number"
                                type="phone"
                                value={formData.otherBusinessPhone}
                                onChange={(value) => handleInputChange('otherBusinessPhone', value)}
                            />
                        </div>

                        <div className="lg:col-span-3">
                            <FormField
                                id="otherBusinessAddress"
                                label="Business Address"
                                type="address"
                                value={formData.otherBusinessAddress}
                                onChange={(value) => handleInputChange('otherBusinessAddress', value)}
                            />
                        </div>
                    </div>

                    <div className="mt-4">
                        <h4 className="font-semibold mb-3">Number of Employees</h4>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <FormField
                                    id="fullTimeEmployees"
                                    label="Full Time"
                                    type="number"
                                    value={formData.numberOfEmployees.fullTime}
                                    onChange={(value) => handleNestedChange('numberOfEmployees', 'fullTime', value)}
                                />
                            </div>

                            <div>
                                <FormField
                                    id="partTimeEmployees"
                                    label="Part Time"
                                    type="number"
                                    value={formData.numberOfEmployees.partTime}
                                    onChange={(value) => handleNestedChange('numberOfEmployees', 'partTime', value)}
                                />
                            </div>

                            <div>
                                <FormField
                                    id="nonPaidEmployees"
                                    label="Non Paid"
                                    type="number"
                                    value={formData.numberOfEmployees.nonPaid}
                                    onChange={(value) => handleNestedChange('numberOfEmployees', 'nonPaid', value)}
                                />
                            </div>

                            <div>
                                <FormField
                                    id="totalEmployees"
                                    label="Total"
                                    type="number"
                                    value={formData.numberOfEmployees.total}
                                    onChange={(value) => handleNestedChange('numberOfEmployees', 'total', value)}
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Declaration */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">DECLARATION</h3>
                    </div>

                    <div className="space-y-4">
                        <p className="text-sm text-gray-700">
                            We declare that the information given above is accurate and correct. We are aware that falsifying information automatically leads to decline of our loan application. We authorise Qupa Microfinance to obtain and use the information obtained for the purposes of this application with any recognised credit bureau. We authorise Qupa microfinance to references from friends, relatives, neighbours and business partners including visits to our homes and verification of my assets. We have read and fully understood the above together with all the conditions, and We agree to be bound by Qupa Micro-Finance terms and conditions.
                        </p>

                        <div>
                            <FormField
                                id="declarationAcknowledged"
                                label="I acknowledge and agree to the above declaration"
                                type="checkbox"
                                value={formData.declaration.acknowledged.toString()}
                                onChange={(value) => handleNestedChange('declaration', 'acknowledged', value === 'true')}
                                checkboxVariant="prominent"
                                checkboxDescription="We declare that the information given above is accurate and correct. We are aware that falsifying information automatically leads to decline of our loan application."
                                required
                            />
                        </div>
                    </div>
                </Card>

                {/* Directors Signature */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">DIRECTORS SIGNATURE</h3>
                    </div>

                    {Array.isArray(formData.directorsSignatures) && formData.directorsSignatures.map((director: Record<string, any>, index: number) => (
                        <div key={index} className="grid gap-4 md:grid-cols-4 mb-4 p-4 border rounded-lg">
                            <div>
                                <FormField
                                    id={`director-${index}-name`}
                                    label="Director: Name"
                                    type="text"
                                    value={director.name}
                                    onChange={(value) => handleArrayChange('directorsSignatures', index, 'name', value)}
                                    autoCapitalize={true}
                                />
                            </div>

                            <div>
                                <Label htmlFor={`director-${index}-signature`}>Signature</Label>
                                <div className="h-10 border-2 border-dashed border-gray-300 rounded flex items-center justify-center text-gray-500 text-sm">
                                    Signature Area
                                </div>
                            </div>

                            <div>
                                <FormField
                                    id={`director-${index}-date`}
                                    label="Date"
                                    type="dial-date"
                                    value={director.date}
                                    onChange={(value) => handleArrayChange('directorsSignatures', index, 'date', value)}
                                    maxDate={currentDate}
                                    defaultAge={0}
                                />
                            </div>
                        </div>
                    ))}
                </Card>

                {/* KYC Checklist */}
                <Card className="p-6">
                    <div className="bg-emerald-100 p-4 rounded-lg mb-4">
                        <h3 className="text-lg font-semibold text-emerald-800">KYC CHECKLIST</h3>
                    </div>

                    <div className="grid gap-3 md:grid-cols-2">
                        <div className="space-y-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="copyOfId" />
                                <Label htmlFor="copyOfId">Copy of ID, License, Valid Passport</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="articlesOfAssociation" />
                                <Label htmlFor="articlesOfAssociation">Articles of association/PBC</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="bankStatement" />
                                <Label htmlFor="bankStatement">Stamped 3 months' Bank Statement</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="groupConstitution" />
                                <Label htmlFor="groupConstitution">Group Constitution</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="financialStatement" />
                                <Label htmlFor="financialStatement">Financial Statement</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="certificateOfIncorporation" />
                                <Label htmlFor="certificateOfIncorporation">Certificate of Incorporation</Label>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox id="ecocashStatements" />
                                <Label htmlFor="ecocashStatements">Ecocash Statements where applicable</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox id="resolutionToBorrow" />
                                <Label htmlFor="resolutionToBorrow">Resolution to borrow</Label>
                            </div>

                            <div className="mt-4">
                                <p className="font-medium mb-2">Company documents:</p>
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox id="cr11" />
                                        <Label htmlFor="cr11">CR11</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox id="cr6" />
                                        <Label htmlFor="cr6">CR6</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox id="cr5" />
                                        <Label htmlFor="cr5">CR5</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox id="moa" />
                                        <Label htmlFor="moa">MOA</Label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end">
                        <div className="w-40 h-20 border-2 border-gray-300 rounded flex items-center justify-center text-gray-500">
                            Qupa Date Stamp:
                        </div>
                    </div>
                </Card>

                {/* Security (Assets Pledged) */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Shield className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Security (Assets Pledged)</h3>
                    </div>

                    {Array.isArray(formData.securityAssets) && formData.securityAssets.map((asset: Record<string, any>, index: number) => (
                        <div key={index} className="grid gap-4 md:grid-cols-3 mb-4 p-4 border rounded-lg">
                            <div>
                                <Label htmlFor={`asset-${index}-description`}>Description</Label>
                                <Input
                                    id={`asset-${index}-description`}
                                    value={asset.description}
                                    onChange={(e) => handleArrayChange('securityAssets', index, 'description', e.target.value)}
                                />
                            </div>

                            <div>
                                <Label htmlFor={`asset-${index}-serial`}>Serial/Reg Number</Label>
                                <Input
                                    id={`asset-${index}-serial`}
                                    value={asset.serialNumber}
                                    onChange={(e) => handleArrayChange('securityAssets', index, 'serialNumber', e.target.value)}
                                />
                            </div>

                            <div>
                                <Label htmlFor={`asset-${index}-value`}>Estimated Asset Value</Label>
                                <Input
                                    id={`asset-${index}-value`}
                                    type="number"
                                    value={asset.estimatedValue}
                                    onChange={(e) => handleArrayChange('securityAssets', index, 'estimatedValue', e.target.value)}
                                />
                            </div>
                        </div>
                    ))}
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

export default SMEBusinessForm;
