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
    'Amby Post Office',
    'Avondale Post Office',
    'Belvedere Post Office',
    'Borrowdale Post Office',
    'Causeway Post Office',
    'Chisipite Post Office',
    'Chitungwiza Post Office',
    'Dzivarasekwa Post Office',
    'Emerald Hill Post Office',
    'Glen Norah Post Office',
    'Glen View Post Office',
    'Graniteside Post Office',
    'Greendale Post Office',
    'Harare Main Post Office (Cnr Inez Terrace)',
    'Hatfield Post Office',
    'Highfield Post Office',
    'Highlands Post Office',
    'Kambuzuma Post Office',
    'Mabelreign Post Office',
    'Mabvuku Post Office',
    'Machipisa Post Office',
    'Marlborough Post Office',
    'Mbare Musika Post Office',
    'Mbare West Post Office',
    'Mt Pleasant Post Office',
    'Mufakose Post Office',
    'Norton Post Office',
    'Ruwa Post Office',
    'Seke Post Office',
    'Southerton Post Office',
    'Tafara Post Office',
    'Waterfalls Post Office',
    'Zimpost Central Sorting Office',

    // Bulawayo & Matabeleland
    'Ascot Post Office',
    'Beitbridge Post Office',
    'Belmont Post Office',
    'Binga Post Office',
    'Bulawayo Main Post Office',
    'Chinotimba Post Office',
    'Dete Post Office',
    'Entumbane Post Office',
    'Esigodini Post Office',
    'Famona Post Office',
    'Figtree Post Office',
    'Filabusi Post Office',
    'Gwanda Post Office',
    'Hillside Post Office',
    'Hwange Post Office',
    'Llewellin Barracks Post Office',
    'Lupane Post Office',
    'Luveve Post Office',
    'Magwegwe Post Office',
    'Maphisa Post Office',
    'Matabisa Post Office',
    'Mbalabala Post Office',
    'Mpopoma Post Office',
    'Morningside Post Office',
    'Mzilikazi Post Office',
    'Nkayi Post Office',
    'Nkulumane Post Office',
    'Northend Post Office',
    'Plumtree Post Office',
    'Pumula Post Office',
    'Raylton Post Office',
    'Shangani Post Office',
    'Solusi Post Office',
    'Tsholotsho Post Office',
    'Turkmine Post Office',
    'Victoria Falls Post Office',
    'West Nicholson Post Office',

    // Manicaland
    'Birchenough Post Office',
    'Checheche Post Office',
    'Chimanimani Post Office',
    'Chipinge Post Office',
    'Dangamvura Post Office',
    'Dorowa Post Office',
    'Hauna Post Office',
    'Headlands Post Office',
    'Marange Post Office',
    'Mt Selinda Post Office',
    'Murambinda Post Office',
    'Mutare Main Post Office',
    'Mutasa Post Office',
    'Nhedziwa Post Office',
    'Nyamaropa Post Office',
    'Nyanga Post Office',
    'Nyanyadzi Post Office',
    'Nyazura Post Office',
    'Odzi Post Office',
    'Penhalonga Post Office',
    'Rusape Post Office',
    'Sakubva Post Office',
    'Watsomba Post Office',

    // Midlands & Masvingo
    'Charandura Post Office',
    'Chatsworth Post Office',
    'Chikato Post Office',
    'Chikombedzi Post Office',
    'Chiredzi Post Office',
    'Chivhu Post Office',
    'Donga Post Office',
    'Gokwe Post Office',
    'Gweru Main Post Office',
    'Jerera Post Office',
    'Kadoma Post Office',
    'Kwekwe Main Post Office',
    'Kwekwe (Mbizo) Post Office',
    'Makuvatsine Post Office',
    'Manoti Post Office',
    'Masase Post Office',
    'Mashava Post Office',
    'Masvingo Main Post Office',
    'Mataga Post Office',
    'Mberengwa Post Office',
    'Mkoba Post Office',
    'Morgenster Post Office',
    'Mpandawana Post Office',
    'Mvuma Post Office',
    'Mwenezi Post Office',
    'Nembudziya Post Office',
    'Ngundu Post Office',
    'Nyika Post Office',
    'Renco Post Office',
    'Rimuka Post Office',
    'Rutenga Post Office',
    'Sanyati Post Office',
    'Shurugwi Post Office',
    'Triangle Post Office',
    'Zhombe Post Office',
    'Zvishavane Post Office',

    // Mashonaland (West, Central, East)
    'Acturus Post Office',
    'Banket Post Office',
    'Beatrice Post Office',
    'Bindura Post Office',
    'Bromley Post Office',
    'Centenary Post Office',
    'Chakari Post Office',
    'Chegutu Post Office',
    'Chikonohono Post Office',
    'Chinhoyi Post Office',
    'Chirundu Post Office',
    'Concession Post Office',
    'Darwendale Post Office',
    'Glendale Post Office',
    'Goromonzi Post Office',
    'Guruve Post Office',
    'Juru Post Office',
    'Kariba Post Office',
    'Karoi Post Office',
    'Macheke Post Office',
    'Magunje Post Office',
    'Marondera Post Office',
    'Mazowe Post Office',
    'Mhangura Post Office',
    'Mhondoro-Ngezi Post Office',
    'Mt Darwin Post Office',
    'Mubayira Post Office',
    'Mudzi Post Office',
    'Murewa Post Office',
    'Murombedzi Post Office',
    'Mutawatawa Post Office',
    'Mutoko Post Office',
    'Mutorashanga Post Office',
    'Muzarabani Post Office',
    'Mvurwi Post Office',
    'Nyamhunga Post Office',
    'Raffingora Post Office',
    'Rushinga Post Office',
    'Sadza Post Office',
    'Selous Post Office',
    'Shamva Post Office',
    'Wedza Post Office',
    'Zengeza Post Office'
].sort();

// Determine delivery agent based on product category and name
const determineDeliveryAgent = (category?: string, productName?: string): {
    agent: 'Gain Cash & Carry' | 'Zim Post Office';
    isEditable: boolean;
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const productNameLower = (productName || '').toLowerCase();
    const combinedText = `${categoryLower} ${productNameLower}`;

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

    // Default to Zim Post Office
    return {
        agent: 'Zim Post Office',
        isEditable: false,
        reason: 'Products are delivered through the Zim Post Office'
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
                city: selectedAgent === 'Zim Post Office' ? selectedCity : '',
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
                        {selectedAgent === 'Zim Post Office' ? 'You will collect at your nearest post office.' : 'You will collect your product from the Gain Cash & Carry depot in the selected location.'}
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