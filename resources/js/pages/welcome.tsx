import { type SharedData } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Globe, CreditCard, Briefcase, FileText, Package, ChevronRight, User, DollarSign, ShoppingBag, Hammer, GraduationCap, Laptop, Home, RotateCcw, LoaderCircle } from 'lucide-react';
import Footer from '@/components/Footer';

interface WelcomeProps {
    hasApplications: boolean;
    hasCompletedApplications: boolean;
    referralCode?: string;
    agentId?: number;
    agentName?: string;
}

// Define the 5 main product/service categories
const PRODUCT_INTENTS = [
    {
        id: 'microBiz',
        name: 'Micro and Small Business Starter Kit',
        description: '(empower yourself for income generating projects)',
        icon: Briefcase,
    },
    {
        id: 'smeBiz',
        name: 'Small and Medium Business Booster Kit',
        description: '(expand your existing business operations)',
        icon: ShoppingBag,
    },
    {
        id: 'homeConstruction',
        name: 'House Construction and Improvements',
        description: '(build your house or improve your home living space)',
        icon: Hammer,
    },
    {
        id: 'personalServices',
        name: 'Personal Development',
        description: '(equip yourself or your loved ones with life changing skills )',
        icon: GraduationCap,
    },
    {
        id: 'personalGadgets',
        name: 'Personal and Homeware Products',
        description: '(improve your lifestyle with the latest gadgets & modern furniture)',
        icon: Laptop,
    }
];

export default function Welcome({ hasApplications, hasCompletedApplications, referralCode, agentId, agentName }: WelcomeProps) {
    const { auth } = usePage<SharedData>().props;

    // Steps: language -> paymentMethod -> intent -> currency
    const [currentStep, setCurrentStep] = useState<'language' | 'paymentMethod' | 'intent' | 'currency'>('language');
    const [selectedLanguage, setSelectedLanguage] = useState<string>('en');
    const [selectedCurrency, setSelectedCurrency] = useState<string>('USD');
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<'credit' | 'cash'>('credit');
    const [lastSelectedIntent, setLastSelectedIntent] = useState<string | null>(null);

    // Resume application state
    const [showResumeInput, setShowResumeInput] = useState(false);
    const [resumePhone, setResumePhone] = useState('+263');
    const [resumeLoading, setResumeLoading] = useState(false);
    const [resumeMessage, setResumeMessage] = useState<{ type: 'success' | 'error' | 'info'; text: string } | null>(null);

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
        setCurrentStep('paymentMethod');
    };

    const handlePaymentMethodSelect = (method: 'credit' | 'cash') => {
        setSelectedPaymentMethod(method);
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
        if (!lastSelectedIntent) return;

        // Build route params
        const params: Record<string, string> = {
            language: selectedLanguage,
            currency: currency,
            intent: lastSelectedIntent,
            paymentType: selectedPaymentMethod
        };

        if (referralCode) {
            params.ref = referralCode;
        }

        router.visit(route('application.wizard', params));
    };

    const handleResumeApplication = async () => {
        const phone = resumePhone.replace(/\s+/g, '');
        if (phone.length < 12) {
            setResumeMessage({ type: 'error', text: 'Please enter a valid phone number.' });
            return;
        }

        setResumeLoading(true);
        setResumeMessage(null);

        try {
            const response = await fetch('/api/states/check-existing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ phone }),
            });

            const result = await response.json();

            if (result.has_existing_session) {
                const step = result.current_step;
                const isSubmitted = ['pending_review', 'approved', 'completed', 'rejected'].includes(step);

                if (isSubmitted) {
                    setResumeMessage({
                        type: 'info',
                        text: 'Your application has already been submitted. Please go to "Track Application" to check its status.',
                    });
                } else {
                    // Redirect to resume
                    window.location.href = `/application?session=${result.session_id}&resume=true`;
                }
            } else {
                setResumeMessage({ type: 'error', text: 'No incomplete application found for this number.' });
            }
        } catch {
            setResumeMessage({ type: 'error', text: 'Something went wrong. Please try again.' });
        } finally {
            setResumeLoading(false);
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
                                            Consider me your smart uncle and digital assistant. My mission is to ensure you get the best user experience for your credit purchase on the Buy Now Pay Later facility, because we are family.
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

                            {currentStep === 'paymentMethod' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            How would you like to pay?
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            Select your preferred payment facility
                                        </p>
                                        <button
                                            onClick={() => setCurrentStep('language')}
                                            className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                        >
                                            ← Back
                                        </button>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl mx-auto">
                                        <button
                                            onClick={() => handlePaymentMethodSelect('credit')}
                                            className="group p-6 text-left rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex items-start space-x-4">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full flex-shrink-0">
                                                    <CreditCard className="h-6 w-6 text-emerald-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <h3 className="text-lg font-semibold mb-2 group-hover:text-emerald-600">
                                                        Buy Now Pay Later
                                                    </h3>
                                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                        Get products on credit and pay in installments.
                                                    </p>
                                                </div>
                                                <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0 group-hover:text-emerald-600" />
                                            </div>
                                        </button>

                                        <button
                                            onClick={() => handlePaymentMethodSelect('cash')}
                                            className="group p-6 text-left rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex items-start space-x-4">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full flex-shrink-0">
                                                    <DollarSign className="h-6 w-6 text-emerald-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <h3 className="text-lg font-semibold mb-2 group-hover:text-emerald-600">
                                                        Full Cash Payment
                                                    </h3>
                                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                        Pay in full now and get faster delivery.
                                                    </p>
                                                </div>
                                                <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0 group-hover:text-emerald-600" />
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
                                            Choose the category that best fits your needs
                                        </p>
                                        <button
                                            onClick={() => setCurrentStep('paymentMethod')}
                                            className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                        >
                                            ← Back
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

                                    {/* Resume Application Link */}
                                    <div className="text-center pt-2">
                                        <button
                                            onClick={() => { setShowResumeInput(!showResumeInput); setResumeMessage(null); }}
                                            className="inline-flex items-center gap-1.5 text-sm text-[#706f6c] hover:text-emerald-600 transition-colors dark:text-[#A1A09A] dark:hover:text-emerald-400"
                                        >
                                            <RotateCcw className="h-3.5 w-3.5" />
                                            Already started an application? Resume here
                                        </button>

                                        {showResumeInput && (
                                            <div className="mt-4 max-w-sm mx-auto space-y-3">
                                                <div className="flex gap-2">
                                                    <input
                                                        type="tel"
                                                        placeholder="+263771234567"
                                                        value={resumePhone}
                                                        onChange={(e) => setResumePhone(e.target.value)}
                                                        className="flex-1 rounded-md border border-[#e3e3e0] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-[#3E3E3A] dark:bg-[#1b1b18] dark:text-[#EDEDEC]"
                                                    />
                                                    <button
                                                        onClick={handleResumeApplication}
                                                        disabled={resumeLoading}
                                                        className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                                                    >
                                                        {resumeLoading ? <LoaderCircle className="h-4 w-4 animate-spin" /> : 'Find'}
                                                    </button>
                                                </div>
                                                {resumeMessage && (
                                                    <p className={`text-xs ${resumeMessage.type === 'error' ? 'text-red-600 dark:text-red-400' :
                                                        resumeMessage.type === 'info' ? 'text-blue-600 dark:text-blue-400' :
                                                            'text-emerald-600 dark:text-emerald-400'
                                                        }`}>
                                                        {resumeMessage.text}
                                                    </p>
                                                )}
                                            </div>
                                        )}
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
