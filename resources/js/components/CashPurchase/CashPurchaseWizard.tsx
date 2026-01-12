import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';

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
    cart: {
        id: number;
        name: string;
        cashPrice: number;
        loanPrice: number;
        category: string;
        description?: string;
        image?: string;
        quantity: number;
    }[];
    // Deprecated single product field, keeping for type safety in legacy checks
    product?: any;
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
        currency?: string;
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
    invoiceNumber?: string; // National ID without dashes - used as invoice number
}

interface CashPurchaseWizardProps {
    purchaseType: 'personal' | 'microbiz';
    language?: string;
    currency?: string;
}

type StepType = 'catalogue' | 'delivery' | 'summary' | 'checkout';

const steps = [
    { id: 'catalogue', name: 'Select Product' },
    { id: 'delivery', name: 'Delivery Details' },
    { id: 'summary', name: 'Review Order' },
    { id: 'checkout', name: 'Payment' },
];

export default function CashPurchaseWizard({ purchaseType, language = 'en', currency = 'USD' }: CashPurchaseWizardProps) {
    const [currentStep, setCurrentStep] = useState<StepType>('catalogue');
    const [wizardData, setWizardData] = useState<CashPurchaseData>({
        purchaseType,
        language,
        cart: [], // Initialize empty cart
        payment: {
            method: 'paynow', // Default
            amount: 0,
            currency: currency
        }
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
                    setWizardData(prev => ({
                        ...prev,
                        ...parsed,
                        cart: parsed.cart || [], // Ensure cart exists for legacy data
                    }));

                    // Resume from last incomplete step
                    if (!parsed.customer) {
                        // Logic adjusted: if we have items in cart (or legacy product), go to delivery, else catalogue
                        if ((parsed.cart && parsed.cart.length > 0) || parsed.product) {
                            if (!parsed.delivery) {
                                setCurrentStep('delivery');
                            } else {
                                setCurrentStep('checkout');
                            }
                        } else {
                            setCurrentStep('catalogue');
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
            // Transform data for backend if needed
            const finalData = {
                ...wizardData,
                ...checkoutData,
                items: wizardData.cart, // Map cart to items
            };

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
                        currency={wizardData.payment?.currency || 'USD'}
                        cart={wizardData.cart}
                        onUpdateCart={(newCart) => updateWizardData({ cart: newCart })}
                        onNext={() => handleNext({})}
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
        <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
            <div className="mx-auto max-w-4xl px-4 py-8">
                {/* Progress Bar - ApplicationWizard style */}
                <div className="mb-8">
                    <div className="flex h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                        <div
                            className="transition-all duration-500 bg-emerald-600"
                            style={{ width: `${((getCurrentStepIndex() + 1) / steps.length) * 100}%` }}
                        />
                    </div>
                    <div className="flex justify-between mt-2 text-xs text-gray-500">
                        <span>Step {getCurrentStepIndex() + 1} of {steps.length}</span>
                        <span>{steps[getCurrentStepIndex()].name}</span>
                    </div>
                </div>

                {/* Step Content */}
                <div className="bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] p-6 lg:p-8">
                    {renderStep()}
                </div>
            </div>
        </div>
    );
}