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

// Zimpost Offices
const ZIMPOST_OFFICES = [
    // Harare
    'Harare Main Post Office (Cnr Inez Terrace)',
    'Avondale Post Office',
    'Belvedere Post Office',
    'Borrowdale Post Office',
    'Causeway Post Office',
    'Chisipite Post Office',
    'Dzivarasekwa Post Office',
    'Emerald Hill Post Office',
    'Glen Norah Post Office',
    'Glen View Post Office',
    'Greendale Post Office',
    'Highlands Post Office',
    'Kambuzuma Post Office',
    'Mabelreign Post Office',
    'Machipisa Post Office',
    'Marlborough Post Office',
    'Mbare Musika Post Office',
    'Mbare West Post Office',
    'Mabvuku Post Office',
    'Mufakose Post Office',
    'Mt Pleasant Post Office',
    'Southerton Post Office',
    'Tafara Post Office',
    'Zimpost Central Sorting Office',

    // Bulawayo
    'Bulawayo Main Post Office',
    'Nkulumane Post Office',
    'Plumtree Post Office',

    // Manicaland
    'Rusape Post Office',
    'Mutare Main Post Office',
    'Chipinge Post Office',
    'Nyanga Post Office',
    'Murambinda Post Office',

    // Midlands
    'Gweru Main Post Office',
    'Kwekwe (Mbizo) Post Office',
    'Zvishavane Post Office',
    'Mvuma Post Office',
    'Gokwe Post Office',

    // Mashonaland
    'Chinhoyi Post Office',
    'Bindura Post Office',
    'Marondera Post Office',
    'Karoi Post Office',
    'Kariba Post Office',
    'Mt Darwin Post Office',

    // Masvingo
    'Masvingo Main Post Office',
    'Chiredzi Post Office',
    'Gutu Post Office',

    // Matabeleland
    'Victoria Falls Post Office',
    'Hwange Post Office',
    'Gwanda Post Office',
    'Beitbridge Post Office'
].sort();

// Determine delivery agent based on product category and name
const determineDeliveryAgent = (category?: string, productName?: string): {
    agent: 'Swift' | 'Gain Cash & Carry' | 'Zim Post Office';
    isEditable: boolean;
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const productNameLower = (productName || '').toLowerCase();
    const combinedText = `${categoryLower} ${productNameLower}`;

    // Check for Phones, Laptops, ICT gadgets - Zim Post Office
    if (
        combinedText.includes('phone') ||
        combinedText.includes('laptop') ||
        combinedText.includes('tablet') ||
        combinedText.includes('gadget') ||
        combinedText.includes('ict') ||
        combinedText.includes('computer') ||
        combinedText.includes('mobile')
    ) {
        return {
            agent: 'Zim Post Office',
            isEditable: false,
            reason: 'Phones, Laptops and ICT gadgets are delivered through the Zim Post Office'
        };
    }

    // Check for tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers ONLY - Gain Cash & Carry
    if (
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
    ) {
        return {
            agent: 'Gain Cash & Carry',
            isEditable: false,
            reason: 'Tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers are delivered through Gain Cash & Carry depots'
        };
    }

    // All other products - Swift (default for everything else)
    return {
        agent: 'Swift',
        isEditable: false,
        reason: 'All products are delivered through Swift depot service'
    };
};

export default function DeliveryStep({ data, onNext, onBack }: DeliveryStepProps) {
    const deliveryAgentInfo = determineDeliveryAgent(data.product?.category, data.product?.name);

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
    }, [data.product?.category, data.product?.name]);

    const validateAndContinue = () => {
        const newErrors: Record<string, string> = {};

        // Validation
        if (selectedAgent === 'Swift' && !selectedCity) {
            newErrors.city = 'Please select a city for Swift delivery';
        }

        if (selectedAgent === 'Gain Cash & Carry' && !selectedDepot) {
            newErrors.depot = 'Please select a Gain Cash & Carry depot for collection';
        }

        if (selectedAgent === 'Zim Post Office' && !selectedCity) { // Reusing city for post office selection
            newErrors.city = 'Please select a Zim Post Office branch for collection';
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length === 0) {
            onNext({
                type: selectedAgent,
                city: (selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') ? selectedCity : '',
                depot: selectedAgent === 'Gain Cash & Carry' ? selectedDepot : '',
                agent: selectedAgent // Ensure consistent structure
            });
        }
    };

    const isSwiftDisabled = (selectedAgent === 'Gain Cash & Carry' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isGainDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isPostOfficeDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Gain Cash & Carry') && !deliveryAgentInfo.isEditable;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div>
                <h2 className="text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Delivery Depot
                </h2>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    Please be advised that you will be required to collect your product from the nearest depot. Kindly select your nearest depot.
                </p>
            </div>

            {/* Delivery Agent Display */}
            <div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Swift Option */}
                    {!isSwiftDisabled && (
                        <div
                            className={`p-4 border-2 rounded-lg ${selectedAgent === 'Swift'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                }`}
                        >
                            <Truck className={`h-6 w-6 mb-2 ${selectedAgent === 'Swift' ? 'text-emerald-600' : 'text-gray-400'
                                }`} />
                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Swift</p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                Courier delivery
                            </p>
                        </div>
                    )}

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

            {/* Swift / Post Office City Selection */}
            {(selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        {selectedAgent === 'Swift' ? 'Select City' : 'Select Branch'} <span className="text-red-600">*</span>
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
                        <option value="">{selectedAgent === 'Swift' ? 'Select the location closest to you' : 'Select a branch closest to you'}</option>
                        {selectedAgent === 'Swift'
                            ? SWIFT_CITIES.map((city) => (
                                <option key={city} value={city}>{city}</option>
                            ))
                            : ZIMPOST_OFFICES.map((office) => (
                                <option key={office} value={office}>{office}</option>
                            ))
                        }
                    </select>
                    {errors.city && (
                        <p className="mt-1 text-sm text-red-600">{errors.city}</p>
                    )}
                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        You will collect your product from the {selectedAgent === 'Swift' ? 'Swift Depot' : 'Zim Post Office branch'} in the selected location.
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

            {/* Actions */}
            <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button onClick={onBack} variant="outline" size="lg">
                    <ChevronLeft className="mr-2 h-5 w-5" />
                    Back
                </Button>
                <Button
                    onClick={validateAndContinue}
                    size="lg"
                    className="bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
            </div>
        </div>
    );
}