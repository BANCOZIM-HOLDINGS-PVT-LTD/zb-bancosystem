import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Building2, User, Users, Landmark, Shield, ChevronLeft, ChevronRight, Briefcase, Info } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

interface CompanyTypeSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface CompanyTypeOption {
    id: string;
    name: string;
    description: string;
    icon: LucideIcon;
    examples?: string;
}

const companyTypes: CompanyTypeOption[] = [
    {
        id: 'sole_trader',
        name: 'Sole Trader',
        description: 'An individual running a business in their own name',
        icon: User,
        examples: 'e.g. Freelancers, market vendors, individual service providers'
    },
    {
        id: 'partnership',
        name: 'Partnership',
        description: 'A business owned by two or more people sharing profits',
        icon: Users,
        examples: 'e.g. Law firms, accounting firms, joint farming ventures'
    },
    {
        id: 'private_limited',
        name: 'Private Limited Company (Pvt Ltd)',
        description: 'A registered company with limited liability for its shareholders',
        icon: Building2,
        examples: 'e.g. Registered trading companies, manufacturing firms'
    },
    {
        id: 'cooperative',
        name: 'Co-operative',
        description: 'A business owned and run jointly by its members for mutual benefit',
        icon: Landmark,
        examples: 'e.g. Farming cooperatives, savings clubs, credit unions'
    },
    {
        id: 'trust',
        name: 'Trust',
        description: 'A legal arrangement where assets are managed on behalf of beneficiaries',
        icon: Shield,
        examples: 'e.g. Family trusts, community trusts, investment trusts'
    }
];

const CompanyTypeSelection: React.FC<CompanyTypeSelectionProps> = ({ data, onNext, onBack, loading }) => {

    const handleCompanyTypeSelect = (companyType: CompanyTypeOption) => {
        onNext({
            employer: 'sme-business',
            employerName: companyType.name,
            employerCategory: 'SME Business',
            companyType: companyType.id,
            companyTypeName: companyType.name,
            formType: 'sme_business',
        });
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <div className="inline-flex items-center justify-center w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-full mb-4">
                    <Briefcase className="h-7 w-7 text-emerald-600" />
                </div>
                <h2 className="text-2xl font-semibold mb-2">What type of business do you operate?</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select the structure that best describes your business
                </p>
            </div>

            {/* Info banner */}
            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm text-blue-800 dark:text-blue-300">
                            <span className="font-medium">SME Business Booster Application</span> — This pathway is designed for existing businesses seeking to expand operations with our Booster packages.
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {companyTypes.map((type) => {
                    const Icon = type.icon;
                    const isDisabled = loading;
                    return (
                        <button
                            key={type.id}
                            onClick={() => !isDisabled && handleCompanyTypeSelect(type)}
                            disabled={isDisabled}
                            className={`group p-5 text-left rounded-xl border-2 transition-all ${isDisabled
                                ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60 dark:border-gray-700 dark:bg-gray-800/50'
                                : 'border-gray-200 hover:border-emerald-500 hover:bg-emerald-50/50 hover:shadow-lg dark:border-gray-700 dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20'
                                }`}
                        >
                            <div className="flex flex-col h-full">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className={`p-2.5 rounded-lg transition-colors ${isDisabled
                                        ? 'bg-gray-100 dark:bg-gray-800'
                                        : 'bg-emerald-100 dark:bg-emerald-900/30 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/50'
                                    }`}>
                                        <Icon className={`h-5 w-5 ${isDisabled ? 'text-gray-400' : 'text-emerald-600'}`} />
                                    </div>
                                    <ChevronRight className={`h-4 w-4 ml-auto ${isDisabled ? 'text-gray-300' : 'text-gray-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-transform'}`} />
                                </div>
                                <h3 className={`text-base font-semibold mb-1.5 ${isDisabled ? 'text-gray-400' : 'text-gray-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400'}`}>
                                    {type.name}
                                </h3>
                                <p className={`text-sm mb-2 flex-grow ${isDisabled ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'}`}>
                                    {type.description}
                                </p>
                                {type.examples && (
                                    <p className="text-xs text-gray-400 dark:text-gray-500 italic mt-auto">
                                        {type.examples}
                                    </p>
                                )}
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
        </div>
    );
};

export default CompanyTypeSelection;
