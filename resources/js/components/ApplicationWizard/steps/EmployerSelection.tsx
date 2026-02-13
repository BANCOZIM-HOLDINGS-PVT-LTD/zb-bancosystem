import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Building, Building2, User, MoreHorizontal, ChevronLeft, ChevronRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface EmployerSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface EmployerOption {
    id: string;
    name: string;
    icon: LucideIcon;
    isSpecial: boolean;
    description?: string;
    disabled?: boolean;
}



const parastatals = [
    'ZESA',
    'TELONE',
    'ZIMRA',
    'NSSA',
    'ZINARA',
    'GMB',
    'CAAZ',
    'NRZ',
    'ZUPCO',
    'ZETDC',
    'ZPC'
];

const employerOptions: EmployerOption[] = [
    {
        id: 'government-ssb',
        name: 'Government of Zimbabwe - SSB',
        icon: Building,
        isSpecial: false
    },
    {
        id: 'government-non-ssb',
        name: 'Government of Zimbabwe - Non SSB',
        icon: Building,
        isSpecial: false
    },
{
        id: 'government-pensioner',
        name: 'Government Pensioner',
        icon: Building,
        isSpecial: false
    },

    {
        id: 'security-company',
        name: 'Security Company',
        icon: Building,
        isSpecial: false
    },
    {
        id: 'municipality',
        name: 'Municipality',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'rural-district-council',
        name: 'Rural District Council',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'parastatal',
        name: 'Parastatal',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'state-university',
        name: 'State University',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'mission-school',
        name: 'Mission School',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'private-school',
        name: 'Private School',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'small-company',
        name: 'Small Company (less than 100 employees)',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'large-company',
        name: 'Large Company (more than 100 employees)',
        icon: Building2,
        isSpecial: false
    },
    {
        id: 'ngo-nonprofit',
        name: "N.G.O's and Non Profit Organisation",
        icon: Building2,
        isSpecial: false
    }
];

const EmployerSelection: React.FC<EmployerSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const [showModal, setShowModal] = useState(false);
    const [modalType, setModalType] = useState<'parastatal' | 'corporate' | 'security-company' | null>(null);
    const [otherEmployer, setOtherEmployer] = useState('');
    const [showOtherInput, setShowOtherInput] = useState(false);
    const [selectedEmployer, setSelectedEmployer] = useState<string>('');

    const handleEmployerSelect = (employerId: string) => {
        const employer = employerOptions.find(e => e.id === employerId);

        if (employer?.isSpecial) {
            setModalType(employerId === 'parastatal' ? 'parastatal' : 'corporate');
            setShowOtherInput(false);
            setOtherEmployer('');
            setShowModal(true);
        } else {
            onNext({
                employer: employerId,
                employerName: employer?.name,
                employerCategory: employer?.name // Add this field for validation
            });
        }
    };

    const handleModalSelect = (specificEmployer: string) => {
        if (specificEmployer === 'OTHER') {
            setShowOtherInput(true);
            return;
        }

        onNext({
            employer: modalType,
            employerName: specificEmployer,
            specificEmployer
        });
        setShowModal(false);
    };

    const handleOtherSubmit = () => {
        if (!otherEmployer.trim()) return;

        onNext({
            employer: modalType,
            employerName: otherEmployer,
            specificEmployer: otherEmployer
        });
        setShowModal(false);
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Who is your employer?</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select your employer type from the options below
                </p>
            </div>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {employerOptions.map((employer) => {
                    const Icon = employer.icon;
                    const isDisabled = employer.disabled || loading;
                    return (
                        <button
                            key={employer.id}
                            onClick={() => !isDisabled && handleEmployerSelect(employer.id)}
                            disabled={isDisabled}
                            className={`group p-4 text-left rounded-lg border transition-all ${isDisabled
                                ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60 dark:border-gray-700 dark:bg-gray-800/50'
                                : 'border-[#e3e3e0] hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20'
                                }`}
                        >
                            <div className="flex items-start space-x-3">
                                <Icon className={`h-6 w-6 flex-shrink-0 mt-1 ${isDisabled ? 'text-gray-400' : 'text-emerald-600'}`} />
                                <div className="flex-1 min-w-0">
                                    <h3 className={`text-sm font-medium mb-1 leading-tight ${isDisabled ? 'text-gray-400' : 'group-hover:text-emerald-600'}`}>
                                        {employer.name}
                                    </h3>
                                    {employer.description && (
                                        <p className={`text-xs ${isDisabled ? 'text-gray-400 italic' : 'text-gray-500 dark:text-gray-400'}`}>
                                            {employer.description}
                                        </p>
                                    )}
                                </div>
                                <ChevronRight className={`h-4 w-4 flex-shrink-0 ${isDisabled ? 'text-gray-300' : 'text-gray-400 group-hover:text-emerald-600'}`} />
                            </div>
                        </button>
                    );
                })}
            </div>

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={onBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
            {/* Modal for Special Employers */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <Card className="w-full max-w-md p-6 bg-white dark:bg-gray-800 animate-in fade-in zoom-in duration-200">
                        <div className="text-center mb-6">
                            <h3 className="text-xl font-semibold mb-2">
                                {modalType === 'parastatal' ? 'Select Parastatal' :
                                    'Select Employer'}
                            </h3>
                            <p className="text-sm text-gray-500">
                                {showOtherInput ? 'Please specify your employer' : 'Select from the list below'}
                            </p>
                        </div>

                        {!showOtherInput ? (
                            <div className="space-y-2 max-h-[60vh] overflow-y-auto">
                                {parastatals.map((company) => (
                                    <button
                                        key={company}
                                        onClick={() => handleModalSelect(company)}
                                        className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700"
                                    >
                                        {company}
                                    </button>
                                ))}
                                <button
                                    onClick={() => handleModalSelect('OTHER')}
                                    className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700 font-medium"
                                >
                                    Other (Please Specify)
                                </button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Employer Name</label>
                                    <input
                                        type="text"
                                        value={otherEmployer}
                                        onChange={(e) => setOtherEmployer(e.target.value)}
                                        placeholder="Enter employer name"
                                        className="w-full p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500 outline-none"
                                        autoFocus
                                    />
                                </div>
                                <Button
                                    onClick={handleOtherSubmit}
                                    disabled={!otherEmployer.trim()}
                                    className="w-full bg-emerald-600 hover:bg-emerald-700"
                                >
                                    Confirm
                                </Button>
                                <Button
                                    variant="ghost"
                                    onClick={() => setShowOtherInput(false)}
                                    className="w-full"
                                >
                                    Back to List
                                </Button>
                            </div>
                        )}

                        {!showOtherInput && (
                            <div className="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <Button
                                    variant="outline"
                                    onClick={() => setShowModal(false)}
                                    className="w-full"
                                >
                                    Cancel
                                </Button>
                            </div>
                        )}
                    </Card>
                </div>
            )}
        </div>
    );
};

export default EmployerSelection;
