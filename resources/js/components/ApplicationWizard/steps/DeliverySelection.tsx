import React, { useState, useEffect } from 'react';
import { Truck, MapPin, Building2, Info } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface DeliverySelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

// Cities for Swift deliveries
const SWIFT_CITIES = [
    'Beitbridge',
    'Bindura',
    'Bulawayo',
    'Checheche',
    'Chegutu',
    'Chinhoyi',
    'Chiredzi',
    'Chivhu',
    'Chivi',
    'Chipinge',
    'Glendale/Mazowe',
    'Gokwe',
    'Gutu',
    'Gwanda',
    'Gweru',
    'Hwange',
    'Jerera/Nyika',
    'Kadoma',
    'Kariba',
    'Kwekwe',
    'Marondera',
    'Masvingo',
    'Mazowe',
    'Mt Darwin',
    'Murambinda',
    'Murehwa',
    'Mutare',
    'Mutoko',
    'Mvurwi',
    'Ngezi',
    'Norton',
    'Nyanga',
    'Nyika',
    'Plumtree',
    'Rusape',
    'Shurugwi',
    'Triangle',
    'Victoria Falls',
    'Zvishavane'


];

// Gain Outlet depots
const GAIN_DEPOTS = [
    // Harare South - SHAMMILA
    'BK Boka - Harare Boka',
    'CV Chivhu - Chivhu',
    'CZ Gain Metro Chitungwiza - Chitungwiza',
    'DA DOMBOSAVA - Domboshava',
    'GS Graniteside - Harare',
    'HA HATCLIFFE - Harare Hetcliff',
    'KN Makoni - Chitungwiza',
    'MU METRO MASASA - Msasa Harare',
    'RC RUWA CBD - Ruwa Harare',
    'RW RUWA - Ruwa Harare',
    'SX Seke - Chitungwiza',
    'UH UBM Warehouse - Harare',
    // Harare West - MERCY
    'AP Aspindale - Aspindale Harare',
    'CG Chegutu - Chegutu harare',
    'CS Chinhoyi Street - Chinhoyi Street',
    'DZ Murombedzi - Murombedzi',
    'GR Graniteside Stockfeeds - Harare',
    'HM HARARE MEGA - harare',
    'LX Lytton - Lytton harare',
    'ME MBARE - Mbare harare',
    'METRO CHEGUTU - Chegutu',
    'MT MUTOKO - Mutoko',
    'NT Norton - Norton',
    'Wl Willovale - Whilovale Harare',

    // Manicaland - IDAISHE
    'BCC BIRCHENOUGH CBD - BCC BIRCHENOUGH CBD',
    'BIRCHENOUGH - BIRCHENOUGH',
    'CC CHIBUWE - Chibuwa Chiredzi',
    'CHECHECHE - Checheche',
    'CHIPINGE - Chipinge',
    'HV Hauna - Hauna',
    'MARONDERA CBD - MARONDERA CBD',
    'MARONDERA MAIN - MARONDERA MAIN',
    'MB Murambinda - Murambinda',
    'MBC Murambinda CBD - Murambinda',
    'NY Nyanga - Nyanga',
    'RX Rusape - Rusape',
    'SK Sakubva - Mutare',
    'SKM METRO CASH & CARRY Sakubva - Sakubva',
    'UX Mutare - Mutare',
    'YE Yeovil - Mutare',

    // Masvingo - MARGARET
    'CHIREDZI MEGA - Chiredzi',
    'CVI Chivi - Chivi',
    'GT Gutu - Gutu',
    'JERERA - Jerera',
    'MA Masvingo Cbd - Masvingo',
    'MK Masvingo Bradburn - Masvingo',
    'MM Masvingo Mega - Masvingo',
    'MS Mashava - Mashava',
    'NS Neshuro - Neshuro',
    'TRIANGLE - Triangle',
    'VX Masvingo - Masvingo',

    // Mashonaland - CASPER
    'BX Bindura - Bindura',
    'CF Gain Metro Chinhoyi - Chinhoyi',
    'CN Chinhoyi Mega - Chinhoyi',
    'GV Guruve - Guruve',
    'KR Karoi - Karoi',
    'MV Mvurwi - Mvurwi',
    'MZ Muzarabani - Muzarabani',
    'NX Chinhoyi - Chinhoyi',
    'SV Shamva - Shamva',

    // Matebeleland - EMANUEL
    'BB Beitbridge - beitbridge',
    'BN Binga - binga',
    'CB Byo Cbd - Buluwayo',
    'EX Express - Buluwayo',
    'FX Victoria Falls - Victoria Fall',
    'GW Gwanda - Gwanda',
    'Gwanda Metro - Gwanda',
    'HX Hwange - Hwange',
    'Hwange CBD - Hwange',
    'KH Khami Metro - Khami',
    'LP Lupane - Lupane',
    'PX Plumtree - plumtree',

    // Midlands - SHELTER
    'GX Gweru - gweru',
    'KB Gokwe Nembudziya - KB Gokwe Nembudziya',
    'KD Kadoma - kadoma',
    'KM Kadoma Cbd - kadoma',
    'KV Gain Metro Kadoma - kadoma',
    'KW Gain Metro Kwekwe - kwekwe',
    'KX Kwekwe - kwekwe',
    'MS Mashava - mashava',
    'MTA Mataga - MTA Mataga',
    'SH Shurugwi - shurugwi',
    'WX Gokwe - gokwe',
    'ZX Zvishavane - zvishavane'
];

// Determine delivery agent based on product category/subcategory
const determineDeliveryAgent = (category?: string, subcategory?: string, business?: string): {
    agent: 'Swift' | 'Gain Outlet'; // | 'Bancozim'; // Bancozim commented out for now
    isEditable: boolean;
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const subcategoryLower = (subcategory || '').toLowerCase();
    const businessLower = (business || '').toLowerCase();
    const combinedText = `${categoryLower} ${subcategoryLower} ${businessLower}`;

    // Check for tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers ONLY - Gain Outlet
    if (
        combinedText.includes('tuckshop') ||
        combinedText.includes('groceries') ||
        combinedText.includes('grocery') ||
        combinedText.includes('airtime') ||
        combinedText.includes('candy') ||
        combinedText.includes('poultry') ||
        combinedText.includes('chicken') ||
        combinedText.includes('livestock') ||
        combinedText.includes('broiler') ||
        combinedText.includes('layer') ||
        combinedText.includes('back to school') ||
        combinedText.includes('book') ||
        combinedText.includes('stationery') ||
        combinedText.includes('stationary') ||
        combinedText.includes('retailing')
    ) {
        return {
            agent: 'Gain Outlet',
            isEditable: false,
            reason: 'Tuckshops, groceries, airtime, candy, books, stationary, back to school and live broilers are delivered through Gain Outlet depots'
        };
    }

    // All other products - Swift (default for everything else)
    return {
        agent: 'Swift',
        isEditable: false,
        reason: 'All products are delivered through Swift depot service'
    };
};

const DeliverySelection: React.FC<DeliverySelectionProps> = ({ data, onNext, onBack, loading }) => {
    const deliveryAgentInfo = determineDeliveryAgent(data.category, data.subcategory, data.business);

    const [selectedAgent, setSelectedAgent] = useState<'Swift' | 'Gain Outlet'>(
        data.deliverySelection?.agent || deliveryAgentInfo.agent
    );
    const [selectedCity, setSelectedCity] = useState<string>(data.deliverySelection?.city || '');
    const [selectedDepot, setSelectedDepot] = useState<string>(data.deliverySelection?.depot || '');
    // const [hasPhysicalAgent, setHasPhysicalAgent] = useState<boolean>(false); // Bancozim - commented out
    const [error, setError] = useState<string>('');

    // Update selected agent if product changes
    useEffect(() => {
        if (!deliveryAgentInfo.isEditable) {
            setSelectedAgent(deliveryAgentInfo.agent);
        }
    }, [data.category, data.subcategory, data.business]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        // Validation
        if (selectedAgent === 'Swift' && !selectedCity) {
            setError('Please select a Depot for Swift delivery');
            return;
        }

        if (selectedAgent === 'Gain Outlet' && !selectedDepot) {
            setError('Please select a Gain Outlet depot for collection');
            return;
        }

        // Bancozim validation - commented out
        // if (selectedAgent === 'Bancozim' && !hasPhysicalAgent) {
        //     setError('Bancozim delivery is only available when applying with a physical agent');
        //     return;
        // }

        // Pass delivery selection to next step
        onNext({
            ...data,
            deliverySelection: {
                agent: selectedAgent,
                city: selectedAgent === 'Swift' ? selectedCity : undefined,
                depot: selectedAgent === 'Gain Outlet' ? selectedDepot : undefined,
                isAgentEditable: deliveryAgentInfo.isEditable
            }
        });
    };

    const isSwiftDisabled = selectedAgent === 'Gain Outlet' && !deliveryAgentInfo.isEditable;
    const isGainDisabled = selectedAgent === 'Swift' && !deliveryAgentInfo.isEditable;
    // const isBancozimDisabled = !deliveryAgentInfo.isEditable || selectedAgent === 'Gain Outlet'; // Bancozim - commented out

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-3xl mx-auto">
                <Card className="p-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-3 mb-2">
                            <Truck className="h-8 w-8 text-emerald-600" />
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Delivery Depot
                            </h2>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">
                            Please be advised that you will be required to collect your product from the nearest depot. Kindly select your nearest depot.
                        </p>
                    </div>

                    {/* Info Banner */}
                    <div className="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div className="flex gap-3">
                            <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="text-sm text-blue-800 dark:text-blue-300 font-medium">
                                    {deliveryAgentInfo.reason}
                                </p>
                                {!deliveryAgentInfo.isEditable && (
                                    <p className="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                        This delivery method has been automatically assigned based on your product selection.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Delivery Agent Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Assigned To: <span className="text-red-500">*</span>
                            </label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Swift Option */}
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!isSwiftDisabled && deliveryAgentInfo.isEditable) {
                                            setSelectedAgent('Swift');
                                            setSelectedDepot('');
                                        }
                                    }}
                                    disabled={isSwiftDisabled}
                                    className={`p-4 border-2 rounded-lg text-left transition-all ${
                                        selectedAgent === 'Swift'
                                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                            : isSwiftDisabled
                                            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50 cursor-not-allowed'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'
                                    }`}
                                >
                                    <Truck className={`h-6 w-6 mb-2 ${
                                        selectedAgent === 'Swift' ? 'text-emerald-600' : 'text-gray-400'
                                    }`} />
                                    <p className="font-medium text-gray-900 dark:text-white">Swift</p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Courier delivery
                                    </p>
                                </button>

                                {/* Gain Outlet Option */}
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!isGainDisabled && deliveryAgentInfo.isEditable) {
                                            setSelectedAgent('Gain Outlet');
                                            setSelectedCity('');
                                        }
                                    }}
                                    disabled={isGainDisabled}
                                    className={`p-4 border-2 rounded-lg text-left transition-all ${
                                        selectedAgent === 'Gain Outlet'
                                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                            : isGainDisabled
                                            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50 cursor-not-allowed'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'
                                    }`}
                                >
                                    <Building2 className={`h-6 w-6 mb-2 ${
                                        selectedAgent === 'Gain Outlet' ? 'text-emerald-600' : 'text-gray-400'
                                    }`} />
                                    <p className="font-medium text-gray-900 dark:text-white">Gain Outlet</p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Depot collection
                                    </p>
                                </button>

                                {/* Bancozim Option - Commented out */}
                                {/* <button
                                    type="button"
                                    onClick={() => {
                                        if (!isBancozimDisabled && deliveryAgentInfo.isEditable) {
                                            setSelectedAgent('Bancozim');
                                            setSelectedCity('');
                                            setSelectedDepot('');
                                        }
                                    }}
                                    disabled={isBancozimDisabled}
                                    className={`p-4 border-2 rounded-lg text-left transition-all ${
                                        selectedAgent === 'Bancozim'
                                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                            : isBancozimDisabled
                                            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50 cursor-not-allowed'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'
                                    }`}
                                >
                                    <MapPin className={`h-6 w-6 mb-2 ${
                                        selectedAgent === 'Bancozim' ? 'text-emerald-600' : 'text-gray-400'
                                    }`} />
                                    <p className="font-medium text-gray-900 dark:text-white">Bancozim</p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Physical agent
                                    </p>
                                </button> */}
                            </div>
                        </div>

                        {/* Swift City Selection */}
                        {selectedAgent === 'Swift' && (
                            <div>
                                <label htmlFor="city" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Select City <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="city"
                                    value={selectedCity}
                                    onChange={(e) => setSelectedCity(e.target.value)}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">Select the location closest to you</option>
                                    {SWIFT_CITIES.map((city) => (
                                        <option key={city} value={city}>
                                            {city}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    You will collect your product from the Swift Depot in the selected location.
                                </p>
                            </div>
                        )}

                        {/* Gain Outlet Depot Selection */}
                        {selectedAgent === 'Gain Outlet' && (
                            <div>
                                <label htmlFor="depot" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Select Gain Outlet Depot <span className="text-red-500">*</span>
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
                                    You will collect your product from the selected Gain Outlet depot.
                                </p>
                            </div>
                        )}

                        {/* Bancozim Agent Confirmation - Commented out */}
                        {/* {selectedAgent === 'Bancozim' && (
                            <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div className="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="hasPhysicalAgent"
                                        checked={hasPhysicalAgent}
                                        onChange={(e) => setHasPhysicalAgent(e.target.checked)}
                                        className="mt-1 h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="hasPhysicalAgent" className="text-sm text-yellow-800 dark:text-yellow-300">
                                        I confirm that I am applying with a Bancozim physical agent present, and will collect my product from them.
                                    </label>
                                </div>
                            </div>
                        )} */}

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
