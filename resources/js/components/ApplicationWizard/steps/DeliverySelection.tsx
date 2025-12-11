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
].sort();

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

// Determine delivery agent based on product category/subcategory
const determineDeliveryAgent = (category?: string, subcategory?: string, business?: string): {
    agent: 'Swift' | 'Gain Cash & Carry' | 'Zim Post Office';
    isEditable: boolean;
    reason: string;
} => {
    const categoryLower = (category || '').toLowerCase();
    const subcategoryLower = (subcategory || '').toLowerCase();
    const businessLower = (business || '').toLowerCase();
    const combinedText = `${categoryLower} ${subcategoryLower} ${businessLower}`;

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

const DeliverySelection: React.FC<DeliverySelectionProps> = ({ data, onNext, onBack, loading }) => {
    const deliveryAgentInfo = determineDeliveryAgent(data.category, data.subcategory, data.business);

    const [selectedAgent, setSelectedAgent] = useState<'Swift' | 'Gain Cash & Carry' | 'Zim Post Office'>(
        (data.deliverySelection?.agent as any) || deliveryAgentInfo.agent
    );
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
        if (selectedAgent === 'Swift' && !selectedCity) {
            setError('Please select a city for Swift delivery');
            return;
        }

        if (selectedAgent === 'Gain Cash & Carry' && !selectedDepot) {
            setError('Please select a Gain Cash & Carry depot for collection');
            return;
        }

        if (selectedAgent === 'Zim Post Office' && !selectedCity) { // Reusing selectedCity for Post Office location
            setError('Please select a Zim Post Office branch for collection');
            return;
        }

        // Pass delivery selection to next step
        onNext({
            ...data,
            deliverySelection: {
                agent: selectedAgent,
                city: (selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') ? selectedCity : undefined,
                depot: selectedAgent === 'Gain Cash & Carry' ? selectedDepot : undefined,
                isAgentEditable: deliveryAgentInfo.isEditable
            }
        });
    };

    const isSwiftDisabled = (selectedAgent === 'Gain Cash & Carry' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isGainDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Zim Post Office') && !deliveryAgentInfo.isEditable;
    const isPostOfficeDisabled = (selectedAgent === 'Swift' || selectedAgent === 'Gain Cash & Carry') && !deliveryAgentInfo.isEditable;

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
                                {isZimparks ? 'Booking Dates' : 'Delivery Depot'}
                            </h2>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">
                            {isZimparks
                                ? 'Please select your preferred dates for your holiday package.'
                                : 'Please be advised that you will be required to collect your product from the nearest depot. Kindly select your nearest depot.'}
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
                                        {!isSwiftDisabled && (
                                            <div
                                                className={`p-4 border-2 rounded-lg ${selectedAgent === 'Swift'
                                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                                    : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-50'
                                                    }`}
                                            >
                                                <Truck className={`h-6 w-6 mb-2 ${selectedAgent === 'Swift' ? 'text-emerald-600' : 'text-gray-400'
                                                    }`} />
                                                <p className="font-medium text-gray-900 dark:text-white">Swift</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
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
                                                <p className="font-medium text-gray-900 dark:text-white">Gain Cash & Carry</p>
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

                                {/* Swift City Selection */}
                                {selectedAgent === 'Swift' && !isSwiftDisabled && (
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

                                {/* Post Office Selection */}
                                {selectedAgent === 'Zim Post Office' && !isPostOfficeDisabled && (
                                    <div>
                                        <label htmlFor="postOffice" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Select Zim Post Office Branch <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="postOffice"
                                            value={selectedCity} // Reusing property to store selection
                                            onChange={(e) => setSelectedCity(e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">Select a branch closest to you</option>
                                            {ZIMPOST_OFFICES.map((office) => (
                                                <option key={office} value={office}>
                                                    {office}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            You will collect your product from the selected Zim Post Office branch.
                                        </p>
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
