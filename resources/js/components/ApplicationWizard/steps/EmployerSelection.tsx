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
}

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
        id: 'municipality-rdc', 
        name: 'Municipality and Rural District Council', 
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
    const [modalType, setModalType] = useState<'parastatal' | 'corporate' | null>(null);
    const [selectedEmployer, setSelectedEmployer] = useState<string>('');
    
    const handleEmployerSelect = (employerId: string) => {
        const employer = employerOptions.find(e => e.id === employerId);
        
        if (employer?.isSpecial) {
            setModalType(employerId === 'parastatal' ? 'parastatal' : 'corporate');
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
        onNext({ 
            employer: modalType,
            employerName: specificEmployer,
            specificEmployer
        });
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
                    return (
                        <button
                            key={employer.id}
                            onClick={() => !loading && handleEmployerSelect(employer.id)}
                            className="group p-4 text-left rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                        >
                            <div className="flex items-start space-x-3">
                                <Icon className="h-6 w-6 text-emerald-600 flex-shrink-0 mt-1" />
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm font-medium mb-1 group-hover:text-emerald-600 leading-tight">
                                        {employer.name}
                                    </h3>
                                    {employer.description && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            {employer.description}
                                        </p>
                                    )}
                                </div>
                                <ChevronRight className="h-4 w-4 text-gray-400 flex-shrink-0 group-hover:text-emerald-600" />
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

export default EmployerSelection;
