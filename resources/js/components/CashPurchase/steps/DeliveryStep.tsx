import { useState } from 'react';
import { ChevronRight, ChevronLeft, Truck, Building2, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { CashPurchaseData } from '../CashPurchaseWizard';

interface DeliveryStepProps {
    data: CashPurchaseData;
    onNext: (delivery: NonNullable<CashPurchaseData['delivery']>) => void;
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
];

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
];

// Determine delivery agent based on product category and name
const determineDeliveryAgent = (category?: string, productName?: string): {
    agent: 'swift' | 'gain_outlet';
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const productNameLower = (productName || '').toLowerCase();
    const combinedText = `${categoryLower} ${productNameLower}`;

    // Check for tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers ONLY - Gain Outlet
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
            agent: 'gain_outlet',
            reason: 'Tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers are delivered through Gain Outlet depots'
        };
    }

    // All other products - Swift (default for everything else)
    return {
        agent: 'swift',
        reason: 'All products are delivered through Swift depot service'
    };
};

export default function DeliveryStep({ data, onNext, onBack }: DeliveryStepProps) {
    const deliveryAgentInfo = determineDeliveryAgent(data.product?.category, data.product?.name);

    const [selectedAgent] = useState<'swift' | 'gain_outlet'>(
        data.delivery?.type || deliveryAgentInfo.agent
    );
    const [selectedCity, setSelectedCity] = useState<string>(data.delivery?.city || '');
    const [selectedDepot, setSelectedDepot] = useState<string>(data.delivery?.depot || '');
    const [errors, setErrors] = useState<Record<string, string>>({});

    const validateAndContinue = () => {
        const newErrors: Record<string, string> = {};

        // Validation
        if (selectedAgent === 'swift' && !selectedCity) {
            newErrors.city = 'Please select a city for Swift delivery';
        }

        if (selectedAgent === 'gain_outlet' && !selectedDepot) {
            newErrors.depot = 'Please select a Gain Outlet depot for collection';
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length === 0) {
            onNext({
                type: selectedAgent,
                city: selectedAgent === 'swift' ? selectedCity : '',
                depot: selectedAgent === 'gain_outlet' ? selectedDepot : '',
            });
        }
    };

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

            {/* Info Banner */}
            <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div className="flex gap-3">
                    <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm text-blue-800 dark:text-blue-300 font-medium">
                            {deliveryAgentInfo.reason}
                        </p>
                        <p className="text-xs text-blue-700 dark:text-blue-400 mt-1">
                            This delivery method has been automatically assigned based on your product selection.
                        </p>
                    </div>
                </div>
            </div>

            {/* Delivery Agent Display */}
            <div>
                <label className="block text-sm font-medium mb-3 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Assigned To: <span className="text-red-600">*</span>
                </label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Swift Option */}
                    <div
                        className={`p-4 border-2 rounded-lg ${
                            selectedAgent === 'swift'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                        }`}
                    >
                        <Truck className={`h-6 w-6 mb-2 ${
                            selectedAgent === 'swift' ? 'text-emerald-600' : 'text-gray-400'
                        }`} />
                        <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Swift</p>
                        <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                            Courier delivery
                        </p>
                    </div>

                    {/* Gain Outlet Option */}
                    <div
                        className={`p-4 border-2 rounded-lg ${
                            selectedAgent === 'gain_outlet'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                        }`}
                    >
                        <Building2 className={`h-6 w-6 mb-2 ${
                            selectedAgent === 'gain_outlet' ? 'text-emerald-600' : 'text-gray-400'
                        }`} />
                        <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Gain Outlet</p>
                        <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                            Depot collection
                        </p>
                    </div>
                </div>
            </div>

            {/* Swift City Selection */}
            {selectedAgent === 'swift' && (
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
                            <option key={city} value={city}>
                                {city}
                            </option>
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

            {/* Gain Outlet Depot Selection */}
            {selectedAgent === 'gain_outlet' && (
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Select Gain Outlet Depot <span className="text-red-600">*</span>
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
                        You will collect your product from the selected Gain Outlet depot.
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