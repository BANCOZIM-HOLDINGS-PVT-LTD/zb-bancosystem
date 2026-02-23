import React, { useState, useEffect } from 'react';
import { Truck, MapPin, Building2, Info, Calendar, User, Users, Phone, Mail, Package } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface DeliverySelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface SupplierInfo {
    id: number;
    name: string;
    contact_person: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    branches: { name: string; address: string; city: string; phone: string }[];
}

// Gain Outlet depots
const GAIN_DEPOTS = [
    'BK Boka - Harare Boka', 'CV Chivhu - Chivhu',
    'DA DOMBOSAVA - Domboshava', 'GS Graniteside - Harare', 'HA HATCLIFFE - Harare Hetcliff',
    'KN Makoni - Chitungwiza', 'RC RUWA CBD - Ruwa Harare',
    'RW RUWA - Ruwa Harare', 'SX Seke - Chitungwiza', 'UH UBM Warehouse - Harare',
    'AP Aspindale - Aspindale Harare', 'CG Chegutu - Chegutu harare', 'CS Chinhoyi Street - Chinhoyi Street',
    'DZ Murombedzi - Murombedzi', 'GR Graniteside Stockfeeds - Harare', 'HM HARARE MEGA - harare',
    'LX Lytton - Lytton harare', 'ME MBARE - Mbare harare',
    'MT MUTOKO - Mutoko', 'NT Norton - Norton', 'Wl Willovale - Whilovale Harare',
    'BCC BIRCHENOUGH CBD - BCC BIRCHENOUGH CBD', 'BIRCHENOUGH - BIRCHENOUGH',
    'CC CHIBUWE - Chibuwa Chiredzi', 'CHECHECHE - Checheche', 'CHIPINGE - Chipinge',
    'HV Hauna - Hauna', 'MARONDERA CBD - MARONDERA CBD', 'MARONDERA MAIN - MARONDERA MAIN',
    'MB Murambinda - Murambinda', 'MBC Murambinda CBD - Murambinda', 'NY Nyanga - Nyanga',
    'RX Rusape - Rusape', 'SK Sakubva - Mutare',
    'UX Mutare - Mutare', 'YE Yeovil - Mutare', 'CHIREDZI MEGA - Chiredzi', 'CVI Chivi - Chivi',
    'GT Gutu - Gutu', 'JERERA - Jerera', 'MA Masvingo Cbd - Masvingo', 'MK Masvingo Bradburn - Masvingo',
    'MM Masvingo Mega - Masvingo', 'MS Mashava - Mashava', 'NS Neshuro - Neshuro',
    'TRIANGLE - Triangle', 'VX Masvingo - Masvingo', 'BX Bindura - Bindura',
    'CN Chinhoyi Mega - Chinhoyi', 'GV Guruve - Guruve',
    'KR Karoi - Karoi', 'MV Mvurwi - Mvurwi', 'MZ Muzarabani - Muzarabani', 'NX Chinhoyi - Chinhoyi',
    'SV Shamva - Shamva', 'BB Beitbridge - beitbridge', 'BN Binga - binga', 'CB Byo Cbd - Buluwayo',
    'EX Express - Buluwayo', 'FX Victoria Falls - Victoria Fall', 'GW Gwanda - Gwanda',
    'HX Hwange - Hwange', 'Hwange CBD - Hwange',
    'LP Lupane - Lupane', 'PX Plumtree - plumtree', 'GX Gweru - gweru',
    'KB Gokwe Nembudziya - KB Gokwe Nembudziya', 'KD Kadoma - kadoma', 'KM Kadoma Cbd - kadoma',
    'KX Kwekwe - kwekwe',
    'MTA Mataga - MTA Mataga', 'SH Shurugwi - shurugwi',
    'WX Gokwe - gokwe'
].sort();

// Metro Peech & Browne Depots
const METRO_DEPOTS = [
    'Bindura', 'Bulawayo', 'Chegutu', 'Chinhoyi', 'Chipinge', 'Chiredzi',
    'Chitungwiza', 'Gokwe', 'Gwanda', 'Gweru', 'Kadoma', 'Khami', 'Kwekwe',
    'Masvingo', 'Msasa', 'Mutare', 'Rusape', 'Sakubva', 'Seke Road', 'Zvishavane'
].sort();

// Farm & City Depots
const FARM_AND_CITY_DEPOTS = [
    'Harare', 'Bulawayo', 'Chitungwiza', 'Mutare', 'Epworth', 'Gweru',
    'Kwekwe', 'Kadoma', 'Masvingo', 'Chinhoyi', 'Norton', 'Marondera',
    'Ruwa', 'Chegutu', 'Zvishavane', 'Bindura', 'Beitbridge', 'Redcliff',
    'Victoria Falls', 'Hwange', 'Rusape', 'Chiredzi', 'Kariba', 'Karoi',
    'Chipinge', 'Gokwe', 'Shurugwi'
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

// Grouped Zimpost Offices
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
        'Northend', 'Turkmine', 'Victoria Falls', 'West Nich'
    ],
    'Bulawayo City ': [
        'Ascot', 'Belmont', 'Bulawayo Main', 'Entumbane', 'Esigodini', 'Famona',
        'Filabusi', 'Llewellin Barracks', 'Luveve', 'Mzilikazi', 'Mpopoma', 'Morningside', 'Nkayi', 'Nkulumane',
        'Plumtree', 'Pumula', 'Raylton', 'Shangani', 'Solusi', 'Tsholotsho',
    ]
};

// Product Delivery Type Determination
type DeliveryType = 'training' | 'driving' | 'zimparks' | 'chicken_lite' | 'chicken_fullhouse' | 'agri' | 'building' | 'tuckshop' | 'zimpost_default';

const determineProductDeliveryType = (data: any): DeliveryType => {
    const category = (data.category || '').toLowerCase();
    const subcategory = (data.subcategory || '').toLowerCase();
    const business = (data.business || '').toLowerCase();
    const scaleName = (data.selectedScale?.name || data.scale || '').toLowerCase();
    const intent = (data.intent || '').toLowerCase();
    const combined = `${category} ${subcategory} ${business}`;

    // Zimparks
    if (business.includes('zimparks') || subcategory.includes('zimparks')) return 'zimparks';

    // Driving School
    if (subcategory.includes('driving') || subcategory.includes('license course')) return 'driving';

    // Training (personalServices but not driving or zimparks)
    if (intent === 'personalservices' && !subcategory.includes('driving') && !business.includes('zimparks')) return 'training';

    // Chicken projects
    if (combined.includes('chicken') || combined.includes('broiler') || combined.includes('layer') || combined.includes('poultry')) {
        if (scaleName.includes('full house')) return 'chicken_fullhouse';
        return 'chicken_lite';
    }

    // Building materials
    if (combined.includes('building') || combined.includes('construction') || combined.includes('cement') ||
        combined.includes('timber') || combined.includes('roofing') || combined.includes('brick') ||
        combined.includes('core house')) return 'building';

    // Agri / farming / greenhouse / specialised
    if (combined.includes('agriculture') || combined.includes('agri') || combined.includes('farming') ||
        combined.includes('machinery') || combined.includes('greenhouse') || combined.includes('green house') ||
        combined.includes('irrigation') || combined.includes('tractor') || combined.includes('fertilizer') ||
        combined.includes('seed') || combined.includes('carport') || combined.includes('mechanization')) return 'agri';

    // Tuckshop / groceries
    if (combined.includes('tuckshop') || combined.includes('grocery') || combined.includes('groceries') ||
        combined.includes('retailing') || combined.includes('retail') || combined.includes('airtime') ||
        combined.includes('candy') || combined.includes('back to school') || combined.includes('stationery')) return 'tuckshop';

    // Default: ZimPost courier (Bancozim and other MicroBiz)
    return 'zimpost_default';
};

const getDeliveryMessage = (deliveryType: DeliveryType, supplierInfo: SupplierInfo | null, data: any): string => {
    switch (deliveryType) {
        case 'training':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will receive training from our partnering Training Academy whose address is listed below.';
        case 'driving':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will receive training from our partnering Driving School whose address is listed below, kindly choose which city/town where you would want to receive the training.';
        case 'zimparks':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will receive accommodation from our partnering Hotel/Resort details are listed below, kindly choose location and dates below.';
        case 'chicken_lite':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will collect your products from the supplier listed below, kindly choose from which location you would want to collect from.';
        case 'chicken_fullhouse':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will collect your products from the supplier listed below, kindly choose from which location you would want to collect from. The Chicken coop will be delivered by a Zimpost Courier Service, delivering countrywide. Please choose the nearest Post Office nearest to you where you will collect from.';
        case 'agri':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will collect your products from the supplier listed below, kindly choose from which location you would want to collect from.';
        case 'building':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will collect your products from the supplier listed below, kindly choose from which location you would want to collect from.';
        case 'tuckshop':
            return 'Please note if the transaction is executed successfully, then you or your beneficiary will collect your products from the supplier listed below, kindly choose from which location you would want to collect from.';
        case 'zimpost_default':
            return 'Please note if the transaction is executed successfully then the product will be delivered by a Zimpost Courier Service, delivering countrywide. Please choose the nearest Post Office where then you or your beneficiary will collect from.';
    }
};

const DeliverySelection: React.FC<DeliverySelectionProps> = ({ data, onNext, onBack, loading }) => {
    const deliveryType = determineProductDeliveryType(data);

    // Beneficiary state
    const [beneficiaryType, setBeneficiaryType] = useState<'self' | 'other'>(
        data.deliverySelection?.beneficiaryType || 'self'
    );
    const [beneficiaryName, setBeneficiaryName] = useState<string>(data.deliverySelection?.beneficiaryName || '');
    const [beneficiaryId, setBeneficiaryId] = useState<string>(data.deliverySelection?.beneficiaryId || '');

    // Collector state
    const [collectorType, setCollectorType] = useState<'self' | 'other'>(
        data.deliverySelection?.collectorType || 'self'
    );
    const [collectorName, setCollectorName] = useState<string>(data.deliverySelection?.collectorName || '');
    const [collectorId, setCollectorId] = useState<string>(data.deliverySelection?.collectorId || '');

    // Delivery location state
    const [selectedAgent, setSelectedAgent] = useState<string>(data.deliverySelection?.agent || '');
    const [selectedCity, setSelectedCity] = useState<string>(data.deliverySelection?.city || '');
    const [selectedDepot, setSelectedDepot] = useState<string>(data.deliverySelection?.depot || '');

    // For full-house chickens: need both supplier branch AND zimpost
    const [zimpostCity, setZimpostCity] = useState<string>(data.deliverySelection?.zimpostCity || '');
    const [zimpostBranch, setZimpostBranch] = useState<string>(data.deliverySelection?.zimpostBranch || '');

    // Tuckshop agent toggle
    const [tuckshopAgent, setTuckshopAgent] = useState<'Gain Cash & Carry' | 'Metro Peech & Browne'>(
        data.deliverySelection?.agent === 'Metro Peech & Browne' ? 'Metro Peech & Browne' : 'Gain Cash & Carry'
    );

    // Supplier info from API
    const [supplierInfo, setSupplierInfo] = useState<SupplierInfo | null>(null);
    const [supplierLoading, setSupplierLoading] = useState(false);

    const [error, setError] = useState<string>('');

    // Fetch supplier info when component mounts
    useEffect(() => {
        const fetchSupplierInfo = async () => {
            // Only fetch for product types that need supplier info from API
            if (!['training', 'chicken_lite', 'chicken_fullhouse', 'agri', 'driving'].includes(deliveryType)) return;

            setSupplierLoading(true);
            try {
                const params = new URLSearchParams();
                if (data.subcategory) params.set('subcategory', data.subcategory);
                if (data.business) params.set('business', data.business);
                if (data.category) params.set('category', data.category);

                const response = await fetch(`/api/products/supplier-info?${params.toString()}`);
                const result = await response.json();

                if (result.success && result.data) {
                    setSupplierInfo(result.data);

                    // If supplier has only 1 branch, auto-select it
                    if (result.data.branches?.length === 1) {
                        setSelectedDepot(result.data.branches[0].name);
                    }
                }
            } catch (err) {
                console.error('Failed to fetch supplier info:', err);
            } finally {
                setSupplierLoading(false);
            }
        };

        fetchSupplierInfo();
    }, [deliveryType, data.subcategory, data.business, data.category]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        // Validate beneficiary
        if (beneficiaryType === 'other') {
            if (!beneficiaryName.trim()) { setError('Please enter the beneficiary\'s full name'); return; }
            if (!beneficiaryId.trim()) { setError('Please enter the beneficiary\'s National ID'); return; }
        }

        // Validate collector
        if (collectorType === 'other') {
            if (!collectorName.trim()) { setError('Please enter the collector\'s full name'); return; }
            if (!collectorId.trim()) { setError('Please enter the collector\'s National ID'); return; }
        }

        // Helper to build common person fields
        const personFields = {
            beneficiaryType,
            beneficiaryName: beneficiaryType === 'other' ? beneficiaryName : '',
            beneficiaryId: beneficiaryType === 'other' ? beneficiaryId : '',
            collectorType,
            collectorName: collectorType === 'other' ? collectorName : '',
            collectorId: collectorType === 'other' ? collectorId : '',
        };

        // ---- Zimparks: Auto-fill, pass through ----
        if (deliveryType === 'zimparks') {
            onNext({
                ...data,
                deliverySelection: {
                    agent: 'Zimparks',
                    depot: data.destinationName || '',
                    ...personFields,
                    supplierName: 'Zimparks',
                },
                bookingDetails: {
                    startDate: data.checkInDate || data.bookingDetails?.startDate || '',
                    endDate: data.checkOutDate || data.bookingDetails?.endDate || '',
                    destination: data.destinationName || data.bookingDetails?.destination || '',
                    nights: data.nights || data.bookingDetails?.nights || '',
                }
            });
            return;
        }

        // ---- Driving: use EasyGo branch ----
        if (deliveryType === 'driving') {
            if (!selectedDepot) { setError('Please select an Easy Go branch'); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: 'Easy Go',
                    depot: selectedDepot,
                    ...personFields,
                    supplierName: 'Easy Go',
                }
            });
            return;
        }

        // ---- Training: supplier branch ----
        if (deliveryType === 'training') {
            if (supplierInfo && supplierInfo.branches?.length > 1 && !selectedDepot) {
                setError('Please select a branch'); return;
            }
            onNext({
                ...data,
                deliverySelection: {
                    agent: supplierInfo?.name || 'Training Academy',
                    depot: selectedDepot || supplierInfo?.address || '',
                    ...personFields,
                    supplierName: supplierInfo?.name || '',
                }
            });
            return;
        }

        // ---- Chicken lite/standard/gold ----
        if (deliveryType === 'chicken_lite') {
            if (!selectedDepot) { setError('Please select a collection branch'); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: supplierInfo?.name || 'Farm & City',
                    depot: selectedDepot,
                    ...personFields,
                    supplierName: supplierInfo?.name || 'Farm & City',
                }
            });
            return;
        }

        // ---- Chicken full house: supplier branch + zimpost ----
        if (deliveryType === 'chicken_fullhouse') {
            if (!selectedDepot) { setError('Please select a collection branch for inputs'); return; }
            if (!zimpostCity) { setError('Please select your city/province for coop delivery'); return; }
            if (!zimpostBranch) { setError('Please select a post office branch for coop delivery'); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: supplierInfo?.name || 'Farm & City',
                    depot: selectedDepot,
                    zimpostCity,
                    zimpostBranch,
                    ...personFields,
                    supplierName: supplierInfo?.name || 'Farm & City',
                }
            });
            return;
        }

        // ---- Agri / greenhouse / specialized ----
        if (deliveryType === 'agri') {
            if (!selectedDepot) { setError('Please select a collection branch'); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: supplierInfo?.name || 'Farm & City',
                    depot: selectedDepot,
                    ...personFields,
                    supplierName: supplierInfo?.name || 'Farm & City',
                }
            });
            return;
        }

        // ---- Building Materials ----
        if (deliveryType === 'building') {
            if (!selectedDepot) { setError('Please select a PG depot'); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: 'PG Building Materials',
                    depot: selectedDepot,
                    ...personFields,
                    supplierName: 'PG Building Materials',
                }
            });
            return;
        }

        // ---- Tuckshop ----
        if (deliveryType === 'tuckshop') {
            if (!selectedDepot) { setError(`Please select a ${tuckshopAgent} depot`); return; }
            onNext({
                ...data,
                deliverySelection: {
                    agent: tuckshopAgent,
                    depot: selectedDepot,
                    ...personFields,
                    supplierName: tuckshopAgent,
                    isAgentEditable: true,
                }
            });
            return;
        }

        // ---- Default: ZimPost ----
        if (!selectedCity) { setError('Please select your city or province'); return; }
        if (!selectedDepot) { setError('Please select a Zim Post Office branch'); return; }
        onNext({
            ...data,
            deliverySelection: {
                agent: 'Zim Post Office',
                city: selectedCity,
                depot: selectedDepot,
                ...personFields,
                supplierName: 'Zim Post Office',
            }
        });
    };

    // Helper: get branch list for supplier-based products
    const getSupplierBranches = (): { name: string; address: string }[] => {
        if (supplierInfo?.branches && supplierInfo.branches.length > 0) {
            return supplierInfo.branches;
        }
        // Fallback to Farm & City depots
        return FARM_AND_CITY_DEPOTS.map(d => ({ name: d, address: d }));
    };

    // Helper: get EasyGo locations
    const EASYGO_LOCATIONS = [
        'Harare CBD', 'Bulawayo', 'Mutare', 'Gweru', 'Bindura', 'Beitbridge',
        'Chitungwiza', 'Marondera', 'Masvingo', 'Chinhoyi', 'Victoria Falls', 'Gwanda'
    ];

    // Render supplier info card
    const renderSupplierCard = () => {
        if (!supplierInfo) return null;
        return (
            <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800 mb-4">
                <h4 className="font-medium text-blue-900 dark:text-blue-200 mb-2 flex items-center gap-2">
                    <Building2 className="w-4 h-4" /> Supplier Details
                </h4>
                <div className="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                    <p><strong>{supplierInfo.name}</strong></p>
                    <p className="flex items-center gap-1"><MapPin className="w-3 h-3" /> {supplierInfo.address}, {supplierInfo.city}</p>
                    <p className="flex items-center gap-1"><User className="w-3 h-3" /> Contact: {supplierInfo.contact_person}</p>
                    <p className="flex items-center gap-1"><Phone className="w-3 h-3" /> {supplierInfo.phone}</p>
                    {supplierInfo.email && <p className="flex items-center gap-1"><Mail className="w-3 h-3" /> {supplierInfo.email}</p>}
                </div>
            </div>
        );
    };

    // Render branch selector (for supplier-based delivery)
    const renderBranchSelector = (branches: { name: string; address: string }[], label: string) => (
        <div>
            <label htmlFor="branchSelect" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {label} <span className="text-red-500">*</span>
            </label>
            <select
                id="branchSelect"
                value={selectedDepot}
                onChange={(e) => setSelectedDepot(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
                <option value="">Select a location</option>
                {branches.map((branch) => (
                    <option key={branch.name} value={branch.name}>
                        {branch.name}{branch.address !== branch.name ? ` — ${branch.address}` : ''}
                    </option>
                ))}
            </select>
        </div>
    );

    // Render ZimPost two-step selector
    const renderZimpostSelector = (cityState: string, setCityState: (v: string) => void, branchState: string, setBranchState: (v: string) => void) => (
        <div className="space-y-4">
            <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select City/Province <span className="text-red-500">*</span>
                </label>
                <select
                    value={cityState}
                    onChange={(e) => { setCityState(e.target.value); setBranchState(''); }}
                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select your city or province</option>
                    {Object.keys(ZIMPOST_LOCATIONS).map((city) => (
                        <option key={city} value={city}>{city}</option>
                    ))}
                </select>
            </div>
            {cityState && (
                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Zim Post Office Branch <span className="text-red-500">*</span>
                    </label>
                    <select
                        value={branchState}
                        onChange={(e) => setBranchState(e.target.value)}
                        className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Select a branch within {cityState}</option>
                        {(ZIMPOST_LOCATIONS[cityState] || []).map((branch) => (
                            <option key={branch} value={branch}>{branch}</option>
                        ))}
                    </select>
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        You will collect your product at your nearest post office.
                    </p>
                </div>
            )}
        </div>
    );

    // Render product-specific delivery controls
    const renderDeliveryControls = () => {
        switch (deliveryType) {
            case 'zimparks':
                return (
                    <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-6 border border-emerald-200 dark:border-emerald-800">
                        <h4 className="font-semibold text-emerald-900 dark:text-emerald-200 mb-4 flex items-center gap-2">
                            <Calendar className="w-5 h-5" /> Your Holiday Details
                        </h4>
                        <div className="space-y-2 text-sm">
                            <p><strong>Destination:</strong> <span className="text-emerald-600">{data.destinationName || 'Not selected'}</span></p>
                            <p><strong>Check-in:</strong> <span className="text-emerald-600">{data.checkInDate || data.bookingDetails?.startDate || 'Not set'}</span></p>
                            <p><strong>Check-out:</strong> <span className="text-emerald-600">{data.checkOutDate || data.bookingDetails?.endDate || 'Not set'}</span></p>
                            {(data.nights || data.bookingDetails?.nights) && (
                                <p><strong>Nights:</strong> <span className="text-emerald-600">{data.nights || data.bookingDetails?.nights}</span></p>
                            )}
                        </div>
                    </div>
                );

            case 'driving':
                return (
                    <div>
                        {renderSupplierCard()}
                        <label htmlFor="easygo" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select Easy Go Branch <span className="text-red-500">*</span>
                        </label>
                        <select
                            id="easygo"
                            value={selectedDepot}
                            onChange={(e) => setSelectedDepot(e.target.value)}
                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Select a branch nearest to you</option>
                            {(supplierInfo?.branches?.length ? supplierInfo.branches.map(b => b.name) : EASYGO_LOCATIONS).map((loc) => (
                                <option key={loc} value={loc}>{loc}</option>
                            ))}
                        </select>
                    </div>
                );

            case 'training':
                return (
                    <div>
                        {supplierLoading ? (
                            <p className="text-gray-500 text-sm">Loading supplier information...</p>
                        ) : (
                            <>
                                {renderSupplierCard()}
                                {supplierInfo && supplierInfo.branches?.length > 1 && (
                                    renderBranchSelector(supplierInfo.branches, 'Select Training Location')
                                )}
                                {supplierInfo && supplierInfo.branches?.length === 1 && (
                                    <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200">
                                        <p className="text-sm text-green-800 dark:text-green-300">
                                            📍 Location: <strong>{supplierInfo.branches[0].name}</strong> — {supplierInfo.branches[0].address}
                                        </p>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                );

            case 'chicken_lite':
            case 'agri':
                return (
                    <div>
                        {supplierLoading ? (
                            <p className="text-gray-500 text-sm">Loading supplier information...</p>
                        ) : (
                            <>
                                {renderSupplierCard()}
                                {renderBranchSelector(
                                    getSupplierBranches(),
                                    `Select ${supplierInfo?.name || 'Farm & City'} Branch`
                                )}
                            </>
                        )}
                    </div>
                );

            case 'chicken_fullhouse':
                return (
                    <div className="space-y-6">
                        {supplierLoading ? (
                            <p className="text-gray-500 text-sm">Loading supplier information...</p>
                        ) : (
                            <>
                                {renderSupplierCard()}
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                                        1. Collection Branch (for feeds & inputs)
                                    </h4>
                                    {renderBranchSelector(
                                        getSupplierBranches(),
                                        `Select ${supplierInfo?.name || 'Farm & City'} Branch`
                                    )}
                                </div>
                            </>
                        )}
                        <div>
                            <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                                2. Zimpost Delivery (for chicken coop & housing)
                            </h4>
                            <p className="text-sm text-gray-500 mb-3">
                                The chicken coop and housing materials will be delivered to your nearest post office via Zimpost Courier Connect.
                            </p>
                            {renderZimpostSelector(zimpostCity, setZimpostCity, zimpostBranch, setZimpostBranch)}
                        </div>
                    </div>
                );

            case 'building':
                return (
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
                                <option key={depot} value={depot}>{depot}</option>
                            ))}
                        </select>
                    </div>
                );

            case 'tuckshop':
                return (
                    <div className="space-y-4">
                        {/* Agent Toggle */}
                        <div className="grid grid-cols-2 gap-4">
                            <div
                                onClick={() => { setTuckshopAgent('Gain Cash & Carry'); setSelectedDepot(''); }}
                                className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${tuckshopAgent === 'Gain Cash & Carry'
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                    }`}
                            >
                                <Building2 className={`h-5 w-5 mb-1 ${tuckshopAgent === 'Gain Cash & Carry' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                <p className="font-medium text-sm text-gray-900 dark:text-white">Gain Cash & Carry</p>
                            </div>
                            <div
                                onClick={() => { setTuckshopAgent('Metro Peech & Browne'); setSelectedDepot(''); }}
                                className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${tuckshopAgent === 'Metro Peech & Browne'
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                    }`}
                            >
                                <Building2 className={`h-5 w-5 mb-1 ${tuckshopAgent === 'Metro Peech & Browne' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                <p className="font-medium text-sm text-gray-900 dark:text-white">Metro Peech & Browne</p>
                            </div>
                        </div>
                        {/* Depot Select */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select {tuckshopAgent} Depot <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={selectedDepot}
                                onChange={(e) => setSelectedDepot(e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Select a depot closest to you</option>
                                {(tuckshopAgent === 'Gain Cash & Carry' ? GAIN_DEPOTS : METRO_DEPOTS).map((depot) => (
                                    <option key={depot} value={depot}>{depot}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                );

            case 'zimpost_default':
                return renderZimpostSelector(selectedCity, setSelectedCity, selectedDepot, setSelectedDepot);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-3xl mx-auto">
                <Card className="p-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-3 mb-2">
                            <Truck className="h-8 w-8 text-emerald-600" />
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Product Delivery Confirmation
                            </h2>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">
                            {getDeliveryMessage(deliveryType, supplierInfo, data)}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">

                        {/* ===== Beneficiary Section ===== */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <Users className="w-5 h-5 text-emerald-600" /> Who is this product for?
                            </h3>
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <button
                                    type="button"
                                    onClick={() => setBeneficiaryType('self')}
                                    className={`p-4 border-2 rounded-lg transition-all text-left ${beneficiaryType === 'self'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                        }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${beneficiaryType === 'self' ? 'border-emerald-600' : 'border-gray-400'}`}>
                                            {beneficiaryType === 'self' && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                        </div>
                                        <User className="w-4 h-4" />
                                        <span className="font-medium">Self</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1 ml-7">For myself</p>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setBeneficiaryType('other')}
                                    className={`p-4 border-2 rounded-lg transition-all text-left ${beneficiaryType === 'other'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                        }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${beneficiaryType === 'other' ? 'border-emerald-600' : 'border-gray-400'}`}>
                                            {beneficiaryType === 'other' && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                        </div>
                                        <Users className="w-4 h-4" />
                                        <span className="font-medium">Other</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1 ml-7">For someone else</p>
                                </button>
                            </div>

                            {beneficiaryType === 'other' && (
                                <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Beneficiary Full Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={beneficiaryName}
                                            onChange={(e) => setBeneficiaryName(e.target.value)}
                                            placeholder="Enter full name"
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Beneficiary National ID <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={beneficiaryId}
                                            onChange={(e) => setBeneficiaryId(e.target.value)}
                                            placeholder="e.g. 63-123456-A-78"
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* ===== Collector Section ===== */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <Package className="w-5 h-5 text-emerald-600" /> Who will collect?
                            </h3>
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <button
                                    type="button"
                                    onClick={() => setCollectorType('self')}
                                    className={`p-4 border-2 rounded-lg transition-all text-left ${collectorType === 'self'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                        }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${collectorType === 'self' ? 'border-emerald-600' : 'border-gray-400'}`}>
                                            {collectorType === 'self' && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                        </div>
                                        <User className="w-4 h-4" />
                                        <span className="font-medium">Self</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1 ml-7">I will collect myself</p>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setCollectorType('other')}
                                    className={`p-4 border-2 rounded-lg transition-all text-left ${collectorType === 'other'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
                                        }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${collectorType === 'other' ? 'border-emerald-600' : 'border-gray-400'}`}>
                                            {collectorType === 'other' && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                        </div>
                                        <Users className="w-4 h-4" />
                                        <span className="font-medium">Other</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1 ml-7">Someone else will collect</p>
                                </button>
                            </div>

                            {collectorType === 'other' && (
                                <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Collector Full Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={collectorName}
                                            onChange={(e) => setCollectorName(e.target.value)}
                                            placeholder="Enter full name"
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Collector National ID <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={collectorId}
                                            onChange={(e) => setCollectorId(e.target.value)}
                                            placeholder="e.g. 63-123456-A-78"
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* ===== Delivery Controls ===== */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <MapPin className="w-5 h-5 text-emerald-600" /> Delivery / Collection Details
                            </h3>
                            {renderDeliveryControls()}
                        </div>

                        {/* Error Message */}
                        {error && (
                            <div className="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
                            </div>
                        )}

                        {/* Action Buttons */}
                        <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Button type="button" variant="outline" onClick={onBack} disabled={loading}>
                                Back
                            </Button>
                            <Button type="submit" disabled={loading} className="bg-emerald-600 hover:bg-emerald-700">
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
