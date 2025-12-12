import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ChevronLeft, CheckCircle, ShoppingCart, Truck, FileText, CreditCard, Package } from 'lucide-react';

// Step components
import CatalogueStep from './steps/CatalogueStep';
import DeliveryStep from './steps/DeliveryStep';
import SummaryStep from './steps/SummaryStep';
import CheckoutStep from './steps/CheckoutStep';

// Types
export interface CashPurchaseData {
    purchaseType: 'personal' | 'microbiz';
    language: string;
    includesMESystem?: boolean;
    meSystemFee?: number;
    includesTraining?: boolean;
    trainingFee?: number;
    product?: {
        id: number;
        name: string;
        cashPrice: number;
        loanPrice: number;
        category: string;
        description?: string;
        image?: string;
    };
    delivery?: {
        type: 'swift' | 'gain_outlet';
        depot: string;
        depotName?: string;
        address?: string;
        city?: string;
        region?: string;
        includesMESystem?: boolean;
        includesTraining?: boolean;
    };
    customer?: {
        nationalId: string;
        fullName: string;
        phone: string;
        email?: string;
    };
    payment?: {
        method: 'paynow' | 'ecocash' | 'onemoney';
        amount: number;
        currency?: 'USD';
        // Common fields
        transactionId?: string;
        // Mobile wallet specific (EcoCash, OneMoney, Omari)
        mobileNumber?: string;
        otpCode?: string;
        // Card specific (Visa, Zimswitch)
        cardNumber?: string;
        cardHolder?: string;
        expiryMonth?: string;
        expiryYear?: string;
        cvv?: string;
        // InnBucks specific
        authorizationCode?: string;
        // Payment gateway response
        paymentReference?: string;
        paymentStatus?: 'pending' | 'success' | 'failed';
    };
}

interface CashPurchaseWizardProps {
    purchaseType: 'personal' | 'microbiz';
    language?: string;
}

type StepType = 'catalogue' | 'delivery' | 'summary' | 'checkout';

const steps = [
    { id: 'catalogue', name: 'Select Product', icon: ShoppingCart },
    { id: 'delivery', name: 'Delivery Details', icon: Truck },
    { id: 'summary', name: 'Review Order', icon: FileText },
    { id: 'checkout', name: 'Payment', icon: CreditCard },
];

export default function CashPurchaseWizard({ purchaseType, language = 'en' }: CashPurchaseWizardProps) {
    const [currentStep, setCurrentStep] = useState<StepType>('catalogue');
    const [wizardData, setWizardData] = useState<CashPurchaseData>({
        purchaseType,
        language,
    });
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Pre-fill user data from auth
    const { props } = usePage<any>();
    const user = props.auth?.user;

    useEffect(() => {
        if (user) {
            setWizardData(prev => {
                const currentCustomer = prev.customer || { nationalId: '', fullName: '', phone: '' };
                let changed = false;

                // Pre-fill National ID (Strict)
                if (user.national_id && currentCustomer.nationalId !== user.national_id) {
                    currentCustomer.nationalId = user.national_id;
                    changed = true;
                }

                // Pre-fill Phone (+263 Only)
                if (user.phone && user.phone.startsWith('+263')) {
                    if (currentCustomer.phone !== user.phone) {
                        currentCustomer.phone = user.phone;
                        changed = true;
                    }
                }

                // Pre-fill Name if available logic (optional, but good for UX)
                if (user.name && !currentCustomer.fullName) {
                    currentCustomer.fullName = user.name;
                    changed = true;
                }

                if (changed) {
                    return { ...prev, customer: currentCustomer };
                }
                return prev;
            });
        }
    }, [user]);
    // Save to localStorage for recovery
    useEffect(() => {
        const savedData = localStorage.getItem('cashPurchaseData');
        if (savedData) {
            try {
                const parsed = JSON.parse(savedData);
                if (parsed.purchaseType === purchaseType) {
                    setWizardData(parsed);
                    // Resume from last incomplete step
                    if (!parsed.customer) {
                        if (!parsed.delivery) {
                            setCurrentStep('delivery');
                        } else if (!parsed.product) {
                            setCurrentStep('catalogue');
                        } else {
                            setCurrentStep('checkout');
                        }
                    }
                }
            } catch (e) {
                console.error('Failed to parse saved data', e);
            }
        }
    }, [purchaseType]);

    // Save wizard data to localStorage
    useEffect(() => {
        localStorage.setItem('cashPurchaseData', JSON.stringify(wizardData));
    }, [wizardData]);

    const updateWizardData = (data: Partial<CashPurchaseData>) => {
        setWizardData((prev) => ({ ...prev, ...data }));
        setErrors({});
    };

    const handleNext = (stepData: Partial<CashPurchaseData>) => {
        updateWizardData(stepData);

        // Navigate to next step
        if (currentStep === 'catalogue') {
            setCurrentStep('delivery');
        } else if (currentStep === 'delivery') {
            setCurrentStep('summary');
        } else if (currentStep === 'summary') {
            setCurrentStep('checkout');
        }
    };

    const handleBack = () => {
        if (currentStep === 'delivery') {
            setCurrentStep('catalogue');
        } else if (currentStep === 'summary') {
            setCurrentStep('delivery');
        } else if (currentStep === 'checkout') {
            setCurrentStep('summary');
        }
    };

    const handleCheckoutComplete = async (checkoutData: Partial<CashPurchaseData>) => {
        setLoading(true);
        try {
            const finalData = { ...wizardData, ...checkoutData };

            // Submit to backend
            const response = await fetch('/api/cash-purchases', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(finalData),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Clear saved data
                localStorage.removeItem('cashPurchaseData');

                // Redirect to success page or payment gateway
                if (result.data.redirect_url) {
                    window.location.href = result.data.redirect_url;
                } else {
                    router.visit(route('cash.purchase.success', { purchaseNumber: result.data.purchase_number }));
                }
            } else {
                throw new Error(result.message || 'Purchase failed');
            }
        } catch (error: any) {
            console.error('Purchase error:', error);
            setErrors({ checkout: error.message || 'An error occurred. Please try again.' });
        } finally {
            setLoading(false);
        }
    };

    const getCurrentStepIndex = () => {
        return steps.findIndex((step) => step.id === currentStep);
    };

    const renderStep = () => {
        switch (currentStep) {
            case 'catalogue':
                return (
                    <CatalogueStep
                        purchaseType={purchaseType}
                        selectedProduct={wizardData.product}
                        onNext={(data) => handleNext({ product: data })}
                        onBack={() => router.visit(route('welcome'))}
                    />
                );

            case 'delivery':
                return (
                    <DeliveryStep
                        data={wizardData}
                        onNext={(data) => handleNext({ delivery: data })}
                        onBack={handleBack}
                    />
                );

            case 'summary':
                return (
                    <SummaryStep
                        data={wizardData}
                        onNext={(data) => {
                            updateWizardData(data);
                            setCurrentStep('checkout');
                        }}
                        onBack={handleBack}
                    />
                );

            case 'checkout':
                return (
                    <CheckoutStep
                        data={wizardData}
                        onComplete={handleCheckoutComplete}
                        onBack={handleBack}
                        loading={loading}
                        error={errors.checkout}
                    />
                );

            default:
                return null;
        }
    };

    return (
        <div className="min-h-screen py-8 px-4 lg:px-8">
            <div className="max-w-6xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">
                        Buy with Cash - {purchaseType === 'personal' ? 'Personal Products' : 'MicroBiz Starter Pack'}
                    </h1>
                    <p className="text-[#706f6c] dark:text-[#A1A09A]">
                        Complete your purchase in a few simple steps
                    </p>
                </div>

                {/* Progress Steps */}
                <div className="mb-8">
                    <div className="flex items-center justify-between">
                        {steps.map((step, index) => {
                            const Icon = step.icon;
                            const isActive = step.id === currentStep;
                            const isCompleted = getCurrentStepIndex() > index;

                            return (
                                <div key={step.id} className="flex-1 flex items-center">
                                    <div className="flex flex-col items-center flex-1">
                                        <div
                                            className={`
                                                w-12 h-12 rounded-full flex items-center justify-center mb-2 transition-all
                                                ${isActive
                                                    ? 'bg-emerald-600 text-white shadow-lg scale-110'
                                                    : isCompleted
                                                        ? 'bg-emerald-500 text-white'
                                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                                                }
                                            `}
                                        >
                                            {isCompleted ? (
                                                <CheckCircle className="h-6 w-6" />
                                            ) : (
                                                <Icon className="h-6 w-6" />
                                            )}
                                        </div>
                                        <span
                                            className={`
                                                text-sm font-medium text-center
                                                ${isActive
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : isCompleted
                                                        ? 'text-emerald-500 dark:text-emerald-400'
                                                        : 'text-gray-500 dark:text-gray-400'
                                                }
                                            `}
                                        >
                                            {step.name}
                                        </span>
                                    </div>
                                    {index < steps.length - 1 && (
                                        <div
                                            className={`
                                                flex-1 h-1 mx-4 rounded transition-all
                                                ${isCompleted
                                                    ? 'bg-emerald-500'
                                                    : 'bg-gray-200 dark:bg-gray-700'
                                                }
                                            `}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Step Content */}
                <div className="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-6 lg:p-8">
                    {renderStep()}
                </div>

                {/* Help Text */}
                <div className="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    <Package className="h-4 w-4 inline mr-1" />
                    Need help? Contact our support team at{' '}
                    <a href="tel:+263772000000" className="text-emerald-600 hover:text-emerald-700">
                        +263 77 200 0000
                    </a>
                </div>
            </div>
        </div>
    );
}