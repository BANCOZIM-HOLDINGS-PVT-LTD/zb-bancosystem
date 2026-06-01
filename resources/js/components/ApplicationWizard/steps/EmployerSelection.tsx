import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Building, Building2, ChevronLeft, ChevronRight, GraduationCap, Leaf, User, HardHat } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { STATE_UNIVERSITIES, MISSION_SCHOOLS, PRIVATE_SCHOOLS } from '../data/educationalInstitutions';

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
    isSpecial?: boolean;
    description?: string;
}

interface EmployerGroup {
    heading: string;
    options: EmployerOption[];
}

const parastatals = [
    // A
    'Airports Company of Zimbabwe (ACZ)',
    'Agricultural Marketing Authority (AMA)',
    'Agricultural Rural Development Authority (ARDA)',
    // B
    'Broadcasting Authority of Zimbabwe (BAZ)',
    // C
    'Civil Aviation Authority of Zimbabwe (CAAZ)',
    'Competition and Tariff Commission (CTC)',
    'Consumer Council of Zimbabwe (CCZ)',
    // D
    'Deposit Protection Corporation (DPC)',
    // E
    'Empower Bank',
    'Environmental Management Agency (EMA)',
    // F
    'Food and Nutrition Council (FNC)',
    'Forestry Commission',
    // G
    'Grain Marketing Board (GMB)',
    // H
    'Health Professions Authority (HPA)',
    // I
    'Industrial Development Corporation of Zimbabwe (IDCZ)',
    'Infrastructure Development Bank of Zimbabwe (IDBZ)',
    'Insurance and Pensions Commission (IPEC)',
    // L
    'Lotteries and Gaming Board',
    // M
    'Medicines Control Authority of Zimbabwe (MCAZ)',
    'Minerals Marketing Corporation of Zimbabwe (MMCZ)',
    // N
    'National AIDS Council (NAC)',
    'National Arts Council of Zimbabwe (NACZ)',
    'National Railways of Zimbabwe (NRZ)',
    'National Social Security Authority (NSSA)',
    'NetOne',
    // P
    "People's Own Savings Bank (POSB)",
    'Pig Industry Board',
    // R
    'Reserve Bank of Zimbabwe (RBZ)',
    'Road Motor Services (RMS)',
    // S
    'Small and Medium Enterprises Development Corporation (SMEDCO)',
    'Sports and Recreation Commission (SRC)',
    // T
    'TelOne',
    'Tobacco Industry and Marketing Board (TIMB)',
    // Z
    'Zambezi River Authority (ZRA)',
    'Zimbabwe Broadcasting Corporation (ZBC)',
    'Zimbabwe Electricity Supply Authority (ZESA)',
    'Zimbabwe Investment and Development Agency (ZIDA)',
    'Zimbabwe National Road Administration (ZINARA)',
    'Zimbabwe National Water Authority (ZINWA)',
    'Zimbabwe Parks and Wildlife Management Authority (ZimParks)',
    'Zimbabwe Revenue Authority (ZIMRA)',
    'Zimbabwe Tourism Authority (ZTA)',
];

const privatePensionFunds = [
    'Old Mutual Zimbabwe',
    'First Mutual Life',
    'Zimnat Life Insurance',
    'FBC Life Assurance',
    'Fidelity Life Assurance',
    'Equity Life Assurance',
    'Sanlam Zimbabwe',
    'NMB Life Insurance',
    'CABS Pension Fund',
    'Standard Chartered Pension Fund',
    'Stanbic Bank Pension Fund',
    'CBZ Life Assurance',
];

const otherPrivateOptions = [
    { id: 'large-company', name: 'Large Company (more than 100 employees)' },
    { id: 'small-company', name: 'Small Company (less than 100 employees)' },
    { id: 'ngo-nonprofit', name: "NGO / Non Profit Organisation" },
];

const PRIVATE_UNIVERSITIES = [
    'Africa University',
    'Zimbabwe Ezekiel Guti University (ZEGU)',
    'Catholic University of Zimbabwe (CUZ)',
    'Reformed Church University (RCU)',
    'Arrupe Jesuit University (AJU)',
    'Solusi University',
    "Women's University in Africa (WUA)",
];

const VOCATIONAL_TRAINING_CENTRES = [
    'Harare Polytechnic',
    'Bulawayo Polytechnic',
    'Mutare Polytechnic',
    'Kwekwe Polytechnic',
    'Masvingo Polytechnic',
    'Gwanda Polytechnic',
    'Chinhoyi University of Technology',
    'Marondera Agricultural College',
    'Gwebi College of Agriculture',
    'Kushinga-Phikelela Farmers Training Centre',
];

const employerGroups: EmployerGroup[] = [
    {
        heading: 'Government of Zimbabwe',
        options: [
            { id: 'government-ssb',     name: 'Paymaster – SSB',     icon: Building },
            { id: 'government-non-ssb', name: 'Paymaster – Non SSB', icon: Building },
            { id: 'parastatal',         name: 'Parastatal',          icon: Building2, isSpecial: true },
        ],
    },
    {
        heading: 'Private Sectors',
        options: [
            { id: 'mining-company',  name: 'Mining Company',   icon: HardHat },
            { id: 'security-company', name: 'Security Company', icon: Building },
            { id: 'other-private',   name: 'Other',            icon: Building2, isSpecial: true, description: 'Large, Small Company, NGO' },
        ],
    },
    {
        heading: 'Municipalities and District Councils',
        options: [
            { id: 'municipality',         name: 'Urban (Municipality)',  icon: Building2 },
            { id: 'rural-district-council', name: 'Rural District Council', icon: Building2 },
        ],
    },
    {
        heading: 'Educational Institutions',
        options: [
            { id: 'edu-state-university',    name: 'State Universities & Polytechnics', icon: GraduationCap, isSpecial: true },
            { id: 'edu-private-university', name: 'Private Universities and Colleges',              icon: GraduationCap, isSpecial: true },
            { id: 'edu-private-school',     name: 'Private Schools',                  icon: GraduationCap, isSpecial: true },
            { id: 'edu-mission-school',     name: 'Mission Schools',                  icon: GraduationCap, isSpecial: true },
            { id: 'edu-vtc',               name: 'Vocational Training Centres (VTCs)', icon: GraduationCap, isSpecial: true },
        ],
    },
    {
        heading: 'Agricultural Based Companies',
        options: [
            { id: 'agricultural-tobacco', name: 'Commodity Tobacco', icon: Leaf },
            { id: 'agricultural-sugar',   name: 'Commodity Sugar',   icon: Leaf },
            { id: 'agricultural-cotton',  name: 'Commodity Cotton',  icon: Leaf },
        ],
    },
    {
        heading: 'Pensioners',
        options: [
            { id: 'government-pensioner', name: 'Former Government of Zimbabwe',           icon: User },
            { id: 'private-pension',      name: 'Private Pension Fund', icon: User, isSpecial: true, description: 'Old Mutual, First Mutual, Zimnat & others' },
        ],
    },
];

// Flat name lookup used when calling onNext directly
const optionNameById: Record<string, string> = Object.fromEntries(
    employerGroups.flatMap(g => g.options.map(o => [o.id, o.name]))
);

type ModalType = 'parastatal' | 'other-private' | 'educational-institution' | 'private-pension';
type EduSubType = 'state-university' | 'private-university' | 'mission-school' | 'private-school' | 'vtc';

const EDU_SUBTYPE_MAP: Record<string, EduSubType> = {
    'edu-state-university':    'state-university',
    'edu-private-university':  'private-university',
    'edu-mission-school':      'mission-school',
    'edu-private-school':      'private-school',
    'edu-vtc':                 'vtc',
};

const EmployerSelection: React.FC<EmployerSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const [showModal, setShowModal]         = useState(false);
    const [modalType, setModalType]         = useState<ModalType | null>(null);
    const [educationSubType, setEducationSubType] = useState<EduSubType | null>(null);
    const [otherEmployer, setOtherEmployer] = useState('');
    const [showOtherInput, setShowOtherInput] = useState(false);

    const openModal = (type: ModalType, eduSub?: EduSubType) => {
        setModalType(type);
        setEducationSubType(eduSub ?? null);
        setShowOtherInput(false);
        setOtherEmployer('');
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setModalType(null);
        setEducationSubType(null);
        setShowOtherInput(false);
        setOtherEmployer('');
    };

    const handleEmployerSelect = (employerId: string) => {
        if (loading) return;

        if (employerId === 'parastatal') {
            openModal('parastatal');
        } else if (employerId === 'other-private') {
            openModal('other-private');
        } else if (employerId === 'private-pension') {
            openModal('private-pension');
        } else if (employerId in EDU_SUBTYPE_MAP) {
            openModal('educational-institution', EDU_SUBTYPE_MAP[employerId]);
        } else {
            onNext({
                employer: employerId,
                employerName: optionNameById[employerId] ?? employerId,
                employerCategory: optionNameById[employerId] ?? employerId,
            });
        }
    };

    // Called when a specific item is chosen inside a modal
    const handleModalSelect = (specificName: string, specificId?: string) => {
        if (specificName === 'OTHER') {
            setShowOtherInput(true);
            return;
        }

        if (modalType === 'educational-institution') {
            onNext({
                employer: 'educational-institution',
                employerName: specificName,
                specificEmployer: specificName,
                educationSubType,
            });
            closeModal();
            return;
        }

        if (modalType === 'other-private') {
            onNext({
                employer: specificId ?? 'other-private',
                employerName: specificName,
                employerCategory: specificName,
            });
            closeModal();
            return;
        }

        if (modalType === 'private-pension') {
            onNext({
                employer: 'private-pension',
                employerName: specificName,
                specificEmployer: specificName,
            });
            closeModal();
            return;
        }

        // parastatal
        onNext({ employer: 'parastatal', employerName: specificName, specificEmployer: specificName });
        closeModal();
    };

    const handleOtherSubmit = () => {
        if (!otherEmployer.trim()) return;
        onNext({
            employer: modalType ?? 'other',
            employerName: otherEmployer,
            specificEmployer: otherEmployer,
        });
        closeModal();
    };

    const eduInstitutionList = (): string[] => {
        if (educationSubType === 'state-university')   return STATE_UNIVERSITIES;
        if (educationSubType === 'private-university') return PRIVATE_UNIVERSITIES;
        if (educationSubType === 'mission-school')     return MISSION_SCHOOLS;
        if (educationSubType === 'private-school')     return PRIVATE_SCHOOLS;
        if (educationSubType === 'vtc')                return VOCATIONAL_TRAINING_CENTRES;
        return [];
    };

    const eduSubTypeLabel: Record<EduSubType, string> = {
        'state-university':   'State Universities & Polytechnics',
        'private-university': 'Private Universities',
        'mission-school':     'Mission Schools',
        'private-school':     'Private Schools',
        'vtc':                'Vocational Training Centres',
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Who is your employer?</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select your employer type from the options below
                </p>
            </div>

            <div className="space-y-8">
                {employerGroups.map((group) => (
                    <div key={group.heading}>
                        {/* Group heading */}
                        <h3 className="text-xl font-bold text-gray-900 dark:text-white border-b-2 border-gray-200 dark:border-gray-700 pb-2 mb-4">
                            {group.heading}
                        </h3>

                        {/* Option buttons */}
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {group.options.map((option) => {
                                const Icon = option.icon;
                                return (
                                    <button
                                        key={option.id}
                                        onClick={() => handleEmployerSelect(option.id)}
                                        disabled={!!loading}
                                        className="group p-4 text-left rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20 disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        <div className="flex items-start space-x-3">
                                            <Icon className="h-6 w-6 flex-shrink-0 mt-1 text-emerald-600" />
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium leading-tight group-hover:text-emerald-600">
                                                    {option.name}
                                                </p>
                                                {option.description && (
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        {option.description}
                                                    </p>
                                                )}
                                            </div>
                                            <ChevronRight className="h-4 w-4 flex-shrink-0 text-gray-400 group-hover:text-emerald-600" />
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            <div className="flex justify-between pt-4">
                <Button variant="outline" onClick={onBack} disabled={loading} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>

            {/* ── Modal ── */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <Card className="w-full max-w-md p-6 bg-white dark:bg-gray-800 animate-in fade-in zoom-in duration-200">

                        <div className="text-center mb-6">
                            <h3 className="text-xl font-semibold mb-1">
                                {modalType === 'parastatal'             ? 'Select Parastatal' :
                                 modalType === 'other-private'          ? 'Select Company Type' :
                                 modalType === 'private-pension'        ? 'Select Pension Fund' :
                                 modalType === 'educational-institution' && educationSubType
                                     ? eduSubTypeLabel[educationSubType]
                                     : 'Select Institution'}
                            </h3>
                            <p className="text-sm text-gray-500">
                                {showOtherInput ? 'Please specify your employer' : 'Select from the list below'}
                            </p>
                        </div>

                        {/* ── Other-specify input ── */}
                        {showOtherInput ? (
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
                                <Button onClick={handleOtherSubmit} disabled={!otherEmployer.trim()} className="w-full bg-emerald-600 hover:bg-emerald-700">
                                    Confirm
                                </Button>
                                <Button variant="ghost" onClick={() => setShowOtherInput(false)} className="w-full">
                                    Back to List
                                </Button>
                            </div>

                        ) : modalType === 'educational-institution' ? (
                            /* ── Educational institution list ── */
                            <div className="space-y-2 max-h-[55vh] overflow-y-auto">
                                {eduInstitutionList().map((institution) => (
                                    <button
                                        key={institution}
                                        onClick={() => handleModalSelect(institution)}
                                        className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700"
                                    >
                                        {institution}
                                    </button>
                                ))}
                                <button
                                    onClick={() => handleModalSelect('OTHER')}
                                    className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700 font-medium"
                                >
                                    Other (Please Specify)
                                </button>
                            </div>

                        ) : modalType === 'other-private' ? (
                            /* ── Other private company types ── */
                            <div className="space-y-2">
                                {otherPrivateOptions.map((opt) => (
                                    <button
                                        key={opt.id}
                                        onClick={() => handleModalSelect(opt.name, opt.id)}
                                        className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700"
                                    >
                                        {opt.name}
                                    </button>
                                ))}
                                <button
                                    onClick={() => handleModalSelect('OTHER')}
                                    className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700 font-medium"
                                >
                                    Other (Please Specify)
                                </button>
                            </div>

                        ) : modalType === 'private-pension' ? (
                            /* ── Private pension fund list ── */
                            <div className="space-y-2 max-h-[55vh] overflow-y-auto">
                                {privatePensionFunds.map((fund) => (
                                    <button
                                        key={fund}
                                        onClick={() => handleModalSelect(fund)}
                                        className="w-full p-3 text-left rounded-lg hover:bg-emerald-50 hover:text-emerald-700 transition-colors border border-gray-100 dark:border-gray-700"
                                    >
                                        {fund}
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
                            /* ── Parastatal list ── */
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
                        )}

                        {!showOtherInput && (
                            <div className="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <Button variant="outline" onClick={closeModal} className="w-full">
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
