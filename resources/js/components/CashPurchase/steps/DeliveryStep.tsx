import { useState, useEffect } from 'react';
import { ChevronRight, ChevronLeft, Truck, Building2, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { CashPurchaseData } from '../CashPurchaseWizard';

interface DeliveryStepProps {
    data: CashPurchaseData;
    onNext: (delivery: any) => void;
    onBack: () => void;
}

// Cities for Swift deliveries
const SWIFT_CITIES = [
    'Beitbridge', 'Bindura', 'Bulawayo', 'Checheche', 'Chegutu', 'Chinhoyi', 'Chiredzi',
    'Chivhu', 'Chivi', 'Chipinge', 'Glendale/Mazowe', 'Gokwe', 'Gutu', 'Gwanda', 'Gweru',
    'Hwange', 'Jerera/Nyika', 'Kadoma', 'Kariba', 'Kwekwe', 'Marondera', 'Masvingo',
    'Mazowe', 'Mt Darwin', 'Murambinda', 'Murehwa', 'Mutare', 'Mutoko', 'Mvurwi',
    'Ngezi', 'Norton', 'Nyanga', 'Nyika', 'Plumtree', 'Rusape', 'Shurugwi', 'Triangle',
    'Victoria Falls', 'Zvishavane'
].sort();

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

// Grouped Zimpost Offices
const ZIMPOST_LOCATIONS: Record<string, string[]> = {
    'Harare': [
        'Amby Post Office', 'Avondale Post Office', 'Belvedere Post Office', 'Borrowdale Post Office',
        'Causeway Post Office', 'Chisipite Post Office', 'Chitungwiza Post Office', 'Dzivarasekwa Post Office',
        'Emerald Hill Post Office', 'Glen Norah Post Office', 'Glen View Post Office', 'Graniteside Post Office',
        'Greendale Post Office', 'Harare Main Post Office (Cnr Inez Terrace)', 'Hatfield Post Office',
        'Highfield Post Office', 'Highlands Post Office', 'Kambuzuma Post Office', 'Mabelreign Post Office',
        'Mabvuku Post Office', 'Machipisa Post Office', 'Marlborough Post Office', 'Mbare Musika Post Office',
        'Mbare West Post Office', 'Mt Pleasant Post Office', 'Mufakose Post Office', 'Norton Post Office',
        'Ruwa Post Office', 'Seke Post Office', 'Southerton Post Office', 'Tafara Post Office',
        'Waterfalls Post Office', 'Zimpost Central Sorting Office', 'Acturus Post Office', 'Beatrice Post Office',
        'Bromley Post Office', 'Goromonzi Post Office', 'Juru Post Office', 'Zengeza Post Office'
    ],
    'Bulawayo': [
        'Ascot Post Office', 'Belmont Post Office', 'Bulawayo Main Post Office', 'Entumbane Post Office',
        'Famona Post Office', 'Hillside Post Office', 'Llewellin Barracks Post Office', 'Luveve Post Office',
        'Magwegwe Post Office', 'Mpopoma Post Office', 'Morningside Post Office', 'Mzilikazi Post Office',
        'Nkulumane Post Office', 'Northend Post Office', 'Pumula Post Office', 'Raylton Post Office',
        'Tsholotsho Post Office'
    ],
    'Masvingo': [
        'Masvingo Main Post Office', 'Chikato Post Office', 'Morgenster Post Office', 'Mashava Post Office',
        'Jerera Post Office', 'Nyika Post Office', 'Gutu Post Office', 'Chiredzi Post Office', 'Triangle Post Office',
        'Mwenezi Post Office', 'Rutenga Post Office', 'Ngundu Post Office', 'Chikombedzi Post Office',
        'Renco Post Office', 'Chatsworth Post Office', 'Mupandawana Post Office'
    ],
    'Mutare': [
        'Mutare Main Post Office', 'Sakubva Post Office', 'Dangamvura Post Office', 'Chimba Post Office',
        'Penhalonga Post Office', 'Odzi Post Office', 'Watsomba Post Office', 'Nyazura Post Office',
        'Rusape Post Office', 'Headlands Post Office', 'Nyanga Post Office', 'Hauna Post Office',
        'Chipinge Post Office', 'Chimanimani Post Office', 'Checheche Post Office',
        'Birchenough Post Office', 'Murambinda Post Office'
    ],
    'Gweru': [
        'Gweru Main Post Office', 'Mkoba Post Office', 'Ascot Post Office', 'Mvuma Post Office',
        'Shurugwi Post Office', 'Charandura Post Office', 'Lalapanzi Post Office'
    ],
    'Kwekwe': [
        'Kwekwe Main Post Office', 'Kwekwe (Mbizo) Post Office', 'Redcliff Post Office',
        'Zhombe Post Office', 'Gokwe Post Office', 'Nembudziya Post Office', 'Manoti Post Office'
    ],
    'Kadoma': [
        'Kadoma Post Office', 'Rimuka Post Office', 'Chegutu Post Office', 'Chakari Post Office',
        'Sanyati Post Office', 'Mubayira Post Office', 'Mhondoro-Ngezi Post Office'
    ],
    'Victoria Falls': [
        'Victoria Falls Post Office', 'Chinotimba Post Office', 'Hwange Post Office', 'Dete Post Office',
        'Binga Post Office', 'Lupane Post Office'
    ]
};

// Flatten to keep compatibility if needed, though we use grouped now
const ALL_ZIMPOST_BRANCHES = Object.values(ZIMPOST_LOCATIONS).flat().sort();

// Determine delivery agent based on cart items
const determineDeliveryAgent = (cart: { category: string; name: string }[]): {
    agent: 'Gain Cash & Carry' | 'Zim Post Office';
    isEditable: boolean;
    reason: string;
} => {
    // Check if any item in the cart requires Gain Cash & Carry
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
            combinedText.includes('chicken') ||
            combinedText.includes('poultry') ||
            combinedText.includes('broiler') ||
            combinedText.includes('livestock') ||
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
            reason: 'Tuckshops, groceries, airtime, and livestock products are delivered through Gain Cash & Carry depots'
        };
    }

    // Default to Zim Post Office
    return {
        agent: 'Zim Post Office',
        isEditable: false,
        reason: 'Products are delivered through the Zim Post Office'
    };
};

export default function DeliveryStep({ data, onNext, onBack }: DeliveryStepProps) {
    const cart = data.cart || [];
    const deliveryAgentInfo = determineDeliveryAgent(cart);

    const [selectedAgent, setSelectedAgent] = useState<'Swift' | 'Gain Cash & Carry' | 'Zim Post Office'>(
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
        if (selectedAgent === 'Gain Cash & Carry' && !selectedDepot) {
            newErrors.depot = 'Please select a Gain Cash & Carry depot for collection';
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

    const isSwiftDisabled = (selectedAgent === 'Gain Cash & Carry' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isGainDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isPostOfficeDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Gain Cash & Carry') && !deliveryAgentInfo.isEditable;

    return (
        <div className="space-y-6 pb-24 sm:pb-8">
            {/* Header */}
            <div>
                <h2 className="text-xl sm:text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Delivery Depot
                </h2>
                <p className="text-sm sm:text-base text-[#706f6c] dark:text-[#A1A09A]">
                    Delivery via Zimpost Courier Connect to all Zimbabwe destinations. Collect from your nearest Post Office.
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

            {/* Swift Selection */}
            {selectedAgent === 'Swift' && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Select City <span className="text-red-600">*</span>
                    </label>
                    <select
                        value={selectedCity}
                        onChange={(e) => {
                            setSelectedCity(e.target.value);
                            setErrors({});
                        }}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.city ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                    >
                        <option value="">Select the location closest to you</option>
                        {SWIFT_CITIES.map((city) => (
                            <option key={city} value={city}>{city}</option>
                        ))}
                    </select>
                    {errors.city && (
                        <p className="mt-1 text-sm text-red-600">{errors.city}</p>
                    )}
                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        You will collect your product from the Swift Depot in the selected location.
                    </p>
                </div>
            )}

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