import React from 'react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, School, Building, Church, Landmark, User, Info } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

interface SchoolTypeSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface SchoolTypeOption {
    id: string;
    name: string;
    description: string;
    icon: LucideIcon;
    examples?: string;
}

const schoolTypes: SchoolTypeOption[] = [
    {
        id: 'government',
        name: 'Government Owned',
        description: 'Schools fully owned and operated by the government / Ministry of Education',
        icon: Landmark,
        examples: 'e.g. Government primary & secondary schools'
    },
    {
        id: 'private',
        name: 'Private',
        description: 'Privately owned and operated schools not affiliated with a church or government',
        icon: Building,
        examples: 'e.g. Independent private schools, academies'
    },
    {
        id: 'mission',
        name: 'Mission / Church',
        description: 'Schools owned and operated by religious organisations or missions',
        icon: Church,
        examples: 'e.g. Catholic, Anglican, Seventh-Day Adventist schools'
    },
    {
        id: 'parastatal',
        name: 'Parastatal',
        description: 'Schools run by government-owned corporations or state enterprises',
        icon: School,
        examples: 'e.g. ZETDC, ZESA, ZBC staff schools'
    },
    {
        id: 'individual',
        name: 'Individual / Proprietor',
        description: 'Schools registered and run by an individual proprietor',
        icon: User,
        examples: 'e.g. Owner-operated nursery, ECD or primary schools'
    },
];

const SchoolTypeSelection: React.FC<SchoolTypeSelectionProps> = ({ data, onNext, onBack, loading }) => {

    const handleSelect = (type: SchoolTypeOption) => {
        onNext({
            employer:       'school-booster',
            employerName:   type.name,
            employerCategory: 'School Booster',
            schoolType:     type.id,
            schoolTypeName: type.name,
            formType:       'school_booster',
        });
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <div className="inline-flex items-center justify-center w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-full mb-4">
                    <School className="h-7 w-7 text-emerald-600" />
                </div>
                <h2 className="text-2xl font-semibold mb-2">What type of school is it?</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select the category that best describes your school
                </p>
            </div>

            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <p className="text-sm text-blue-800 dark:text-blue-300">
                        <span className="font-medium">School Booster Application</span> — Designed for schools seeking equipment and resources on credit to improve learning facilities.
                    </p>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {schoolTypes.map((type) => {
                    const Icon = type.icon;
                    return (
                        <button
                            key={type.id}
                            onClick={() => !loading && handleSelect(type)}
                            disabled={loading}
                            className={`group p-5 text-left rounded-xl border-2 transition-all ${
                                loading
                                    ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60 dark:border-gray-700 dark:bg-gray-800/50'
                                    : 'border-gray-200 hover:border-emerald-500 hover:bg-emerald-50/50 hover:shadow-lg dark:border-gray-700 dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20'
                            }`}
                        >
                            <div className="flex flex-col h-full">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className={`p-2.5 rounded-lg transition-colors ${
                                        loading ? 'bg-gray-100 dark:bg-gray-800' : 'bg-emerald-100 dark:bg-emerald-900/30 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/50'
                                    }`}>
                                        <Icon className={`h-5 w-5 ${loading ? 'text-gray-400' : 'text-emerald-600'}`} />
                                    </div>
                                    <ChevronRight className={`h-4 w-4 ml-auto ${loading ? 'text-gray-300' : 'text-gray-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-transform'}`} />
                                </div>
                                <h3 className={`text-base font-semibold mb-1.5 ${loading ? 'text-gray-400' : 'text-gray-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400'}`}>
                                    {type.name}
                                </h3>
                                <p className={`text-sm mb-2 flex-grow ${loading ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'}`}>
                                    {type.description}
                                </p>
                                {type.examples && (
                                    <p className="text-xs text-gray-400 dark:text-gray-500 italic mt-auto">{type.examples}</p>
                                )}
                            </div>
                        </button>
                    );
                })}
            </div>

            <div className="flex justify-between pt-4">
                <Button variant="outline" onClick={onBack} disabled={loading} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
        </div>
    );
};

export default SchoolTypeSelection;
