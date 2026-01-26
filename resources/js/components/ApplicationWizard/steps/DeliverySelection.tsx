import React, { useState, useEffect } from 'react';
import { Truck, MapPin, Building2, Info, Calendar } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

interface DeliverySelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
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
    'Harare City & Chitungwiza': [
        'Avondale', 'Borrowdale', 'Causeway', 'Chisipite', 'Chitungwiza',
        'Dzivarasekwa', 'Glen Norah', 'Graniteside', 'Greendale', 'Harare Main',
        'Hatfield', 'Highfield', 'Highlands', 'Kambuzuma', 'Mabelreign',
        'Marlborough', 'Mt. Pleasant', 'Southerton', 'Tafara', 'Waterfalls'
    ],
    'Mashonaland': [
        'Arcturus', 'Banket', 'Bindura', 'Beatrice', 'Bromley', 'Centenary',
        'Chakari', 'Chegutu', 'Chikonohono', 'Chinhoyi', 'Chirundu', 'Chivhu',
        'Concession', 'Darwendale', 'Glendale', 'Goromonzi', 'Guruve', 'Juru',
        'Kadoma', 'Kariba', 'Karoi', 'Macheke', 'Magunje', 'Marondera',
        'Marondera Womens Prison', 'Mazowe', 'Mhangura', 'Mhondoro-Ngezi',
        'Mt Darwin', 'Mubayira', 'Mudzi', 'Murewa', 'Murombedzi', 'Mutawatawa',
        'Mutoko', 'Mutorashanga', 'Muzarabani', 'Mvurwi', 'Nyamhunga', 'Norton',
        'Raffingora', 'Rimuka', 'Rushinga', 'Ruwa', 'Sadza', 'Sanyati', 'Selous', 'Seke',
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
        'Beitbridge', 'Colleen Bawn', 'Cowdray Park',
        'Dete', 'Donnington', 'Enqameni', 'Gwanda', 'Hillside', 'Hwange',
        'Lupane', 'Magwegwe', 'Maphisa', 'Matabisa', 'Mbalabala',
        'Northend',
        'Turkmine', 'Victoria Falls', 'West Nich'
    ],
    'Bulawayo City ': [
        'Ascot', 'Belmont', 'Bulawayo Main', 'Entumbane', 'Esigodini', 'Famona',
        'Filabusi', 'Llewellin Barracks', 'Luveve', 'Mzilikazi', 'Mpopoma', 'Morningside', 'Nkayi', 'Nkulumane',
        'Plumtree', 'Pumula', 'Raylton', 'Shangani', 'Solusi', 'Tsholotsho',
    ]
};

// Flatten to keep compatibility if needed, though we use grouped now
const ALL_ZIMPOST_BRANCHES = Object.values(ZIMPOST_LOCATIONS).flat().sort();

// Determine delivery agent based on product category/subcategory
const determineDeliveryAgent = (category?: string, subcategory?: string, business?: string): {
    agent: 'Gain Cash & Carry' | 'Zim Post Office' | 'Farm & City' | 'PG Building Materials';
    isEditable: boolean;
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const subcategoryLower = (subcategory || '').toLowerCase();
    const businessLower = (business || '').toLowerCase();
    const combinedText = `${categoryLower} ${subcategoryLower} ${businessLower}`;

    // 1. Check for Farm & City products (Agri inputs, mechanization, machinery, chickens)
    if (
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
    ) {
        return {
            agent: 'Farm & City',
            isEditable: false,
            reason: 'All agricultural inputs, mechanization, machinery, and chickens will be collected at your nearest Farm and City depot.'
        };
    }

    // 2. Check for Building Materials -> PG
    if (
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
    ) {
        return {
            agent: 'PG Building Materials',
            isEditable: false,
            reason: 'All Building Materials will be collected at your nearest PG depot.'
        };
    }

    // 3. Check for Tuckshop -> Gain Cash & Carry
    if (
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
    ) {
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

const DeliverySelection: React.FC<DeliverySelectionProps> = ({ data, onNext, onBack, loading }) => {
    const deliveryAgentInfo = determineDeliveryAgent(data.category, data.subcategory, data.business);

    const [selectedAgent, setSelectedAgent] = useState<'Gain Cash & Carry' | 'Zim Post Office' | 'Farm & City' | 'PG Building Materials'>(
        (data.deliverySelection?.agent as any) || deliveryAgentInfo.agent
    );

    // For Zimpost, selectedCity will store the "Province/City" key, and selectedDepot will store the actual branch
    const [selectedCity, setSelectedCity] = useState<string>(data.deliverySelection?.city || '');
    const [selectedDepot, setSelectedDepot] = useState<string>(data.deliverySelection?.depot || '');

    // Booking dates for Zimparks
    const [startDate, setStartDate] = useState<Date | null>(
        data.bookingDetails?.startDate ? new Date(data.bookingDetails.startDate) : null
    );
    const [endDate, setEndDate] = useState<Date | null>(
        data.bookingDetails?.endDate ? new Date(data.bookingDetails.endDate) : null
    );

    const [error, setError] = useState<string>('');

    // Check if this is a Zimparks product
    const isZimparks = data.business === 'Zimparks Vacation Package';

    // Update selected agent if product changes (only for non-Zimparks)
    useEffect(() => {
        if (!isZimparks && !deliveryAgentInfo.isEditable) {
            setSelectedAgent(deliveryAgentInfo.agent);
        }
    }, [data.category, data.subcategory, data.business, isZimparks]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        if (isZimparks) {
            // Validate dates
            if (!startDate) {
                setError('Please select a check-in date');
                return;
            }
            if (!endDate) {
                setError('Please select a check-out date');
                return;
            }
            if (startDate < new Date()) {
                setError('Check-in date cannot be in the past');
                return;
            }
            if (endDate <= startDate) {
                setError('Check-out date must be after check-in date');
                return;
            }

            // Pass booking details
            onNext({
                ...data,
                bookingDetails: {
                    startDate: startDate.toISOString().split('T')[0],
                    endDate: endDate.toISOString().split('T')[0],
                    destination: data.destinationName
                },
                deliverySelection: undefined
            });
            return;
        }

        // Standard Delivery Validation
        if ((selectedAgent === 'Gain Cash & Carry' || selectedAgent === 'Farm & City' || selectedAgent === 'PG Building Materials') && !selectedDepot) {
            setError(`Please select a ${selectedAgent} depot for collection`);
            return;
        }

        if (selectedAgent === 'Zim Post Office') {
            if (!selectedCity) {
                setError('Please select your city or province');
                return;
            }
            if (!selectedDepot) { // We use selectedDepot for the branch now to avoid confusion
                setError('Please select a Zim Post Office branch');
                return;
            }
        }

        // Pass delivery selection to next step
        onNext({
            ...data,
            deliverySelection: {
                agent: selectedAgent,
                city: selectedAgent === 'Zim Post Office' ? selectedCity : undefined,
                depot: selectedDepot, // All agents use this now for the final specific location
                isAgentEditable: deliveryAgentInfo.isEditable
            }
        });
    };

    const isGainDisabled = selectedAgent !== 'Gain Cash & Carry' && !deliveryAgentInfo.isEditable;
    const isPostOfficeDisabled = selectedAgent !== 'Zim Post Office' && !deliveryAgentInfo.isEditable;
    const isFarmCityDisabled = selectedAgent !== 'Farm & City' && !deliveryAgentInfo.isEditable;
    const isPGDisabled = selectedAgent !== 'PG Building Materials' && !deliveryAgentInfo.isEditable;

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-3xl mx-auto">
                <Card className="p-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-3 mb-2">
                            {isZimparks ? (
                                <Calendar className="h-8 w-8 text-emerald-600" />
                            ) : (
                                <Truck className="h-8 w-8 text-emerald-600" />
                            )}
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white">
                                {isZimparks ? 'Booking Dates' : 'Collection Depot'}
                            </h2>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">
                            {isZimparks
                                ? 'Please select your preferred dates for your holiday package.'
                                : selectedAgent === 'Gain Cash & Carry'
                                    ? 'Please be advised that Grocery collections will be done  through the below selected Gain Cash & Carry/Metro.'
                                    : selectedAgent === 'Farm & City'
                                        ? 'Please be advised that for all Agricultural Inputs and Chicken Projects, that all collections will be done through the below selected Farm & City branch.'
                                        : selectedAgent === 'PG Building Materials'
                                            ? 'Please be advised that for all Building Material deliveries are done via our courier, PG Building Materials. You will collect your product from the PG depot nearest to you.'
                                            : 'Please be advised that for all deliveries are done via our courier, Zimpost Courier Connect to all urban and rural destinations in Zimbabwe. You will collect your product from the Post Office nearest to you.'}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">

                        {isZimparks ? (
                            /* Zimparks Date Selection */
                            <div className="space-y-6">
                                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                                    <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                                        Select Your Stay Dates
                                    </h3>
                                    <div className="flex justify-center">
                                        <DatePicker
                                            selected={startDate}
                                            onChange={(dates) => {
                                                const [start, end] = dates as [Date | null, Date | null];
                                                setStartDate(start);
                                                setEndDate(end);
                                            }}
                                            startDate={startDate}
                                            endDate={endDate}
                                            selectsRange
                                            inline
                                            monthsShown={2}
                                            minDate={new Date()}
                                            calendarClassName="border-0"
                                        />
                                    </div>
                                    {startDate && endDate && (
                                        <div className="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                            <p className="text-sm font-medium text-emerald-900 dark:text-emerald-100">
                                                Your Stay: {startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - {endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} ({Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24))} nights)
                                            </p>
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Selected Destination: <span className="font-medium text-emerald-600">{data.destinationName || 'Not selected'}</span>
                                    </p>
                                </div>
                            </div>
                        ) : (
                            /* Standard Delivery Selection */
                            <>
                                {/* Delivery Agent Selection */}
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
                                                <p className="font-medium text-gray-900 dark:text-white">Gain Cash & Carry</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Depot collection
                                                </p>
                                            </div>
                                        )}

                                        {/* Farm & City Option */}
                                        {!isFarmCityDisabled && (
                                            <div
                                                className={`p-4 border-2 rounded-lg ${selectedAgent === 'Farm & City'
                                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                    : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                                    }`}
                                            >
                                                <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'Farm & City' ? 'text-emerald-600' : 'text-gray-400'
                                                    }`} />
                                                <p className="font-medium text-gray-900 dark:text-white">Farm & City</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Depot collection
                                                </p>
                                            </div>
                                        )}

                                        {/* PG Building Materials Option */}
                                        {!isPGDisabled && (
                                            <div
                                                className={`p-4 border-2 rounded-lg ${selectedAgent === 'PG Building Materials'
                                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                    : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                                    }`}
                                            >
                                                <Building2 className={`h-6 w-6 mb-2 ${selectedAgent === 'PG Building Materials' ? 'text-emerald-600' : 'text-gray-400'
                                                    }`} />
                                                <p className="font-medium text-gray-900 dark:text-white">PG Building Materials</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
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
                                                <p className="font-medium text-gray-900 dark:text-white">Zim Post Office</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Zimpost collection
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>



                                {/* Gain Cash & Carry Depot Selection */}
                                {selectedAgent === 'Gain Cash & Carry' && !isGainDisabled && (
                                    <div>
                                        <label htmlFor="depot" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Select Gain Cash & Carry Depot <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="depot"
                                            value={selectedDepot}
                                            onChange={(e) => setSelectedDepot(e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">Select a depot closest to you</option>
                                            {GAIN_DEPOTS.map((depot) => (
                                                <option key={depot} value={depot}>
                                                    {depot}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            You will collect your product from the selected Gain Cash & Carry depot.
                                        </p>
                                    </div>
                                )}

                                {/* Farm & City Depot Selection */}
                                {selectedAgent === 'Farm & City' && !isFarmCityDisabled && (
                                    <div>
                                        <label htmlFor="fc_depot" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Select Farm & City Depot <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="fc_depot"
                                            value={selectedDepot}
                                            onChange={(e) => setSelectedDepot(e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">Select a depot closest to you</option>
                                            {FARM_AND_CITY_DEPOTS.map((depot) => (
                                                <option key={depot} value={depot}>
                                                    {depot}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            You will collect your product from the selected Farm & City Centre.
                                        </p>
                                    </div>
                                )}

                                {/* PG Building Materials Depot Selection */}
                                {selectedAgent === 'PG Building Materials' && !isPGDisabled && (
                                    <div>
                                        <label htmlFor="pg_depot" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Select PG Materials Depot <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="pg_depot"
                                            value={selectedDepot}
                                            onChange={(e) => setSelectedDepot(e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">Select a depot closest to you</option>
                                            {PG_DEPOTS.map((depot) => (
                                                <option key={depot} value={depot}>
                                                    {depot}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            You will collect your materials from the selected PG depot.
                                        </p>
                                    </div>
                                )}

                                {/* Post Office Selection (New Two-Step) */}
                                {selectedAgent === 'Zim Post Office' && !isPostOfficeDisabled && (
                                    <div className="space-y-4">
                                        {/* Step 1: City/Province Selection */}
                                        <div>
                                            <label htmlFor="postOfficeCity" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Select City/Province <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                id="postOfficeCity"
                                                value={selectedCity}
                                                onChange={(e) => {
                                                    setSelectedCity(e.target.value);
                                                    setSelectedDepot(''); // Reset branch on city change
                                                }}
                                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="">Select your city or province</option>
                                                {Object.keys(ZIMPOST_LOCATIONS).map((city) => (
                                                    <option key={city} value={city}>
                                                        {city}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {/* Step 2: Branch Selection */}
                                        {selectedCity && (
                                            <div>
                                                <label htmlFor="postOfficeBranch" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Select Zim Post Office Branch <span className="text-red-500">*</span>
                                                </label>
                                                <select
                                                    id="postOfficeBranch"
                                                    value={selectedDepot}
                                                    onChange={(e) => setSelectedDepot(e.target.value)}
                                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                                >
                                                    <option value="">Select a branch within {selectedCity}</option>
                                                    {(ZIMPOST_LOCATIONS[selectedCity] || []).map((branch) => (
                                                        <option key={branch} value={branch}>
                                                            {branch}
                                                        </option>
                                                    ))}
                                                </select>
                                                <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    You will collect at your nearest post office.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </>
                        )}

                        {/* Error Message */}
                        {error && (
                            <div className="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
                            </div>
                        )}

                        {/* Action Buttons */}
                        <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onBack}
                                disabled={loading}
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                disabled={loading}
                                className="bg-emerald-600 hover:bg-emerald-700"
                            >
                                {loading ? 'Processing...' : 'Continue'}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    );
};

export default DeliverySelection;
