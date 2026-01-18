import { type SharedData } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Globe, CreditCard, Briefcase, FileText, Package, ChevronRight, User, DollarSign, ShoppingBag, Banknote, Hammer, GraduationCap, Laptop, Home } from 'lucide-react';
import Footer from '@/components/Footer';

interface WelcomeProps {
    hasApplications: boolean;
    hasCompletedApplications: boolean;
    referralCode?: string;
    agentId?: number;
    agentName?: string;
}

// Define the 4 main product/service categories
const PRODUCT_INTENTS = [
    {
        id: 'microBiz',
        name: 'Small to Medium Business Starter Pack',
        description: '(empower yourself for income generating projects)',
        icon: Briefcase,
    },
    {
        id: 'homeConstruction',
        name: 'House Construction and Improvements',
        description: '(build your house or improve your home living space)',
        icon: Hammer,
    },
    {
        id: 'personalServices',
        name: 'Invest in Personal Development',
        description: '(equip yourself or your loved ones with life changing skills )',
        icon: GraduationCap,
    },
    {
        id: 'personalGadgets',
        name: 'Personal and Homeware Products',
        description: '(improve your lifestyle with the latest gadgets & furniture)',
        icon: Laptop,
    }
];

export default function Welcome({ hasApplications, hasCompletedApplications, referralCode, agentId, agentName }: WelcomeProps) {
    const { auth } = usePage<SharedData>().props;

    // Steps: language -> paymentMode -> intent -> currency
    const getInitialStep = () => {
        // Always start at language, regardless of auth status
        return 'language';
    };

    const [currentStep, setCurrentStep] = useState<'language' | 'paymentMode' | 'intent' | 'currency'>('language');
    const [selectedLanguage, setSelectedLanguage] = useState<string>('en');
    const [selectedCurrency, setSelectedCurrency] = useState<string>('USD');
    const [paymentMode, setPaymentMode] = useState<'cash' | 'credit' | null>(null);
    const [lastSelectedIntent, setLastSelectedIntent] = useState<string | null>(null);

    const languages = [
        { code: 'en', name: 'English', greeting: 'Welcome to Microbiz' },
        { code: 'sn', name: 'Shona', greeting: 'Mauya kuZB Bank' },
        { code: 'nd', name: 'Ndebele', greeting: 'Ngiyakwemukela kuZB Bank' }
    ];

    // Tracking intents for returning users
    const trackingIntents = [
        {
            id: 'checkStatus',
            name: 'Track your application',
            icon: FileText,
            description: 'Check the status of your existing application',
            route: 'application.status',
        },
        {
            id: 'trackDelivery',
            name: 'Track your delivery',
            icon: Package,
            description: 'Monitor the delivery of your product or equipment',
            route: 'delivery.tracking',
        }
    ];

    const handleLanguageSelect = (language: string) => {
        setSelectedLanguage(language);
        // Skip auth step - go directly to payment mode
        setCurrentStep('paymentMode');
    };

    const handlePaymentModeSelect = (mode: 'cash' | 'credit') => {
        setPaymentMode(mode);
        setCurrentStep('intent');
    };

    const handleIntentSelect = (intentId: string) => {
        // Check if it's a tracking intent
        const trackingIntent = trackingIntents.find(i => i.id === intentId);
        if (trackingIntent) {
            const params: Record<string, string> = { language: selectedLanguage };
            router.visit(route(trackingIntent.route, params));
            return;
        }

        // It's a product intent
        setLastSelectedIntent(intentId);
        setCurrentStep('currency');
    };

    const handleCurrencySelect = (currency: string) => {
        setSelectedCurrency(currency);
        if (!lastSelectedIntent || !paymentMode) return;

        // Build route params
        const params: Record<string, string> = {
            language: selectedLanguage,
            currency: currency
        };

        if (referralCode) {
            params.ref = referralCode;
        }

        if (paymentMode === 'cash') {
            // Cash Purchase Flow
            // We pass the new intent ID as the 'type' to the cash purchase page
            // The CashPurchase component/wizard will need to handle these new types
            params.type = lastSelectedIntent;
            router.visit(route('cash.purchase', params));
        } else {
            // Credit Flow
            // We pass the new intent ID as 'intent'
            params.intent = lastSelectedIntent;
            router.visit(route('application.wizard', params));
        }
    };

    const selectedLang = languages.find(l => l.code === selectedLanguage);

    return (
        <>
            <Head title="BancoSystem - Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                {auth.user && (
                    <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                        <nav className="flex items-center justify-end gap-4">
                            <Link
                                href={route('dashboard')}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        </nav>
                    </header>
                )}

                {referralCode && agentName && (
                    <div className="mb-6 w-full max-w-4xl">
                        <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-4 dark:bg-emerald-950/20 dark:border-emerald-800">
                            <p className="text-sm text-emerald-800 dark:text-emerald-300">
                                <span className="font-semibold">Referred by:</span> {agentName}
                            </p>
                        </div>
                    </div>
                )}

                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="w-full max-w-4xl">
                        <div className="bg-white rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] p-6 lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">

                            {currentStep === 'language' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <img
                                            src="/adala.jpg"
                                            alt="Adala Logo"
                                            className="mx-auto h-16 w-16 mb-6 rounded-full object-cover"
                                        />
                                        <h1 className="text-3xl font-bold mb-4">Hello there! I am Adala</h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            consider me your smart uncle and digital assistant. My mission is to ensure you get the best user experience for your intended acquisition because we are family.
                    </p>
                                    </div>

                                    <div className="flex justify-center max-w-md mx-auto">
                                        <button
                                            onClick={() => handleLanguageSelect('en')}
                                            className="group w-full p-8 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <h3 className="text-xl font-semibold mb-2 group-hover:text-emerald-600">Proceed</h3>
                                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">Let's get started</p>
                                            <ChevronRight className="mx-auto mt-4 h-6 w-6 text-gray-400 group-hover:text-emerald-600" />
                                        </button>
                                    </div>
                                </div>
                            )}


                            {currentStep === 'paymentMode' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            Start Application
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            How would you like to purchase today?
                                        </p>
                                        {!auth.user && (
                                            <button
                                                onClick={() => setCurrentStep('language')}
                                                className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                            >
                                                ← Back
                                            </button>
                                        )}
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl mx-auto">
                                        <button
                                            onClick={() => handlePaymentModeSelect('credit')}

                                            className="group p-8 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <CreditCard className="h-8 w-8 text-emerald-600" />
                                                </div>
                                                <h3 className="text-xl font-semibold group-hover:text-emerald-600">
                                                    Buy on Credit
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    (apply for hire purchase)
                                                </p>
                                                <ChevronRight className="h-5 w-5 text-gray-400 group-hover:text-emerald-600" />
                                            </div>
                                        </button>

                                        <button
                                            onClick={() => handlePaymentModeSelect('cash')}
                                            className="group p-8 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <Banknote className="h-8 w-8 text-emerald-600" />
                                                </div>
                                                <h3 className="text-xl font-semibold group-hover:text-emerald-600">
                                                    Buy with Cash
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    (EcoCash/Zimswitch/Mastercard/Visa)
                                                </p>
                                                <ChevronRight className="h-5 w-5 text-gray-400 group-hover:text-emerald-600" />
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {currentStep === 'intent' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            Select Category
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            You are viewing <strong>{paymentMode === 'cash' ? 'Cash Purchase' : 'Credit'}</strong> options.
                                        </p>
                                        <button
                                            onClick={() => setCurrentStep('paymentMode')}
                                            className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                        >
                                            ← Change Mode
                                        </button>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-4xl mx-auto">
                                        {PRODUCT_INTENTS.map((intent) => {
                                            const Icon = intent.icon;
                                            return (
                                                <button
                                                    key={intent.id}
                                                    onClick={() => handleIntentSelect(intent.id)}
                                                    className="group p-6 text-left rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                                >
                                                    <div className="flex items-start space-x-4">
                                                        <Icon className="h-8 w-8 text-emerald-600 flex-shrink-0 group-hover:scale-110 transition-transform" />
                                                        <div className="flex-1">
                                                            <h3 className="text-lg font-semibold mb-2 group-hover:text-emerald-600">
                                                                {intent.name}
                                                            </h3>
                                                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                                {intent.description}
                                                            </p>
                                                        </div>
                                                        <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0 group-hover:text-emerald-600" />
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {currentStep === 'currency' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            Select Currency
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            Please select the applicable currency
                                        </p>
                                        <button
                                            onClick={() => setCurrentStep('intent')}
                                            className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                        >
                                            ← Back to Categories
                                        </button>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-md mx-auto">
                                        <button
                                            onClick={() => handleCurrencySelect('USD')}
                                            className="group p-6 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <DollarSign className="h-8 w-8 text-emerald-600" />
                                                </div>
                                                <h3 className="text-xl font-bold group-hover:text-emerald-600">
                                                    USD
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    United States Dollar
                                                </p>
                                            </div>
                                        </button>

                                        <button
                                            onClick={() => handleCurrencySelect('ZiG')}
                                            className="group p-6 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <span className="text-2xl font-bold text-emerald-600">Z</span>
                                                </div>
                                                <h3 className="text-xl font-bold group-hover:text-emerald-600">
                                                    ZiG
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    Zimbabwe Gold
                                                </p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            )}

                        </div>
                    </main>
                </div>
            </div>

            <Footer />
        </>
    );
}
