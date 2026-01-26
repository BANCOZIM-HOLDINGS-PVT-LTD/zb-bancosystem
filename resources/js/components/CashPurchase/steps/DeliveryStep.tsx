import { useState, useEffect } from 'react';
import { ChevronRight, ChevronLeft, Truck, Building2, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { CashPurchaseData } from '../CashPurchaseWizard';

interface DeliveryStepProps {
    data: CashPurchaseData;
    onNext: (delivery: any) => void;
    onBack: () => void;
}

// Gain Outlet depots
const GAIN_DEPOTS = [
    'BK Boka - Harare Boka', 'CV Chivhu - Chivhu', 'CZ Gain Metro Chitungwiza - Chitungwiza',
    'DA DOMBOSAVA - Domboshava', 'GS Graniteside - Harare', 'HA HATCLIFFE - Harare Hetcliff',
    'KN Makoni - Chitungwiza', 'MU METRO MASASA - Msasa Harare', 'RC RUWA CBD - Ruwa Harare',
    'RW RUWA - Ruwa Harare', 'SX Seke - Chitungwiza', 'UH UBM Warehouse - Harare',
    'AP Aspindale - Aspindale Harare', 'CG Chegutu - Chegutu harare', 'CS Chinhoyi Street - Chinhoyi Street',
    'DZ Murombedzi - Murombedzi', 'GR Graniteside Stockfeeds - Harare', 'HM HARARE MEGA - harare',
    'LX Lytton - Lytton harare', 'ME MBARE - Mbare harare', 'METRO CHEGUTU - Chegutu',
    'MT MUTOKO - Mutoko', 'NT Norton - Norton', 'Wl Willovale - Whilovale Harare',
    'BCC BIRCHENOUGH CBD - BCC BIRCHENOUGH CBD', 'BIRCHENOUGH - BIRCHENOUGH',
    'CC CHIBUWE - Chibuwa Chiredzi', 'CHECHECHE - Checheche', 'CHIPINGE - Chipinge',
    'HV Hauna - Hauna', 'MARONDERA CBD - MARONDERA CBD', 'MARONDERA MAIN - MARONDERA MAIN',
    'MB Murambinda - Murambinda', 'MBC Murambinda CBD - Murambinda', 'NY Nyanga - Nyanga',
    'RX Rusape - Rusape', 'SK Sakubva - Mutare', 'SKM METRO CASH & CARRY Sakubva - Sakubva',
    'UX Mutare - Mutare', 'YE Yeovil - Mutare', 'CHIREDZI MEGA - Chiredzi', 'CVI Chivi - Chivi',
    'GT Gutu - Gutu', 'JERERA - Jerera', 'MA Masvingo Cbd - Masvingo', 'MK Masvingo Bradburn - Masvingo',
    'MM Masvingo Mega - Masvingo', 'MS Mashava - Mashava', 'NS Neshuro - Neshuro',
    'TRIANGLE - Triangle', 'VX Masvingo - Masvingo', 'BX Bindura - Bindura',
    'CF Gain Metro Chinhoyi - Chinhoyi', 'CN Chinhoyi Mega - Chinhoyi', 'GV Guruve - Guruve',
    'KR Karoi - Karoi', 'MV Mvurwi - Mvurwi', 'MZ Muzarabani - Muzarabani', 'NX Chinhoyi - Chinhoyi',
    'SV Shamva - Shamva', 'BB Beitbridge - beitbridge', 'BN Binga - binga', 'CB Byo Cbd - Buluwayo',
    'EX Express - Buluwayo', 'FX Victoria Falls - Victoria Fall', 'GW Gwanda - Gwanda',
    'Gwanda Metro - Gwanda', 'HX Hwange - Hwange', 'Hwange CBD - Hwange', 'KH Khami Metro - Khami',
    'LP Lupane - Lupane', 'PX Plumtree - plumtree', 'GX Gweru - gweru',
    'KB Gokwe Nembudziya - KB Gokwe Nembudziya', 'KD Kadoma - kadoma', 'KM Kadoma Cbd - kadoma',
    'KV Gain Metro Kadoma - kadoma', 'KW Gain Metro Kwekwe - kwekwe', 'KX Kwekwe - kwekwe',
    'MS Mashava - mashava', 'MTA Mataga - MTA Mataga', 'SH Shurugwi - shurugwi',
    'WX Gokwe - gokwe', 'ZX Zvishavane - zvishavane'
].sort();

// Farm & City Depots (Major Cities)
const FARM_AND_CITY_DEPOTS = [
    'Harare',
    'Bulawayo',
    'Chitungwiza',
    'Mutare',
    'Epworth',
    'Gweru',
    'Kwekwe',
    'Kadoma',
    'Masvingo',
    'Chinhoyi',
    'Norton',
    'Marondera',
    'Ruwa',
    'Chegutu',
    'Zvishavane',
    'Bindura',
    'Beitbridge',
    'Redcliff',
    'Victoria Falls',
    'Hwange',
    'Rusape',
    'Chiredzi',
    'Kariba',
    'Karoi',
    'Chipinge',
    'Gokwe',
    'Shurugwi'
].sort();

// PG Building Materials Depots
const PG_DEPOTS = [
    'PGC HARARE - 21 Chinhoyi Street, CBD, Harare',
    'PG TIMBERS - 5 Nottingham Road, Workington, Harare',
    'PGC BULAWAYO - Cnr 23rd Avenue Birmingham Road/Belmont, Bulawayo',
    'CHINHOYI - 662 Gadzema Road, Chinhoyi',
    'KWEKWE - 1 Industrial Road, Kwekwe',
    'MASVINGO - 700 Timber Road, Masvingo',
    'MUTARE - 7 Bvumba Road, Paulington, Mutare'
];

// Grouped Zimpost Offices - Updated 2026 (5 Provinces)
const ZIMPOST_LOCATIONS: Record<string, string[]> = {
    'Harare': [
        'Avondale', 'Borrowdale', 'Causeway', 'Chisipite', 'Chitungwiza',
        'Dzivarasekwa', 'Glen Norah', 'Graniteside', 'Greendale', 'Harare Main',
        'Hatfield', 'Highfield', 'Highlands', 'Kambuzuma', 'Mabelreign',
        'Marlborough', 'Mt. Pleasant', 'Norton', 'Ruwa', 'Seke',
        'Southerton', 'Tafara', 'Waterfalls'
    ],
    'Mashonaland': [
        'Arcturus', 'Banket', 'Bindura', 'Beatrice', 'Bromley', 'Centenary',
        'Chakari', 'Chegutu', 'Chikonohono', 'Chinhoyi', 'Chirundu', 'Chivhu',
        'Concession', 'Darwendale', 'Glendale', 'Goromonzi', 'Guruve', 'Juru',
        'Kadoma', 'Kariba', 'Karoi', 'Macheke', 'Magunje', 'Marondera',
        'Marondera Womens Prison', 'Mazowe', 'Mhangura', 'Mhondoro-Ngezi',
        'Mt Darwin', 'Mubayira', 'Mudzi', 'Murewa', 'Murombedzi', 'Mutawatawa',
        'Mutoko', 'Mutorashanga', 'Muzarabani', 'Mvurwi', 'Nyamhunga',
        'Raffingora', 'Rimuka', 'Rushinga', 'Sadza', 'Sanyati', 'Selous',
        'Shamva', 'Wedza'
    ],
    'Manicaland': [
        'Birchenough', 'Chimanimani', 'Chipinge', 'Checheche', 'Dangamvura',
        'Dorowa', 'Headlands', 'Hauna', 'Murambinda', 'Mutare', 'Mt Selinda',
        'Marange', 'Mutasa', 'Nyanga', 'Nyamaropa', 'Nhedziwa', 'Nyazura',
        'Nyanyadzi', 'Rusape', 'Sakubva', 'Odzi', 'Penhalonga', 'Watsomba'
    ],
    'Midlands': [
        'Chachacha', 'Charandura', 'Chivi', 'Chirumhanzu', 'Gokwe', 'Gutu',
        'Gweru', 'Gweru Station', 'Jerera', 'Kaguvi', 'Lalapanzi', 'Kwekwe',
        'Manoti', 'Makuvatsine', 'Mataga', 'Mashava', 'Masase', 'Masvingo',
        'Mberengwa', 'Mbizo', 'Morgenster', 'Mkoba', 'Mpandawana', 'Mvuma',
        'Mwenezi', 'Nembudziya', 'Ngundu', 'Nyika', 'Renco', 'Rimuka',
        'Rutenga', 'Sanyati', 'Shangani', 'Shurugwi', 'Triangle', 'Zhombe',
        'Zvishavane'
    ],
    'Matebeleland': [
        'Belmont', 'Beitbridge', 'Bulawayo Main', 'Colleen Bawn', 'Cowdray Park',
        'Dete', 'Donnington', 'Entumbane', 'Enqameni', 'Esigodini', 'Famona',
        'Filabusi', 'Gwanda', 'Hillside', 'Hwange', 'Llewellin Barracks',
        'Lupane', 'Luveve', 'Magwegwe', 'Maphisa', 'Matabisa', 'Mbalabala',
        'Mpopoma', 'Morningside', 'Mzilikazi', 'Nkayi', 'Nkulumane', 'Northend',
        'Plumtree', 'Pumula', 'Raylton', 'Shangani', 'Solusi', 'Tsholotsho',
        'Turkmine', 'Victoria Falls', 'West Nich'
    ]
};

// Flatten to keep compatibility if needed, though we use grouped now
const ALL_ZIMPOST_BRANCHES = Object.values(ZIMPOST_LOCATIONS).flat().sort();

// Determine delivery agent based on cart items
const determineDeliveryAgent = (cart: { category: string; name: string }[]): {
    agent: 'Gain Cash & Carry' | 'Zim Post Office' | 'Farm & City' | 'PG Building Materials';
    isEditable: boolean;
    reason: string;
} => {
    // 1. Check for Farm & City products
    const hasFarmCityItem = cart.some(item => {
        const categoryLower = (item.category || '').toLowerCase();
        const productNameLower = (item.name || '').toLowerCase();
        const combinedText = `${categoryLower} ${productNameLower}`;

        return (
            combinedText.includes('agriculture') ||
            combinedText.includes('agri') ||
            combinedText.includes('mechanization') ||
            combinedText.includes('machinery') ||
            combinedText.includes('chicken') ||
            combinedText.includes('poultry') ||
            combinedText.includes('livestock') ||
            combinedText.includes('broiler') ||
            combinedText.includes('layer') ||
            combinedText.includes('fertilizer') ||
            combinedText.includes('seed') ||
            combinedText.includes('tractor')
        );
    });

    if (hasFarmCityItem) {
        return {
            agent: 'Farm & City',
            isEditable: false,
            reason: 'Agricultural inputs, mechanization, machinery, and chickens will be collected at your nearest Farm and City depot.'
        };
    }

    // 2. Check for PG Building Materials
    const hasPGItem = cart.some(item => {
        const categoryLower = (item.category || '').toLowerCase();
        const productNameLower = (item.name || '').toLowerCase();
        const combinedText = `${categoryLower} ${productNameLower}`;

        return (
            combinedText.includes('building') ||
            combinedText.includes('construction') ||
            combinedText.includes('cement') ||
            combinedText.includes('timber') ||
            combinedText.includes('roofing') ||
            combinedText.includes('brick') ||
            combinedText.includes('door') ||
            combinedText.includes('window') ||
            combinedText.includes('plumbing') ||
            combinedText.includes('core house')
        );
    });

    if (hasPGItem) {
        return {
            agent: 'PG Building Materials',
            isEditable: false,
            reason: 'Building Materials will be collected at your nearest PG depot.'
        };
    }

    // 3. Check for Gain Cash & Carry
    const hasGainItem = cart.some(item => {
        const categoryLower = (item.category || '').toLowerCase();
        const productNameLower = (item.name || '').toLowerCase();
        const combinedText = `${categoryLower} ${productNameLower}`;

        return (
            combinedText.includes('tuckshop') ||
            combinedText.includes('groceries') ||
            combinedText.includes('grocery') ||
            combinedText.includes('airtime') ||
            combinedText.includes('candy') ||
            combinedText.includes('back to school') ||
            combinedText.includes('book') ||
            combinedText.includes('stationery') ||
            combinedText.includes('stationary') ||
            combinedText.includes('retailing')
        );
    });

    if (hasGainItem) {
        return {
            agent: 'Gain Cash & Carry',
            isEditable: false,
            reason: 'Tuckshops, groceries, airtime, candy, books, and stationery are delivered through Gain Cash & Carry depots.'
        };
    }

    // 4. Default to Zim Post Office
    return {
        agent: 'Zim Post Office',
        isEditable: false,
        reason: 'Products are delivered through the Zim Post Office.'
    };
};

export default function DeliveryStep({ data, onNext, onBack }: DeliveryStepProps) {
    const cart = data.cart || [];
    const deliveryAgentInfo = determineDeliveryAgent(cart);

    const [selectedAgent, setSelectedAgent] = useState<'Gain Cash & Carry' | 'Zim Post Office' | 'Farm & City' | 'PG Building Materials'>(
        (data.delivery?.type as any) || deliveryAgentInfo.agent
    );
    const [selectedCity, setSelectedCity] = useState<string>(data.delivery?.city || '');
    const [selectedDepot, setSelectedDepot] = useState<string>(data.delivery?.depot || '');
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (!deliveryAgentInfo.isEditable) {
            setSelectedAgent(deliveryAgentInfo.agent);
        }
    }, [cart]);

    const validateAndContinue = () => {
        const newErrors: Record<string, string> = {};

        // Validation
        if ((selectedAgent === 'Gain Cash & Carry' || selectedAgent === 'Farm & City' || selectedAgent === 'PG Building Materials') && !selectedDepot) {
            newErrors.depot = `Please select a ${selectedAgent} depot for collection`;
        }

        if (selectedAgent === 'Zim Post Office') {
            if (!selectedCity) {
                newErrors.city = 'Please select your city or province';
            } else if (!selectedDepot) {
                newErrors.depot = 'Please select a Zim Post Office branch';
            }
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length === 0) {
            onNext({
                type: selectedAgent,
                city: selectedAgent === 'Zim Post Office' ? selectedCity : '',
                depot: selectedDepot, // Unified field
                agent: selectedAgent // Ensure consistent structure
            });
        }
    };

    const isGainDisabled = selectedAgent !== 'Gain Cash & Carry' && !deliveryAgentInfo.isEditable;
    const isPostOfficeDisabled = selectedAgent !== 'Zim Post Office' && !deliveryAgentInfo.isEditable;
    const isFarmCityDisabled = selectedAgent !== 'Farm & City' && !deliveryAgentInfo.isEditable;
    const isPGDisabled = selectedAgent !== 'PG Building Materials' && !deliveryAgentInfo.isEditable;

    return (
        <div className="space-y-6 pb-24 sm:pb-8">
            {/* Header */}
            <div>
                <h2 className="text-xl sm:text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Delivery Depot
                </h2>
                <p className="text-sm sm:text-base text-[#706f6c] dark:text-[#A1A09A]">
                    {selectedAgent === 'Gain Cash & Carry'
                        ? 'Please be advised that all Tuckshop and Grocery deliveries are done via our courier, Gain Cash & Carry. You will collect your product from the Gain Cash & Carry depot nearest to you.'
                        : selectedAgent === 'Farm & City'
                            ? 'Please be advised that all Agricultural, Machinery, and Livestock deliveries are done via our courier, Farm & City. You will collect your product from the Farm & City depot nearest to you.'
                            : selectedAgent === 'PG Building Materials'
                                ? 'Please be advised that all Building Material deliveries are done via our courier, PG Building Materials. You will collect your product from the PG depot nearest to you.'
                                : 'Please be advised that all deliveries are done via our courier, Zimpost Courier Connect to all urban and rural destinations in Zimbabwe. You will collect your product from the Post Office nearest to you.'}
                </p>
            </div>

            {/* Delivery Agent Display */}
            <div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Swift Option */}
                    {/* Gain Cash & Carry Option */}
                    {!isGainDisabled && (
                        <div
                            className={`p-4 border-2 rounded-lg ${selectedAgent === 'Gain Cash & Carry'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                }`}
                        >
                            <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'Gain Cash & Carry' ? 'text-emerald-600' : 'text-gray-400'
                                }`} />
                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Gain Cash & Carry</p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                Depot collection
                            </p>
                        </div>
                    )}

                    {!isFarmCityDisabled && (
                        <div
                            className={`p-4 border-2 rounded-lg ${selectedAgent === 'Farm & City'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                }`}
                        >
                            <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'Farm & City' ? 'text-emerald-600' : 'text-gray-400'
                                }`} />
                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Farm & City</p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                Depot collection
                            </p>
                        </div>
                    )}

                    {!isPGDisabled && (
                        <div
                            className={`p-4 border-2 rounded-lg ${selectedAgent === 'PG Building Materials'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                }`}
                        >
                            <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'PG Building Materials' ? 'text-emerald-600' : 'text-gray-400'
                                }`} />
                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">PG Building Materials</p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                Depot collection
                            </p>
                        </div>
                    )}

                    {/* Post Office Option */}
                    {!isPostOfficeDisabled && (
                        <div
                            className={`p-4 border-2 rounded-lg ${selectedAgent === 'Zim Post Office'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                }`}
                        >
                            <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'Zim Post Office' ? 'text-emerald-600' : 'text-gray-400'
                                }`} />
                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Zim Post Office</p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                Zimpost collection
                            </p>
                        </div>
                    )}
                </div>
            </div>



            {/* New Two-Step Post Office Selection */}
            {selectedAgent === 'Zim Post Office' && (
                <div className="space-y-4">
                    {/* Step 1: City/Province */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            Select City/Province <span className="text-red-600">*</span>
                        </label>
                        <select
                            value={selectedCity}
                            onChange={(e) => {
                                setSelectedCity(e.target.value);
                                setSelectedDepot(''); // Reset branch
                                setErrors({});
                            }}
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.city ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                        >
                            <option value="">Select your city or province</option>
                            {Object.keys(ZIMPOST_LOCATIONS).map((city) => (
                                <option key={city} value={city}>{city}</option>
                            ))}
                        </select>
                        {errors.city && (
                            <p className="mt-1 text-sm text-red-600">{errors.city}</p>
                        )}
                    </div>

                    {/* Step 2: Branch */}
                    {selectedCity && (
                        <div>
                            <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Select Zim Post Office Branch <span className="text-red-600">*</span>
                            </label>
                            <select
                                value={selectedDepot}
                                onChange={(e) => {
                                    setSelectedDepot(e.target.value);
                                    setErrors({});
                                }}
                                className={`
                                    w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                    dark:bg-gray-800 dark:text-white
                                    ${errors.depot ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                                `}
                            >
                                <option value="">Select a branch within {selectedCity}</option>
                                {(ZIMPOST_LOCATIONS[selectedCity] || []).map((branch) => (
                                    <option key={branch} value={branch}>{branch}</option>
                                ))}
                            </select>
                            {errors.depot && (
                                <p className="mt-1 text-sm text-red-600">{errors.depot}</p>
                            )}
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                You will collect at your nearest post office.
                            </p>
                        </div>
                    )}
                </div>
            )}

            {/* Farm & City Depot Selection */}
            {selectedAgent === 'Farm & City' && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Select Farm & City Depot <span className="text-red-600">*</span>
                    </label>
                    <select
                        value={selectedDepot}
                        onChange={(e) => {
                            setSelectedDepot(e.target.value);
                            setErrors({});
                        }}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.depot ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                    >
                        <option value="">Select a depot closest to you</option>
                        {FARM_AND_CITY_DEPOTS.map((depot) => (
                            <option key={depot} value={depot}>
                                {depot}
                            </option>
                        ))}
                    </select>
                    {errors.depot && (
                        <p className="mt-1 text-sm text-red-600">{errors.depot}</p>
                    )}
                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        You will collect your product from the selected Farm & City Centre.
                    </p>
                </div>
            )}

            {/* PG Building Materials Depot Selection */}
            {selectedAgent === 'PG Building Materials' && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Select PG Materials Depot <span className="text-red-600">*</span>
                    </label>
                    <select
                        value={selectedDepot}
                        onChange={(e) => {
                            setSelectedDepot(e.target.value);
                            setErrors({});
                        }}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.depot ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                    >
                        <option value="">Select a depot closest to you</option>
                        {PG_DEPOTS.map((depot) => (
                            <option key={depot} value={depot}>
                                {depot}
                            </option>
                        ))}
                    </select>
                    {errors.depot && (
                        <p className="mt-1 text-sm text-red-600">{errors.depot}</p>
                    )}
                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        You will collect your materials from the selected PG depot.
                    </p>
                </div>
            )}

            {/* Gain Cash & Carry Depot Selection */}
            {selectedAgent === 'Gain Cash & Carry' && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Select Gain Cash & Carry Depot <span className="text-red-600">*</span>
                    </label>
                    <select
                        value={selectedDepot}
                        onChange={(e) => {
                            setSelectedDepot(e.target.value);
                            setErrors({});
                        }}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.depot ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                    >
                        <option value="">Select a depot closest to you</option>
                        {GAIN_DEPOTS.map((depot) => (
                            <option key={depot} value={depot}>
                                {depot}
                            </option>
                        ))}
                    </select>
                    {errors.depot && (
                        <p className="mt-1 text-sm text-red-600">{errors.depot}</p>
                    )}
                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        You will collect your product from the selected Gain Cash & Carry depot.
                    </p>
                </div>
            )}

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 mb-32">
                <Button onClick={onBack} variant="outline" size="lg">
                    <ChevronLeft className="mr-2 h-5 w-5" />
                    Back
                </Button>
                <Button onClick={validateAndContinue} size="lg" className="bg-emerald-600 hover:bg-emerald-700">
                    Continue
                    <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
            </div>
        </div>
    );
}