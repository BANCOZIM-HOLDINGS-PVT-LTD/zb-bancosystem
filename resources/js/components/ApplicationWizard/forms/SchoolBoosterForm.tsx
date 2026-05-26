import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { ChevronLeft, School, DollarSign, Users, Shield } from 'lucide-react';
import FormField from '../components/FormField';
import { formatZimbabweId } from '../utils/formatters';

interface SchoolBoosterFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
    onSaveProgress?: (rawData: any) => void;
}

const SchoolBoosterForm: React.FC<SchoolBoosterFormProps> = ({ data, onNext, onBack, loading, onSaveProgress }) => {
    const businessName = data.business;
    const currentDate = new Date().toISOString().split('T')[0];
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';
    const finalPrice = data.amount || 0;

    const _saved = data._rawFormData?._formType === 'schoolBooster' ? data._rawFormData : null;

    const [spouseError, setSpouseError] = useState('');
    const [consentError, setConsentError] = useState('');
    const [consentGiven, setConsentGiven] = useState<boolean>(_saved?.consentGiven ?? false);

    const [formData, setFormData] = useState<Record<string, any>>(_saved?.formData ?? {
        // Credit Facility Details
        creditFacilityType: `School Booster Loan - ${businessName || 'School Equipment'}`,
        loanAmount: finalPrice.toFixed ? finalPrice.toFixed(2) : finalPrice,
        loanTenure: '24',
        monthlyPayment: '',
        interestRate: '10.0',
        date: currentDate,

        // School Information
        schoolName: '',
        schoolRegistrationNumber: '',
        schoolType: data.schoolTypeName || data.schoolType || '',
        schoolAddress: '',
        schoolCity: '',
        schoolProvince: '',
        postalAddress: '',
        contactPhone: '',
        emailAddress: '',
        yearsEstablished: '',
        dateEstablished: '',
        numberOfStudents: '',
        numberOfStaff: '',
        positionInSchool: 'Principal',
        bpNumber: '',

        // Financial Information
        annualFeeRevenue: '',
        annualBudget: '',
        netSurplus: '',
        totalLiabilities: '',
        mainExpenses: '',
        purposeOfLoan: businessName ? `${businessName} - School Equipment` : '',

        // Budget Breakdown
        budgetItems: [
            { item: '', cost: '' },
            { item: '', cost: '' },
            { item: '', cost: '' },
        ],

        // Principal / Head of School Details
        principalDetails: {
            title: '',
            firstName: '',
            surname: '',
            gender: '',
            dateOfBirth: '',
            maritalStatus: '',
            nationality: 'Zimbabwean',
            idNumber: '',
            cellNumber: '',
            whatsApp: '',
            emailAddress: '',
            residentialAddress: '',
        },

        // Spouse and Next of Kin
        spouseAndNextOfKin: {
            spouse: { fullName: '', phoneNumber: '', emailAddress: '', address: '' },
            nextOfKin1: { fullName: '', relationship: '', phoneNumber: '', emailAddress: '', address: '' },
        },

        // Banking Details
        bankingDetails: { bank: '', branch: '', accountNumber: '' },

        // Loans with Other Institutions
        otherLoans: [
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' },
            { institution: '', monthlyInstallment: '', currentBalance: '', maturityDate: '' },
        ],

        // References
        references: [
            { name: '', phoneNumber: '' },
            { name: '', phoneNumber: '' },
        ],

        // KYC Documents
        kycDocuments: {
            copyOfId: false,
            bankStatement: false,
            schoolRegistrationCert: false,
            financialStatement: false,
            letterFromMinistry: false,
            resolutionToBorrow: false,
        },

        // Declaration
        declaration: { acknowledged: false },
    });

    const handleBackWithSave = () => {
        onSaveProgress?.({ _formType: 'schoolBooster', formData, consentGiven });
        onBack();
    };

    const handleInputChange = (field: string, value: string) => {
        const processedValue = field.toLowerCase().includes('idnumber') ? formatZimbabweId(value) : value;
        setFormData(prev => ({ ...prev, [field]: processedValue }));
    };

    const handleNestedChange = (section: string, field: string, value: string | boolean) => {
        setFormData(prev => ({
            ...prev,
            [section]: { ...prev[section], [field]: value },
        }));
    };

    const handleDeepNestedChange = (section: string, subsection: string, field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [section]: {
                ...prev[section],
                [subsection]: { ...prev[section]?.[subsection], [field]: value },
            },
        }));
    };

    const handleArrayChange = (arr: string, index: number, field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [arr]: prev[arr].map((item: any, i: number) => i === index ? { ...item, [field]: value } : item),
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!consentGiven) { setConsentError('Please give consent to proceed.'); return; }
        if (!formData.schoolName?.trim()) { alert('Please enter the name of the school.'); return; }
        if (!formData.principalDetails?.firstName?.trim() || !formData.principalDetails?.surname?.trim()) {
            alert('Please fill in the Principal/Head details.'); return;
        }

        onNext({
            formResponses: {
                ...formData,
                firstName: formData.principalDetails?.firstName || '',
                surname:   formData.principalDetails?.surname || '',
                mobile:    formData.principalDetails?.cellNumber || formData.contactPhone || '',
                nationalIdNumber: formData.principalDetails?.idNumber || '',
            },
            formData,
            formId: 'school_booster_application.json',
            _formType: 'schoolBooster',
        });
    };

    const SectionHeader: React.FC<{ icon: React.ReactNode; title: string }> = ({ icon, title }) => (
        <div className="flex items-center gap-3 mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <div className="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">{icon}</div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{title}</h3>
        </div>
    );

    const provinces = ['Harare','Bulawayo','Manicaland','Mashonaland Central','Mashonaland East','Mashonaland West','Masvingo','Matabeleland North','Matabeleland South','Midlands'];

    return (
        <form onSubmit={handleSubmit} className="space-y-8">
            {/* Header */}
            <div className="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl p-6">
                <h2 className="text-2xl font-bold mb-1">School Booster Application</h2>
                <p className="text-emerald-100 text-sm">{businessName} — {currencySymbol}{Number(finalPrice).toLocaleString()}</p>
            </div>

            {/* ── SCHOOL INFORMATION ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<School className="h-5 w-5 text-emerald-600" />} title="School Information" />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="md:col-span-2">
                        <FormField label="Name of School" required>
                            <Input value={formData.schoolName} onChange={e => handleInputChange('schoolName', e.target.value)} placeholder="Full registered name of the school" />
                        </FormField>
                    </div>
                    <FormField label="School Registration Number">
                        <Input value={formData.schoolRegistrationNumber} onChange={e => handleInputChange('schoolRegistrationNumber', e.target.value)} placeholder="e.g. MOE/SCH/2005/001" />
                    </FormField>
                    <FormField label="Type of School">
                        <Input value={formData.schoolType} readOnly className="bg-gray-50" />
                    </FormField>
                    <FormField label="Contact Phone" required>
                        <Input value={formData.contactPhone} onChange={e => handleInputChange('contactPhone', e.target.value)} placeholder="e.g. 0771234567" />
                    </FormField>
                    <FormField label="Email Address">
                        <Input type="email" value={formData.emailAddress} onChange={e => handleInputChange('emailAddress', e.target.value)} placeholder="school@example.com" />
                    </FormField>
                    <FormField label="School Address / Location" required>
                        <Input value={formData.schoolAddress} onChange={e => handleInputChange('schoolAddress', e.target.value)} placeholder="Street / Area" />
                    </FormField>
                    <FormField label="City / Town">
                        <Input value={formData.schoolCity} onChange={e => handleInputChange('schoolCity', e.target.value)} placeholder="e.g. Harare" />
                    </FormField>
                    <FormField label="Province">
                        <Select value={formData.schoolProvince} onValueChange={v => handleInputChange('schoolProvince', v)}>
                            <SelectTrigger><SelectValue placeholder="Select province" /></SelectTrigger>
                            <SelectContent>{provinces.map(p => <SelectItem key={p} value={p}>{p}</SelectItem>)}</SelectContent>
                        </Select>
                    </FormField>
                    <FormField label="BP Number">
                        <Input value={formData.bpNumber} onChange={e => handleInputChange('bpNumber', e.target.value)} placeholder="e.g. BP123456" />
                    </FormField>
                    <FormField label="Year Established">
                        <Input type="number" min="1900" max={new Date().getFullYear()} value={formData.yearsEstablished} onChange={e => handleInputChange('yearsEstablished', e.target.value)} placeholder="e.g. 1985" />
                    </FormField>
                    <FormField label="Number of Students">
                        <Input type="number" min="0" value={formData.numberOfStudents} onChange={e => handleInputChange('numberOfStudents', e.target.value)} placeholder="e.g. 450" />
                    </FormField>
                    <FormField label="Number of Staff">
                        <Input type="number" min="0" value={formData.numberOfStaff} onChange={e => handleInputChange('numberOfStaff', e.target.value)} placeholder="e.g. 25" />
                    </FormField>
                    <FormField label="Purpose of Loan">
                        <Input value={formData.purposeOfLoan} onChange={e => handleInputChange('purposeOfLoan', e.target.value)} placeholder="e.g. Science lab equipment" />
                    </FormField>
                </div>
            </div>

            {/* ── FINANCIAL INFORMATION ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<DollarSign className="h-5 w-5 text-emerald-600" />} title="Financial Information" />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label={`Annual Fee Revenue (${currencySymbol})`}>
                        <Input type="number" min="0" value={formData.annualFeeRevenue} onChange={e => handleInputChange('annualFeeRevenue', e.target.value)} placeholder="0.00" />
                    </FormField>
                    <FormField label={`Annual Budget (${currencySymbol})`}>
                        <Input type="number" min="0" value={formData.annualBudget} onChange={e => handleInputChange('annualBudget', e.target.value)} placeholder="0.00" />
                    </FormField>
                    <FormField label={`Net Surplus (${currencySymbol})`}>
                        <Input type="number" value={formData.netSurplus} onChange={e => handleInputChange('netSurplus', e.target.value)} placeholder="0.00" />
                    </FormField>
                    <FormField label={`Total Liabilities (${currencySymbol})`}>
                        <Input type="number" min="0" value={formData.totalLiabilities} onChange={e => handleInputChange('totalLiabilities', e.target.value)} placeholder="0.00" />
                    </FormField>
                    <div className="md:col-span-2">
                        <FormField label="Main Expenses / Commitments">
                            <Textarea value={formData.mainExpenses} onChange={e => handleInputChange('mainExpenses', e.target.value)} placeholder="Describe main recurring expenses..." rows={2} />
                        </FormField>
                    </div>
                </div>

                {/* Budget Breakdown */}
                <div className="mt-4">
                    <Label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Equipment Budget Breakdown</Label>
                    <div className="space-y-2">
                        {formData.budgetItems.map((item: any, idx: number) => (
                            <div key={idx} className="grid grid-cols-2 gap-2">
                                <Input value={item.item} onChange={e => handleArrayChange('budgetItems', idx, 'item', e.target.value)} placeholder={`Item ${idx + 1}`} />
                                <Input type="number" value={item.cost} onChange={e => handleArrayChange('budgetItems', idx, 'cost', e.target.value)} placeholder={`${currencySymbol}0.00`} />
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* ── PRINCIPAL / HEAD DETAILS ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<Users className="h-5 w-5 text-emerald-600" />} title="Principal / Head of School Details" />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Title">
                        <Select value={formData.principalDetails.title} onValueChange={v => handleNestedChange('principalDetails', 'title', v)}>
                            <SelectTrigger><SelectValue placeholder="Select title" /></SelectTrigger>
                            <SelectContent>
                                {['Mr','Mrs','Ms','Dr','Prof','Rev'].map(t => <SelectItem key={t} value={t}>{t}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField label="First Name" required>
                        <Input value={formData.principalDetails.firstName} onChange={e => handleNestedChange('principalDetails', 'firstName', e.target.value)} placeholder="First name" />
                    </FormField>
                    <FormField label="Surname" required>
                        <Input value={formData.principalDetails.surname} onChange={e => handleNestedChange('principalDetails', 'surname', e.target.value)} placeholder="Surname" />
                    </FormField>
                    <FormField label="Gender">
                        <Select value={formData.principalDetails.gender} onValueChange={v => handleNestedChange('principalDetails', 'gender', v)}>
                            <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Male">Male</SelectItem>
                                <SelectItem value="Female">Female</SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField label="Date of Birth" required>
                        <Input type="date" value={formData.principalDetails.dateOfBirth} onChange={e => handleNestedChange('principalDetails', 'dateOfBirth', e.target.value)} />
                    </FormField>
                    <FormField label="Marital Status">
                        <Select value={formData.principalDetails.maritalStatus} onValueChange={v => handleNestedChange('principalDetails', 'maritalStatus', v)}>
                            <SelectTrigger><SelectValue placeholder="Select status" /></SelectTrigger>
                            <SelectContent>
                                {['Single','Married','Divorced','Widowed'].map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField label="National ID Number" required>
                        <Input value={formData.principalDetails.idNumber} onChange={e => handleNestedChange('principalDetails', 'idNumber', formatZimbabweId(e.target.value))} placeholder="e.g. 12-345678-A-90" />
                    </FormField>
                    <FormField label="Nationality">
                        <Input value={formData.principalDetails.nationality} onChange={e => handleNestedChange('principalDetails', 'nationality', e.target.value)} placeholder="Zimbabwean" />
                    </FormField>
                    <FormField label="Cell Number" required>
                        <Input value={formData.principalDetails.cellNumber} onChange={e => handleNestedChange('principalDetails', 'cellNumber', e.target.value)} placeholder="e.g. 0771234567" />
                    </FormField>
                    <FormField label="WhatsApp Number">
                        <Input value={formData.principalDetails.whatsApp} onChange={e => handleNestedChange('principalDetails', 'whatsApp', e.target.value)} placeholder="e.g. 0771234567" />
                    </FormField>
                    <FormField label="Email Address">
                        <Input type="email" value={formData.principalDetails.emailAddress} onChange={e => handleNestedChange('principalDetails', 'emailAddress', e.target.value)} placeholder="principal@school.com" />
                    </FormField>
                    <div className="md:col-span-2">
                        <FormField label="Residential Address">
                            <Input value={formData.principalDetails.residentialAddress} onChange={e => handleNestedChange('principalDetails', 'residentialAddress', e.target.value)} placeholder="Principal's home address" />
                        </FormField>
                    </div>
                </div>
            </div>

            {/* ── NEXT OF KIN ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<Users className="h-5 w-5 text-emerald-600" />} title="Spouse & Next of Kin" />
                {spouseError && <p className="text-red-500 text-sm mb-3">{spouseError}</p>}
                <div className="mb-4">
                    <h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Next of Kin</h4>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <FormField label="Full Name">
                            <Input value={formData.spouseAndNextOfKin.nextOfKin1.fullName} onChange={e => handleDeepNestedChange('spouseAndNextOfKin', 'nextOfKin1', 'fullName', e.target.value)} placeholder="Full name" />
                        </FormField>
                        <FormField label="Relationship">
                            <Input value={formData.spouseAndNextOfKin.nextOfKin1.relationship} onChange={e => handleDeepNestedChange('spouseAndNextOfKin', 'nextOfKin1', 'relationship', e.target.value)} placeholder="e.g. Spouse, Sibling" />
                        </FormField>
                        <FormField label="Phone Number">
                            <Input value={formData.spouseAndNextOfKin.nextOfKin1.phoneNumber} onChange={e => handleDeepNestedChange('spouseAndNextOfKin', 'nextOfKin1', 'phoneNumber', e.target.value)} placeholder="e.g. 0771234567" />
                        </FormField>
                    </div>
                </div>
            </div>

            {/* ── BANKING DETAILS ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<DollarSign className="h-5 w-5 text-emerald-600" />} title="Banking Details" />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <FormField label="Bank Name">
                        <Input value={formData.bankingDetails.bank} onChange={e => handleNestedChange('bankingDetails', 'bank', e.target.value)} placeholder="e.g. ZB Bank" />
                    </FormField>
                    <FormField label="Branch">
                        <Input value={formData.bankingDetails.branch} onChange={e => handleNestedChange('bankingDetails', 'branch', e.target.value)} placeholder="e.g. Harare Main" />
                    </FormField>
                    <FormField label="Account Number">
                        <Input value={formData.bankingDetails.accountNumber} onChange={e => handleNestedChange('bankingDetails', 'accountNumber', e.target.value)} placeholder="Account number" />
                    </FormField>
                </div>
            </div>

            {/* ── REFERENCES ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<Users className="h-5 w-5 text-emerald-600" />} title="References" />
                <div className="space-y-3">
                    {formData.references.map((ref: any, idx: number) => (
                        <div key={idx} className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <FormField label={`Reference ${idx + 1} Name`}>
                                <Input value={ref.name} onChange={e => handleArrayChange('references', idx, 'name', e.target.value)} placeholder="Full name" />
                            </FormField>
                            <FormField label="Phone Number">
                                <Input value={ref.phoneNumber} onChange={e => handleArrayChange('references', idx, 'phoneNumber', e.target.value)} placeholder="e.g. 0771234567" />
                            </FormField>
                        </div>
                    ))}
                </div>
            </div>

            {/* ── KYC DOCUMENTS ── */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <SectionHeader icon={<Shield className="h-5 w-5 text-emerald-600" />} title="KYC Documents Checklist" />
                <p className="text-sm text-gray-500 mb-4">Please indicate documents you will provide with this application:</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {[
                        { key: 'copyOfId', label: "Copy of Principal's National ID" },
                        { key: 'bankStatement', label: 'Latest 3 months bank statement' },
                        { key: 'schoolRegistrationCert', label: 'School Registration Certificate' },
                        { key: 'financialStatement', label: 'Latest Financial Statement' },
                        { key: 'letterFromMinistry', label: 'Letter from Ministry of Education' },
                        { key: 'resolutionToBorrow', label: 'Resolution to Borrow' },
                    ].map(({ key, label }) => (
                        <label key={key} className="flex items-center gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <Checkbox
                                checked={formData.kycDocuments[key]}
                                onCheckedChange={checked => handleNestedChange('kycDocuments', key, !!checked)}
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">{label}</span>
                        </label>
                    ))}
                </div>
            </div>

            {/* ── DECLARATION ── */}
            <div className="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-6">
                <h3 className="text-base font-semibold text-emerald-900 dark:text-emerald-100 mb-3">Declaration</h3>
                <p className="text-sm text-emerald-800 dark:text-emerald-200 mb-4">
                    I/We hereby declare that the information provided in this application is true, correct and complete. I/We consent to the School Booster loan terms and authorise Microbiz to verify any information provided.
                </p>
                <label className="flex items-start gap-3 cursor-pointer">
                    <Checkbox checked={consentGiven} onCheckedChange={checked => { setConsentGiven(!!checked); setConsentError(''); }} className="mt-0.5" />
                    <span className="text-sm text-emerald-800 dark:text-emerald-200 font-medium">
                        I/We acknowledge and agree to the above declaration.
                    </span>
                </label>
                {consentError && <p className="text-red-500 text-sm mt-2">{consentError}</p>}
            </div>

            {/* ── NAVIGATION ── */}
            <div className="flex justify-between pt-4">
                <Button type="button" variant="outline" onClick={handleBackWithSave} disabled={loading} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
                <Button type="submit" disabled={loading} className="bg-emerald-600 hover:bg-emerald-700 text-white px-8">
                    {loading ? 'Submitting...' : 'Submit Application'}
                </Button>
            </div>
        </form>
    );
};

export default SchoolBoosterForm;
