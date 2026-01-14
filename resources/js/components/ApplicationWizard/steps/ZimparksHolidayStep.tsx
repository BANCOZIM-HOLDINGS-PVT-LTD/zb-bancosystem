import React, { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, MapPin, Hotel, Moon, Calendar, Info } from 'lucide-react';

interface ZimparksHolidayStepProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
}

// Zimparks facilities organized by province
const PROVINCE_FACILITIES = [
    {
        province: 'Harare',
        facilities: [
            { id: 'harare-1', name: 'Lake Chivero Recreational Park' },
            { id: 'harare-2', name: 'Ewarigg Botanical Gardens' },
        ]
    },
    {
        province: 'Mashonaland West',
        facilities: [
            { id: 'mashwest-1', name: 'Darwendale National Park' },
            { id: 'mashwest-2', name: 'Chinhoyi Caves' },
            { id: 'mashwest-3', name: 'Ngezi Recreational Park' },
            { id: 'mashwest-4', name: 'Kariba Recreational Park' },
        ]
    },
    {
        province: 'Mashonaland Central',
        facilities: [
            { id: 'mashcentral-1', name: 'Chewore North' },
        ]
    },
    {
        province: 'Manicaland',
        facilities: [
            { id: 'manicaland-1', name: 'Nyanga Udu Dam' },
            { id: 'manicaland-2', name: 'Nyanga Rhodes Dam' },
            { id: 'manicaland-3', name: 'Chimanimani National Park' },
            { id: 'manicaland-4', name: 'Mtarazi' },
            { id: 'manicaland-5', name: 'Mare Caravan Site' },
            { id: 'manicaland-6', name: 'Mare Dam' },
            { id: 'manicaland-7', name: 'Osborne Dam' },
            { id: 'manicaland-8', name: 'Vumba Recreational' },
        ]
    },
    {
        province: 'Midlands',
        facilities: [
            { id: 'midlands-1', name: 'Sebakwe Recreational Park' },
        ]
    },
    {
        province: 'Masvingo',
        facilities: [
            { id: 'masvingo-1', name: 'Kyle Recreational Park' },
            { id: 'masvingo-2', name: 'Mushandike College' },
            { id: 'masvingo-3', name: 'Tugwi Mukosi' },
        ]
    },
    {
        province: 'Matabeleland North',
        facilities: [
            { id: 'matnorth-1', name: 'Hwange Main Camp' },
            { id: 'matnorth-2', name: 'Hwange Sinamatela' },
            { id: 'matnorth-3', name: 'Hwange Robins Camp' },
            { id: 'matnorth-4', name: 'Kazuma Pan' },
            { id: 'matnorth-5', name: 'Zambezi Camp' },
            { id: 'matnorth-6', name: 'Chizarira National Park' },
        ]
    },
    {
        province: 'Matabeleland South',
        facilities: [
            { id: 'matsouth-1', name: 'Matobo' },
            { id: 'matsouth-2', name: 'Lake Cunningham' },
            { id: 'matsouth-3', name: 'Umzingwane' },
        ]
    },
    {
        province: 'Mashonaland East',
        facilities: [
            { id: 'masheast-1', name: 'Manapools National Park' },
            { id: 'masheast-2', name: 'Nyamaneche' },
        ]
    },
];

// Lodging types with pricing
const LODGING_TYPES = [
    { id: '2bed', name: '2 Bed Room', pricePerNight: 80, description: 'Cozy accommodation for 2 guests' },
    { id: '4bed', name: '4 Bed Room', pricePerNight: 120, description: 'Spacious room for families up to 4 guests' },
    { id: 'executive', name: 'Executive Suite', pricePerNight: 200, description: 'Premium suite with all amenities' },
];

// Number of nights options
const NIGHTS_OPTIONS = [1, 2, 3, 4, 5, 6, 7];

const ZimparksHolidayStep: React.FC<ZimparksHolidayStepProps> = ({ data, onNext, onBack }) => {
    const [selectedProvince, setSelectedProvince] = useState<string>(data.zimparksProvince || '');
    const [selectedFacility, setSelectedFacility] = useState<string>(data.zimparksFacility || '');
    const [lodgingType, setLodgingType] = useState<string>(data.zimparksLodging || '');
    const [numberOfNights, setNumberOfNights] = useState<number>(data.zimparksNights || 0);

    // Date preferences (up to 3 options)
    const [dateOption1Start, setDateOption1Start] = useState<string>(data.dateOption1Start || '');
    const [dateOption1End, setDateOption1End] = useState<string>(data.dateOption1End || '');
    const [dateOption2Start, setDateOption2Start] = useState<string>(data.dateOption2Start || '');
    const [dateOption2End, setDateOption2End] = useState<string>(data.dateOption2End || '');
    const [dateOption3Start, setDateOption3Start] = useState<string>(data.dateOption3Start || '');
    const [dateOption3End, setDateOption3End] = useState<string>(data.dateOption3End || '');

    // Get facilities for selected province
    const provinceFacilities = PROVINCE_FACILITIES.find(p => p.province === selectedProvince)?.facilities || [];

    // Get selected lodging details
    const selectedLodging = LODGING_TYPES.find(l => l.id === lodgingType);

    // Calculate total cost
    const totalCost = selectedLodging && numberOfNights > 0
        ? selectedLodging.pricePerNight * numberOfNights
        : 0;

    // Validation - at least one date range required
    const hasValidDateRange = dateOption1Start && dateOption1End;
    const canProceed = selectedProvince && selectedFacility && lodgingType && numberOfNights > 0 && hasValidDateRange;

    const handleContinue = () => {
        if (!canProceed) {
            return;
        }

        onNext({
            zimparksProvince: selectedProvince,
            zimparksFacility: selectedFacility,
            zimparksLodging: lodgingType,
            zimparksLodgingName: selectedLodging?.name,
            zimparksNights: numberOfNights,
            dateOption1Start,
            dateOption1End,
            dateOption2Start,
            dateOption2End,
            dateOption3Start,
            dateOption3End,
            amount: totalCost,
            grossLoan: totalCost * 1.06,
            netLoan: totalCost,
            loanAmount: totalCost * 1.06,
        });
    };

    // Get min date (today)
    const today = new Date().toISOString().split('T')[0];

    // Calculate max end date based on start date + number of nights
    const calculateMaxEndDate = (startDate: string) => {
        if (!startDate || numberOfNights <= 0) return undefined;
        const start = new Date(startDate);
        start.setDate(start.getDate() + numberOfNights);
        return start.toISOString().split('T')[0];
    };

    // Auto-calculate end date when start date changes
    const handleStartDateChange = (
        startDate: string,
        setStart: (val: string) => void,
        setEnd: (val: string) => void
    ) => {
        setStart(startDate);
        if (startDate && numberOfNights > 0) {
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + numberOfNights);
            setEnd(endDate.toISOString().split('T')[0]);
        }
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Book a Zimparks Holiday
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select your preferred location, accommodation, and dates
                </p>
            </div>

            {/* Step 1: Provincial Location */}
            <Card className="p-6">
                <div className="flex items-center gap-2 mb-4">
                    <MapPin className="w-5 h-5 text-emerald-600" />
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                        1. Select Provincial Location
                    </h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Province *
                        </label>
                        <select
                            value={selectedProvince}
                            onChange={(e) => {
                                setSelectedProvince(e.target.value);
                                setSelectedFacility(''); // Reset facility when province changes
                            }}
                            className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                        >
                            <option value="">Select a province</option>
                            {PROVINCE_FACILITIES.map(p => (
                                <option key={p.province} value={p.province}>{p.province}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Facility *
                        </label>
                        <select
                            value={selectedFacility}
                            onChange={(e) => setSelectedFacility(e.target.value)}
                            disabled={!selectedProvince}
                            className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white disabled:opacity-50"
                        >
                            <option value="">Select a facility</option>
                            {provinceFacilities.map(f => (
                                <option key={f.id} value={f.name}>{f.name}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </Card>

            {/* Step 2: Lodging Type */}
            {selectedFacility && (
                <Card className="p-6 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <div className="flex items-center gap-2 mb-4">
                        <Hotel className="w-5 h-5 text-emerald-600" />
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                            2. Select Lodging Available
                        </h3>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {LODGING_TYPES.map(lodging => (
                            <button
                                key={lodging.id}
                                onClick={() => setLodgingType(lodging.id)}
                                className={`p-4 rounded-xl border-2 text-left transition-all ${lodgingType === lodging.id
                                        ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                    }`}
                            >
                                <div className="flex items-center justify-between mb-2">
                                    <span className="font-medium text-gray-900 dark:text-white">{lodging.name}</span>
                                    <span className="text-emerald-600 font-bold">${lodging.pricePerNight}/night</span>
                                </div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">{lodging.description}</p>
                            </button>
                        ))}
                    </div>
                </Card>
            )}

            {/* Step 3: Number of Nights */}
            {lodgingType && (
                <Card className="p-6 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <div className="flex items-center gap-2 mb-4">
                        <Moon className="w-5 h-5 text-emerald-600" />
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                            3. Number of Nights Required
                        </h3>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        {NIGHTS_OPTIONS.map(nights => (
                            <button
                                key={nights}
                                onClick={() => setNumberOfNights(nights)}
                                className={`w-16 h-16 rounded-xl border-2 font-bold text-lg transition-all ${numberOfNights === nights
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400 text-gray-900 dark:text-white'
                                    }`}
                            >
                                {nights}
                            </button>
                        ))}
                        <button
                            onClick={() => setNumberOfNights(8)}
                            className={`px-6 h-16 rounded-xl border-2 font-bold text-lg transition-all ${numberOfNights >= 8
                                    ? 'border-emerald-600 bg-emerald-600 text-white'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400 text-gray-900 dark:text-white'
                                }`}
                        >
                            7+
                        </button>
                    </div>
                    {numberOfNights >= 8 && (
                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Enter exact number of nights
                            </label>
                            <input
                                type="number"
                                min="8"
                                value={numberOfNights}
                                onChange={(e) => setNumberOfNights(parseInt(e.target.value) || 0)}
                                className="w-32 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                            />
                        </div>
                    )}
                </Card>
            )}

            {/* Step 4: Preferred Dates */}
            {numberOfNights > 0 && (
                <Card className="p-6 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <div className="flex items-center gap-2 mb-4">
                        <Calendar className="w-5 h-5 text-emerald-600" />
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                            4. Preferred Dates (up to 3 options)
                        </h3>
                    </div>
                    <p className="text-sm text-gray-500 mb-4">
                        Select your preferred date ranges. At least one option is required. End date is auto-calculated based on nights.
                    </p>

                    <div className="space-y-4">
                        {/* Option 1 - Required */}
                        <div className="p-4 border rounded-lg border-gray-200 dark:border-gray-700">
                            <div className="font-medium text-gray-900 dark:text-white mb-3">
                                Option 1 (Required) *
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">Commencing</label>
                                    <input
                                        type="date"
                                        min={today}
                                        value={dateOption1Start}
                                        onChange={(e) => handleStartDateChange(e.target.value, setDateOption1Start, setDateOption1End)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">To ({numberOfNights} night{numberOfNights > 1 ? 's' : ''})</label>
                                    <input
                                        type="date"
                                        min={dateOption1Start || today}
                                        max={calculateMaxEndDate(dateOption1Start)}
                                        value={dateOption1End}
                                        onChange={(e) => setDateOption1End(e.target.value)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Option 2 - Optional */}
                        <div className="p-4 border rounded-lg border-gray-200 dark:border-gray-700">
                            <div className="font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Option 2 (Optional)
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">Commencing</label>
                                    <input
                                        type="date"
                                        min={today}
                                        value={dateOption2Start}
                                        onChange={(e) => handleStartDateChange(e.target.value, setDateOption2Start, setDateOption2End)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">To ({numberOfNights} night{numberOfNights > 1 ? 's' : ''})</label>
                                    <input
                                        type="date"
                                        min={dateOption2Start || today}
                                        max={calculateMaxEndDate(dateOption2Start)}
                                        value={dateOption2End}
                                        onChange={(e) => setDateOption2End(e.target.value)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Option 3 - Optional */}
                        <div className="p-4 border rounded-lg border-gray-200 dark:border-gray-700">
                            <div className="font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Option 3 (Optional)
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">Commencing</label>
                                    <input
                                        type="date"
                                        min={today}
                                        value={dateOption3Start}
                                        onChange={(e) => handleStartDateChange(e.target.value, setDateOption3Start, setDateOption3End)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm text-gray-600 dark:text-gray-400 mb-1">To ({numberOfNights} night{numberOfNights > 1 ? 's' : ''})</label>
                                    <input
                                        type="date"
                                        min={dateOption3Start || today}
                                        max={calculateMaxEndDate(dateOption3Start)}
                                        value={dateOption3End}
                                        onChange={(e) => setDateOption3End(e.target.value)}
                                        className="w-full p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            )}

            {/* Cost Summary */}
            {canProceed && (
                <Card className="p-6 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <h4 className="font-semibold text-emerald-900 dark:text-emerald-200 mb-4">Booking Summary</h4>
                    <div className="space-y-2">
                        <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Location</span>
                            <span>{selectedFacility}, {selectedProvince}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Accommodation</span>
                            <span>{selectedLodging?.name}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Duration</span>
                            <span>{numberOfNights} night{numberOfNights > 1 ? 's' : ''}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Rate</span>
                            <span>${selectedLodging?.pricePerNight}/night × {numberOfNights}</span>
                        </div>
                        <div className="border-t border-emerald-200 dark:border-emerald-700 my-2 pt-2 flex justify-between font-bold text-emerald-900 dark:text-emerald-200">
                            <span>Total</span>
                            <span>${totalCost.toFixed(2)}</span>
                        </div>
                    </div>
                </Card>
            )}

            {/* Info Box */}
            <div className="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
                <h4 className="flex items-center gap-2 font-medium text-blue-900 dark:text-blue-200 mb-2">
                    <Info className="w-4 h-4" />
                    Booking Information
                </h4>
                <div className="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                    <p>• All bookings are subject to availability</p>
                    <p>• Multiple date options help us find the best fit for you</p>
                    <p>• Final confirmation will be sent after processing</p>
                </div>
            </div>

            {/* Navigation */}
            <div className="flex justify-between pt-6">
                <Button variant="outline" onClick={onBack} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
                <Button
                    onClick={handleContinue}
                    disabled={!canProceed}
                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
};

export default ZimparksHolidayStep;
