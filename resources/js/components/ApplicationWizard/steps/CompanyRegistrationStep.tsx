import React, { useState, useEffect } from 'react';
import { User, MapPin, Building, FileText, Check, Plus, Trash2, Info } from 'lucide-react';

interface CompanyRegistrationStepProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
}

const CompanyRegistrationStep: React.FC<CompanyRegistrationStepProps> = ({ data, onNext, onBack }) => {
    // Initialize state
    const [companyType, setCompanyType] = useState<'PBC' | 'PVT LTD'>(data.companyType || 'PBC');
    const [directors, setDirectors] = useState<{ name: string, id: string }[]>(
        data.directors || [{ name: '', id: '' }]
    );
    const [preferredNames, setPreferredNames] = useState<string[]>(
        data.preferredNames || ['', '', '']
    );
    const [companyAddress, setCompanyAddress] = useState(data.companyAddress || '');
    const [idErrors, setIdErrors] = useState<{ [key: number]: string }>({});

    // National ID Regex: XX-XXXXXXX-X-XX (2 digits, dash, 6-7 digits, dash, 1 letter, dash, 2 digits)
    const nationalIdRegex = /^\d{2}-\d{6,7}-[A-Za-z]-\d{2}$/;

    // Auto-format National ID with hyphens
    const formatNationalId = (value: string): string => {
        // Remove all non-alphanumeric characters
        const cleaned = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();

        // Apply formatting: XX-XXXXXXX-X-XX
        let formatted = '';
        for (let i = 0; i < cleaned.length && i < 13; i++) {
            if (i === 2 || i === 9 || i === 10) {
                formatted += '-';
            }
            formatted += cleaned[i];
        }
        return formatted;
    };

    // Cost Constants
    const BASE_REG_FEE = 130; // Matches seeder base price (includes reg + search + delivery base?)
    // Actually, let's break it down as requested
    const REG_FEE = 100;
    const NAME_SEARCH_FEE = 12; // Example
    const DELIVERY_TRIP_COST = 6;
    const DELIVERY_TRIPS = 3;
    const TOTAL_DELIVERY = DELIVERY_TRIP_COST * DELIVERY_TRIPS;

    // Calculate Total
    const totalCost = REG_FEE + NAME_SEARCH_FEE + TOTAL_DELIVERY;

    // Handlers
    const handleAddDirector = () => {
        if (directors.length < 2) {
            setDirectors([...directors, { name: '', id: '' }]);
        }
    };

    const handleRemoveDirector = (index: number) => {
        const newDirectors = [...directors];
        newDirectors.splice(index, 1);
        setDirectors(newDirectors);
    };

    const handleDirectorChange = (index: number, field: 'name' | 'id', value: string) => {
        const newDirectors = [...directors];

        // Auto-format the ID field
        const processedValue = field === 'id' ? formatNationalId(value) : value;
        newDirectors[index] = { ...newDirectors[index], [field]: processedValue };
        setDirectors(newDirectors);

        // Validate ID format if changing ID field
        if (field === 'id') {
            const newErrors = { ...idErrors };
            if (processedValue && !nationalIdRegex.test(processedValue)) {
                newErrors[index] = 'Format: XX-XXXXXXX-X-XX (e.g., 63-123456A-A-42)';
            } else {
                delete newErrors[index];
            }
            setIdErrors(newErrors);
        }
    };

    const handleAddName = () => {
        if (preferredNames.length < 10) {
            setPreferredNames([...preferredNames, '']);
        }
    };

    const handleNameChange = (index: number, value: string) => {
        const newNames = [...preferredNames];
        newNames[index] = value;
        setPreferredNames(newNames);
    };

    const handleNextClick = () => {
        // Basic Validation
        if (directors.some(d => !d.name || !d.id)) {
            alert('Please fill in all director details.');
            return;
        }
        // Validate all IDs match regex
        const invalidIds = directors.filter(d => d.id && !nationalIdRegex.test(d.id));
        if (invalidIds.length > 0) {
            alert('Please correct the National ID format (XX-XXXXXXX-X-XX).');
            return;
        }
        if (preferredNames.filter(n => n.trim()).length < 1) {
            alert('Please provide at least one preferred name.');
            return;
        }
        if (!companyAddress) {
            alert('Please provide the company address.');
            return;
        }

        onNext({
            companyType,
            directors,
            preferredNames: preferredNames.filter(n => n.trim()),
            companyAddress,
            registrationCost: totalCost
        });
    };

    return (
        <div className="space-y-6 sm:space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-24 sm:pb-8">
            <div className="text-center">
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Company Registration</h2>
                <p className="mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">Complete the details below.</p>
            </div>

            {/* 1. Company Type */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <Building className="w-5 h-5 text-emerald-600" />
                    1. Company Type
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button
                        onClick={() => setCompanyType('PBC')}
                        className={`p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2 ${companyType === 'PBC'
                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                            : 'border-gray-200 dark:border-gray-800 hover:border-emerald-300'
                            }`}
                    >
                        <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${companyType === 'PBC' ? 'border-emerald-600' : 'border-gray-400'}`}>
                            {companyType === 'PBC' && <div className="w-3 h-3 bg-emerald-600 rounded-full" />}
                        </div>
                        <span className="font-medium text-gray-900 dark:text-white">PBC</span>
                        <span className="text-sm text-gray-500 text-center">Private Business Corporation<br />Suited for small enterprises</span>
                    </button>

                    <button
                        onClick={() => setCompanyType('PVT LTD')}
                        className={`p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2 ${companyType === 'PVT LTD'
                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                            : 'border-gray-200 dark:border-gray-800 hover:border-emerald-300'
                            }`}
                    >
                        <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${companyType === 'PVT LTD' ? 'border-emerald-600' : 'border-gray-400'}`}>
                            {companyType === 'PVT LTD' && <div className="w-3 h-3 bg-emerald-600 rounded-full" />}
                        </div>
                        <span className="font-medium text-gray-900 dark:text-white">PVT LTD</span>
                        <span className="text-sm text-gray-500 text-center">Private Limited<br />Suited for medium to large enterprises</span>
                    </button>
                </div>
            </div>

            {/* 2. Directors */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <User className="w-5 h-5 text-emerald-600" />
                    2. Directors (Up to 2)
                </h3>
                {directors.map((director, index) => (
                    <div key={index} className="bg-gray-50 dark:bg-zinc-900 p-4 rounded-xl relative group">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Director Name</label>
                                <input
                                    type="text"
                                    value={director.name}
                                    onChange={(e) => handleDirectorChange(index, 'name', e.target.value)}
                                    className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-black focus:ring-2 focus:ring-emerald-500 outline-none transition-all"
                                    placeholder="Full Name"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Director ID</label>
                                <input
                                    type="text"
                                    value={director.id}
                                    onChange={(e) => handleDirectorChange(index, 'id', e.target.value)}
                                    className={`w-full px-4 py-2 rounded-lg border ${idErrors[index] ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'} bg-white dark:bg-black focus:ring-2 focus:ring-emerald-500 outline-none transition-all`}
                                    placeholder="XX-XXXXXXX-X-XX"
                                />
                                {idErrors[index] && (
                                    <p className="text-xs text-red-500 mt-1">{idErrors[index]}</p>
                                )}
                            </div>
                        </div>
                        {directors.length > 1 && (
                            <button
                                onClick={() => handleRemoveDirector(index)}
                                className="absolute -top-2 -right-2 bg-red-100 dark:bg-red-900/30 text-red-600 p-1.5 rounded-full hover:bg-red-200 transition-colors"
                            >
                                <Trash2 className="w-4 h-4" />
                            </button>
                        )}
                    </div>
                ))}
                {directors.length < 2 && (
                    <button
                        onClick={handleAddDirector}
                        className="flex items-center gap-2 text-sm text-emerald-600 font-medium hover:text-emerald-500"
                    >
                        <Plus className="w-4 h-4" /> Add Another Director
                    </button>
                )}
            </div>

            {/* 3. Preferred Names */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <FileText className="w-5 h-5 text-emerald-600" />
                    3. Preferred Organization Names (1-10)
                </h3>
                <div className="grid grid-cols-1 gap-2">
                    {preferredNames.map((name, index) => (
                        <div key={index} className="flex gap-2">
                            <span className="mt-2 text-sm text-gray-400 w-6 text-right">{index + 1}.</span>
                            <input
                                type="text"
                                value={name}
                                onChange={(e) => handleNameChange(index, e.target.value)}
                                className="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-black focus:ring-2 focus:ring-emerald-500 outline-none transition-all"
                                placeholder={`Proposed Name ${index + 1}`}
                            />
                        </div>
                    ))}
                    {preferredNames.length < 10 && (
                        <button
                            onClick={handleAddName}
                            className="ml-8 flex items-center gap-2 text-sm text-emerald-600 font-medium hover:text-emerald-500"
                        >
                            <Plus className="w-4 h-4" /> Add Name Option
                        </button>
                    )}
                </div>
            </div>

            {/* 4. Company Address */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <MapPin className="w-5 h-5 text-emerald-600" />
                    4. Company Situation (Address)
                </h3>
                <textarea
                    value={companyAddress}
                    onChange={(e) => setCompanyAddress(e.target.value)}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-black focus:ring-2 focus:ring-emerald-500 outline-none transition-all min-h-[80px]"
                    placeholder="Where will the company be situated?"
                />
            </div>



            {/* Status Info Box */}
            <div className="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
                <h4 className="flex items-center gap-2 font-medium text-blue-900 dark:text-blue-200 mb-2">
                    <Info className="w-4 h-4" />
                    Application Status Stages
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-blue-800 dark:text-blue-300">
                    <div className="flex items-center gap-2"><Check className="w-3 h-3" /> CV4 Response (Success)</div>
                    <div className="flex items-center gap-2"><Check className="w-3 h-3" /> Name Resubmission (If failed)</div>
                    <div className="flex items-center gap-2"><Check className="w-3 h-3" /> Paperwork Review</div>
                    <div className="flex items-center gap-2"><Check className="w-3 h-3" /> Signing</div>
                </div>
            </div>

            {/* Cost Breakdown */}
            <div className="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 p-6 border border-emerald-200 dark:border-emerald-800">
                <h4 className="font-semibold text-emerald-900 dark:text-emerald-200 mb-4">Cost Breakdown</h4>
                <div className="space-y-2">
                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Full Company Registration Fee</span>
                        <span>${REG_FEE.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Name Search</span>
                        <span>${NAME_SEARCH_FEE.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Delivery (Documents @ ${DELIVERY_TRIP_COST} x {DELIVERY_TRIPS} trips)</span>
                        <span>${TOTAL_DELIVERY.toFixed(2)}</span>
                    </div>
                    <div className="border-t border-emerald-200 dark:border-emerald-700 my-2 pt-2 flex justify-between font-bold text-emerald-900 dark:text-emerald-200">
                        <span>Total Estimated Cost</span>
                        <span>${totalCost.toFixed(2)}</span>
                    </div>
                </div>
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-4 pt-6 mb-32">
                <button
                    onClick={onBack}
                    className="px-6 py-2.5 rounded-full border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors"
                >
                    Back
                </button>
                <button
                    onClick={handleNextClick}
                    className="px-6 py-2.5 rounded-full bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-600/20"
                >
                    Continue
                </button>
            </div>
        </div>
    );
};

export default CompanyRegistrationStep;
