import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeft, FileText, MapPin, Search, Briefcase, AlertTriangle, Plus, Trash2 } from 'lucide-react';
import FormField from '@/components/ApplicationWizard/components/FormField';

interface FCBReportFormProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface AddressEntry {
    date: string;
    streetName: string;
    city: string;
    country: string;
    propertyRights: string;
}

interface IncomeEntry {
    date: string;
    employer: string;
    industry: string;
    salaryBand: string;
    occupation: string;
}

const FCBReportForm: React.FC<FCBReportFormProps> = ({ data, onNext, onBack, loading }) => {
    const currentDate = new Date().toISOString().split('T')[0];

    // Pre-fill from previous form data if available
    const [formData, setFormData] = useState({
        // Individual Details
        nationality: data.formResponses?.nationality || 'Zimbabwe',
        dateOfBirth: data.formResponses?.dateOfBirth || '',
        nationalId: data.formResponses?.idNumber || data.formResponses?.nationalIdNumber || '',
        gender: data.formResponses?.gender || '',
        mobile: data.formResponses?.cellNumber || data.formResponses?.mobile || '',
        propertyStatus: data.formResponses?.propertyOwnership || '',
        propertyDensity: '',
        address: data.formResponses?.permanentAddress || data.formResponses?.residentialAddress || '',
        maritalStatus: data.formResponses?.maritalStatus || '',

        // FCB Score (to be filled by admin after credit check)
        fcbScore: '',
        fcbStatus: '',

        // Addresses (Last 5 years)
        addresses: [
            { date: currentDate, streetName: '', city: '', country: 'Zimbabwe', propertyRights: '' }
        ] as AddressEntry[],

        // Reported Incomes
        incomes: [
            { date: currentDate, employer: data.formResponses?.employerName || '', industry: '', salaryBand: '', occupation: data.formResponses?.jobTitle || '' }
        ] as IncomeEntry[],

        // Credit Events
        hasActiveCredit: 'no',
        hasSettledCredit: 'no',
        hasExposures: 'no',
        hasConvictions: 'no',

        // Notes
        additionalNotes: ''
    });

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleAddressChange = (index: number, field: keyof AddressEntry, value: string) => {
        setFormData(prev => ({
            ...prev,
            addresses: prev.addresses.map((addr, i) =>
                i === index ? { ...addr, [field]: value } : addr
            )
        }));
    };

    const addAddress = () => {
        setFormData(prev => ({
            ...prev,
            addresses: [
                ...prev.addresses,
                { date: '', streetName: '', city: '', country: 'Zimbabwe', propertyRights: '' }
            ]
        }));
    };

    const removeAddress = (index: number) => {
        if (formData.addresses.length > 1) {
            setFormData(prev => ({
                ...prev,
                addresses: prev.addresses.filter((_, i) => i !== index)
            }));
        }
    };

    const handleIncomeChange = (index: number, field: keyof IncomeEntry, value: string) => {
        setFormData(prev => ({
            ...prev,
            incomes: prev.incomes.map((inc, i) =>
                i === index ? { ...inc, [field]: value } : inc
            )
        }));
    };

    const addIncome = () => {
        setFormData(prev => ({
            ...prev,
            incomes: [
                ...prev.incomes,
                { date: '', employer: '', industry: '', salaryBand: '', occupation: '' }
            ]
        }));
    };

    const removeIncome = (index: number) => {
        if (formData.incomes.length > 1) {
            setFormData(prev => ({
                ...prev,
                incomes: prev.incomes.filter((_, i) => i !== index)
            }));
        }
    };

    const getFCBStatusColor = (status: string) => {
        switch (status.toUpperCase()) {
            case 'GOOD':
            case 'GREEN':
                return 'bg-green-100 text-green-800 border-green-300';
            case 'FAIR':
                return 'bg-yellow-100 text-yellow-800 border-yellow-300';
            case 'ADVERSE':
                return 'bg-red-100 text-red-800 border-red-300';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-300';
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const fcbReportData = {
            fcbReport: {
                ...formData,
                reportDate: new Date().toISOString(),
                reportType: 'INDIVIDUAL_ADD_INFO_REPORT',
                subscriber: 'ZB Bank',
                branch: 'BancoZim',
            }
        };

        onNext(fcbReportData);
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">FCB Credit Report Form</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-2">
                    Financial Clearing Bureau Individual Report
                </p>
                <p className="text-sm text-blue-600 dark:text-blue-400">
                    This information will be used for credit verification
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">

                {/* Individual Details */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <FileText className="h-6 w-6 text-teal-600 mr-3" />
                        <h3 className="text-lg font-semibold">Individual Details</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <Label htmlFor="nationality">Nationality</Label>
                            <Input
                                id="nationality"
                                value={formData.nationality}
                                onChange={(e) => handleInputChange('nationality', e.target.value)}
                                readOnly
                                className="bg-gray-50"
                            />
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
                            />
                        </div>

                        <div>
                            <Label htmlFor="nationalId">National ID *</Label>
                            <Input
                                id="nationalId"
                                value={formData.nationalId}
                                onChange={(e) => handleInputChange('nationalId', e.target.value)}
                                required
                                placeholder="e.g. 12-345678 A 12"
                            />
                        </div>

                        <div>
                            <Label htmlFor="gender">Gender</Label>
                            <Select value={formData.gender} onValueChange={(value) => handleInputChange('gender', value)}>
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
                                id="mobile"
                                label="Mobile Number *"
                                type="phone"
                                value={formData.mobile}
                                onChange={(value) => handleInputChange('mobile', value)}
                                required
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
                            <Label htmlFor="propertyStatus">Property Status</Label>
                            <Select value={formData.propertyStatus} onValueChange={(value) => handleInputChange('propertyStatus', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Owned">Owned</SelectItem>
                                    <SelectItem value="Rented">Rented</SelectItem>
                                    <SelectItem value="Mortgaged">Mortgaged</SelectItem>
                                    <SelectItem value="Employer Owned">Employer Owned</SelectItem>
                                    <SelectItem value="Parents Owned">Parents Owned</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="propertyDensity">Property Density</Label>
                            <Select value={formData.propertyDensity} onValueChange={(value) => handleInputChange('propertyDensity', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select density" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="High">High</SelectItem>
                                    <SelectItem value="Medium">Medium</SelectItem>
                                    <SelectItem value="Low">Low</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="md:col-span-2 lg:col-span-3">
                            <Label htmlFor="address">Current Address *</Label>
                            <Input
                                id="address"
                                value={formData.address}
                                onChange={(e) => handleInputChange('address', e.target.value)}
                                required
                                placeholder="Full residential address"
                            />
                        </div>
                    </div>
                </Card>

                {/* Addresses (Last 5 Years) */}
                <Card className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center">
                            <MapPin className="h-6 w-6 text-teal-600 mr-3" />
                            <h3 className="text-lg font-semibold">Addresses (Last 5 Years)</h3>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addAddress}
                            className="flex items-center gap-1"
                        >
                            <Plus className="h-4 w-4" /> Add Address
                        </Button>
                    </div>

                    <div className="space-y-4">
                        {formData.addresses.map((address, index) => (
                            <div key={index} className="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-sm font-medium text-gray-600">Address {index + 1}</span>
                                    {formData.addresses.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeAddress(index)}
                                            className="text-red-500 hover:text-red-700"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    )}
                                </div>
                                <div className="grid gap-3 md:grid-cols-5">
                                    <div>
                                        <Label>Date</Label>
                                        <Input
                                            type="date"
                                            value={address.date}
                                            onChange={(e) => handleAddressChange(index, 'date', e.target.value)}
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <Label>Street Name / Address</Label>
                                        <Input
                                            value={address.streetName}
                                            onChange={(e) => handleAddressChange(index, 'streetName', e.target.value)}
                                            placeholder="Street address"
                                        />
                                    </div>
                                    <div>
                                        <Label>City</Label>
                                        <Input
                                            value={address.city}
                                            onChange={(e) => handleAddressChange(index, 'city', e.target.value)}
                                            placeholder="City"
                                        />
                                    </div>
                                    <div>
                                        <Label>Property Rights</Label>
                                        <Select value={address.propertyRights} onValueChange={(value) => handleAddressChange(index, 'propertyRights', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Owned">Owned</SelectItem>
                                                <SelectItem value="Rented">Rented</SelectItem>
                                                <SelectItem value="Unknown">Unknown</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Reported Incomes */}
                <Card className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center">
                            <Briefcase className="h-6 w-6 text-teal-600 mr-3" />
                            <h3 className="text-lg font-semibold">Reported Incomes (Last 5 Years)</h3>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addIncome}
                            className="flex items-center gap-1"
                        >
                            <Plus className="h-4 w-4" /> Add Income
                        </Button>
                    </div>

                    <div className="space-y-4">
                        {formData.incomes.map((income, index) => (
                            <div key={index} className="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-sm font-medium text-gray-600">Income Record {index + 1}</span>
                                    {formData.incomes.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeIncome(index)}
                                            className="text-red-500 hover:text-red-700"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    )}
                                </div>
                                <div className="grid gap-3 md:grid-cols-5">
                                    <div>
                                        <Label>Date</Label>
                                        <Input
                                            type="date"
                                            value={income.date}
                                            onChange={(e) => handleIncomeChange(index, 'date', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label>Employer</Label>
                                        <Input
                                            value={income.employer}
                                            onChange={(e) => handleIncomeChange(index, 'employer', e.target.value)}
                                            placeholder="Employer name"
                                        />
                                    </div>
                                    <div>
                                        <Label>Industry</Label>
                                        <Input
                                            value={income.industry}
                                            onChange={(e) => handleIncomeChange(index, 'industry', e.target.value)}
                                            placeholder="Industry"
                                        />
                                    </div>
                                    <div>
                                        <Label>Salary Band</Label>
                                        <Select value={income.salaryBand} onValueChange={(value) => handleIncomeChange(index, 'salaryBand', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="0 to 500">$0 - $500</SelectItem>
                                                <SelectItem value="501 to 1000">$501 - $1,000</SelectItem>
                                                <SelectItem value="1001 to 2500">$1,001 - $2,500</SelectItem>
                                                <SelectItem value="2501 to 5000">$2,501 - $5,000</SelectItem>
                                                <SelectItem value="5001 to 7500">$5,001 - $7,500</SelectItem>
                                                <SelectItem value="7501 to 10000">$7,501 - $10,000</SelectItem>
                                                <SelectItem value="Over 10000">Over $10,000</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Occupation</Label>
                                        <Input
                                            value={income.occupation}
                                            onChange={(e) => handleIncomeChange(index, 'occupation', e.target.value)}
                                            placeholder="Job title"
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Credit Events Declaration */}
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-teal-600 mr-3" />
                        <h3 className="text-lg font-semibold">Credit Events Declaration</h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Do you have any active credit accounts?</Label>
                            <Select value={formData.hasActiveCredit} onValueChange={(value) => handleInputChange('hasActiveCredit', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no">No</SelectItem>
                                    <SelectItem value="yes">Yes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Do you have any settled/paid up credit?</Label>
                            <Select value={formData.hasSettledCredit} onValueChange={(value) => handleInputChange('hasSettledCredit', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no">No</SelectItem>
                                    <SelectItem value="yes">Yes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Do you have any credit exposures?</Label>
                            <Select value={formData.hasExposures} onValueChange={(value) => handleInputChange('hasExposures', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no">No</SelectItem>
                                    <SelectItem value="yes">Yes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Do you have any convictions?</Label>
                            <Select value={formData.hasConvictions} onValueChange={(value) => handleInputChange('hasConvictions', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no">No</SelectItem>
                                    <SelectItem value="yes">Yes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p className="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Note:</strong> The FCB Score and Status will be determined after the credit check is performed.
                            The FCB Bureau will assess your credit history based on the information provided.
                        </p>
                    </div>
                </Card>

                {/* FCB Score Legend */}
                <Card className="p-6 bg-gray-50 dark:bg-gray-800">
                    <div className="flex items-center mb-4">
                        <Search className="h-6 w-6 text-teal-600 mr-3" />
                        <h3 className="text-lg font-semibold">FCB Score Legend</h3>
                    </div>

                    <div className="grid gap-2 md:grid-cols-5 text-sm">
                        <div className="p-2 bg-red-100 border border-red-300 rounded text-center">
                            <div className="font-bold text-red-800">High Risk</div>
                            <div className="text-red-700">0 - 200</div>
                        </div>
                        <div className="p-2 bg-orange-100 border border-orange-300 rounded text-center">
                            <div className="font-bold text-orange-800">Medium-High Risk</div>
                            <div className="text-orange-700">201 - 250</div>
                        </div>
                        <div className="p-2 bg-yellow-100 border border-yellow-300 rounded text-center">
                            <div className="font-bold text-yellow-800">Medium Risk</div>
                            <div className="text-yellow-700">251 - 300</div>
                        </div>
                        <div className="p-2 bg-lime-100 border border-lime-300 rounded text-center">
                            <div className="font-bold text-lime-800">Medium-Low Risk</div>
                            <div className="text-lime-700">301 - 350</div>
                        </div>
                        <div className="p-2 bg-green-100 border border-green-300 rounded text-center">
                            <div className="font-bold text-green-800">Low Risk</div>
                            <div className="text-green-700">351 - 400</div>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-2 md:grid-cols-6 text-xs">
                        <div className="p-1 bg-green-500 text-white rounded text-center">GREEN - No History</div>
                        <div className="p-1 bg-blue-500 text-white rounded text-center">GOOD - Clean History</div>
                        <div className="p-1 bg-yellow-500 text-white rounded text-center">FAIR - Prior Adverse</div>
                        <div className="p-1 bg-red-500 text-white rounded text-center">ADVERSE - Open Issues</div>
                        <div className="p-1 bg-purple-500 text-white rounded text-center">PEP - Politically Exposed</div>
                        <div className="p-1 bg-gray-500 text-white rounded text-center">INCONCLUSIVE</div>
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
                        className="bg-teal-600 hover:bg-teal-700 px-8"
                    >
                        {loading ? 'Processing...' : 'Agree & Submit Application'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default FCBReportForm;
