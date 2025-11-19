import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Globe, CreditCard, Briefcase, FileText, Package, ChevronRight, User, DollarSign, ShoppingBag } from 'lucide-react';

interface WelcomeProps {
    hasApplications: boolean;
    hasCompletedApplications: boolean;
    referralCode?: string;
    agentId?: number;
    agentName?: string;
}

export default function Welcome({ hasApplications, hasCompletedApplications, referralCode, agentId, agentName }: WelcomeProps) {
    const { auth } = usePage<SharedData>().props;

    // If user is authenticated, skip directly to intent selection
    // Otherwise, start with language selection
    const getInitialStep = () => {
        if (auth.user) {
            return 'intent';
        }
        return 'language';
    };

    const [currentStep, setCurrentStep] = useState<'language' | 'auth' | 'intent'>(getInitialStep());
    const [selectedLanguage, setSelectedLanguage] = useState<string>('en');

    const languages = [
        { code: 'en', name: 'English', greeting: 'Welcome to Microbiz' },
        { code: 'sn', name: 'Shona', greeting: 'Mauya kuZB Bank' },
        { code: 'nd', name: 'Ndebele', greeting: 'Ngiyakwemukela kuZB Bank' }
    ];

    // All possible intents
    const allIntents = [
        {
            id: 'hirePurchase',
            name: 'Buy on Credit___________ (Hire Purchase)_________ Personal Products_______',
            icon: CreditCard,
            description: 'Get credit for phones, furniture, appliances and more',
            route: 'application.wizard',
            forNewUsers: true,  // Show only for new users
        },
        {
            id: 'microBiz',
            name: 'Buy on Credit________ Business Starter Pack (MicroBiz)__________',
            icon: Briefcase,
            description: 'A jump start into the world of entrepreneurship',
            route: 'application.wizard',
            forNewUsers: true,  // Show only for new users
        },
        {
            id: 'cashPurchasePersonal',
            name: 'Buy with Cash - Personal Products',
            icon: ShoppingBag,
            description: 'Purchase products directly with cash payment via Paynow',
            route: 'cash.purchase',
            forNewUsers: true,  // Show only for new users
        },
        {
            id: 'cashPurchaseMicroBiz',
            name: 'Buy with Cash - MicroBiz Starter Pack',
            icon: DollarSign,
            description: 'Get your business started with an instant cash purchase',
            route: 'cash.purchase',
            forNewUsers: true,  // Show only for new users
        },
        {
            id: 'checkStatus',
            name: 'Track your application',
            icon: FileText,
            description: 'Check the status of your existing application',
            route: 'application.status',
            forNewUsers: false,  // Show only for returning users
        },
        {
            id: 'trackDelivery',
            name: 'Track your delivery',
            icon: Package,
            description: 'Monitor the delivery of your product or equipment',
            route: 'delivery.tracking',
            forNewUsers: false,  // Show only for returning users
        }
    ];

    // Filter intents based on whether user has COMPLETED applications
    const intents = useMemo(() => {
        if (hasCompletedApplications) {
            // Returning user with completed applications - show only tracking options
            return allIntents.filter(intent => !intent.forNewUsers);
        } else {
            // New user or user with incomplete applications - show application options
            return allIntents.filter(intent => intent.forNewUsers);
        }
    }, [hasCompletedApplications]);

    const handleLanguageSelect = (language: string) => {
        setSelectedLanguage(language);
        // If user is authenticated, go directly to intent selection
        if (auth.user) {
            setCurrentStep('intent');
        } else {
            // Otherwise, show auth options
            setCurrentStep('auth');
        }
    };

    const handleIntentSelect = (intent: string) => {
        const selectedIntent = intents.find(i => i.id === intent);
        if (selectedIntent) {
            // Build base params
            let params: Record<string, string> = {};

            if (intent === 'hirePurchase' || intent === 'microBiz') {
                params = { intent, language: selectedLanguage };
                // Add referral code if available
                if (referralCode) {
                    params.ref = referralCode;
                }
                window.location.href = route(selectedIntent.route, params);
            } else if (intent === 'cashPurchasePersonal' || intent === 'cashPurchaseMicroBiz') {
                // Determine purchase type based on intent
                const purchaseType = intent === 'cashPurchasePersonal' ? 'personal' : 'microbiz';
                params = { type: purchaseType, language: selectedLanguage };
                // Add referral code if available
                if (referralCode) {
                    params.ref = referralCode;
                }
                window.location.href = route(selectedIntent.route, params);
            } else {
                params = { language: selectedLanguage };
                window.location.href = route(selectedIntent.route, params);
            }
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
                                            Consider me your digital uncle, here to assist you to get the best digital credit application experience, because we are family.
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

                            {currentStep === 'auth' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            {selectedLang?.greeting}
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            Please register or login to continue
                                        </p>
                                        <button
                                            onClick={() => setCurrentStep('language')}
                                            className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                        >
                                            ← Back to Start
                                        </button>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl mx-auto">
                                        <Link
                                            href={route('client.login')}
                                            className="group p-8 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <User className="h-8 w-8 text-emerald-600" />
                                                </div>
                                                <h3 className="text-xl font-semibold group-hover:text-emerald-600">
                                                    Log in
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    Already have an account? Sign in with your National ID
                                                </p>
                                                <ChevronRight className="h-5 w-5 text-gray-400 group-hover:text-emerald-600" />
                                            </div>
                                        </Link>

                                        <Link
                                            href={route('client.register')}
                                            className="group p-8 text-center rounded-lg border border-[#e3e3e0] transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:border-[#3E3E3A] dark:hover:border-emerald-500 dark:hover:bg-emerald-950/20"
                                        >
                                            <div className="flex flex-col items-center space-y-3">
                                                <div className="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-full">
                                                    <User className="h-8 w-8 text-emerald-600" />
                                                </div>
                                                <h3 className="text-xl font-semibold group-hover:text-emerald-600">
                                                    Register
                                                </h3>
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    New here? Create an account to get started
                                                </p>
                                                <ChevronRight className="h-5 w-5 text-gray-400 group-hover:text-emerald-600" />
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {currentStep === 'intent' && (
                                <div className="space-y-8">
                                    <div className="text-center">
                                        <h1 className="text-3xl font-bold mb-4">
                                            {selectedLang?.greeting}
                                        </h1>
                                        <p className="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                                            How can we help you today?
                                        </p>
                                        {!auth.user && (
                                            <button
                                                onClick={() => setCurrentStep('language')}
                                                className="mt-4 text-sm text-emerald-600 hover:text-emerald-700"
                                            >
                                                ← Back to Start
                                            </button>
                                        )}
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2 max-w-4xl mx-auto">
                                        {intents.map((intent) => {
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

                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
