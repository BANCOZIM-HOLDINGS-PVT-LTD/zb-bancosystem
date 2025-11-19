import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type { CashPurchaseData } from '../CashPurchaseWizard';
import { validateZimbabweanID } from '@/utils/zimbabwean-id-validator';
import {
    AlertCircle,
    User,
    CreditCard,
    Smartphone,
    Check,
    Wallet,
    Hash,
    ChevronLeft,
    Loader2,
    DollarSign,
    Shield,
    MessageSquare
} from 'lucide-react';

// Import payment components
import VisaPayment from '../payments/VisaPayment';
import ZimswitchPayment from '../payments/ZimswitchPayment';
import EcoCashPayment from '../payments/EcoCashPayment';
import OneMoneyPayment from '../payments/OneMoneyPayment';
import InnBucksPayment from '../payments/InnBucksPayment';
import OmariPayment from '../payments/OmariPayment';

interface CheckoutStepProps {
    data: CashPurchaseData;
    onComplete: (checkoutData: Partial<CashPurchaseData>) => void;
    onBack: () => void;
    loading: boolean;
    error?: string;
}

type PaymentMethod = 'visa' | 'zimswitch' | 'ecocash' | 'onemoney' | 'innbucks' | 'omari';

const ME_SYSTEM_FEE = 9.99;
const TRAINING_PERCENTAGE = 0.055; // 5.5%

export default function CheckoutStep({ data, onComplete, onBack, loading, error }: CheckoutStepProps) {
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(null);
    const [nationalId, setNationalId] = useState('');
    const [fullName, setFullName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [agreedToTerms, setAgreedToTerms] = useState(false);
    const [showPaymentForm, setShowPaymentForm] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const product = data.product!;
    const delivery = data.delivery!;

    // Check if purchase type is microbiz and calculate fees
    const isMicrobiz = data.purchaseType === 'microbiz';
    const includesMESystem = isMicrobiz && (delivery.includesMESystem || false);
    const includesTraining = isMicrobiz && (delivery.includesTraining || false);
    const meSystemFee = includesMESystem ? ME_SYSTEM_FEE : 0;
    const trainingFee = includesTraining ? (product.cashPrice * TRAINING_PERCENTAGE) : 0;
    const deliveryFee = delivery.type === 'swift' ? 10 : 0;
    const totalAmount = product.cashPrice + meSystemFee + trainingFee + deliveryFee;

    const validateCustomerInfo = () => {
        const newErrors: Record<string, string> = {};

        // Validate National ID
        if (!nationalId.trim()) {
            newErrors.nationalId = 'National ID is required';
        } else {
            const idValidation = validateZimbabweanID(nationalId);
            if (!idValidation.valid) {
                newErrors.nationalId = idValidation.message || 'Invalid National ID format';
            }
        }

        // Validate Surname and First Names
        const surname = fullName.split(' ')[0] || '';
        const firstNames = fullName.split(' ').slice(1).join(' ');

        if (!surname.trim()) {
            newErrors.fullName = 'Surname is required';
        } else if (surname.trim().length < 2) {
            newErrors.fullName = 'Surname must be at least 2 characters';
        } else if (!firstNames.trim()) {
            newErrors.fullName = 'First names are required';
        } else if (firstNames.trim().length < 2) {
            newErrors.fullName = 'First names must be at least 2 characters';
        }

        // Validate Phone
        if (!phone.trim()) {
            newErrors.phone = 'Phone number is required';
        } else if (!/^\+263-[0-9]{9}$/.test(phone.replace(/\s/g, ''))) {
            newErrors.phone = 'Invalid Zimbabwe phone number (e.g., +263-771234567)';
        }

        // Validate Email (optional but must be valid if provided)
        if (email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            newErrors.email = 'Invalid email address';
        }

        // Validate Payment Method Selection
        if (!paymentMethod) {
            newErrors.paymentMethod = 'Please select a payment method';
        }

        // Validate Terms
        if (!agreedToTerms) {
            newErrors.terms = 'You must agree to the terms and conditions';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleContinueToPayment = () => {
        if (validateCustomerInfo()) {
            setShowPaymentForm(true);
        }
    };

    const handlePaymentSuccess = (paymentData: any) => {
        onComplete({
            customer: {
                nationalId: validateZimbabweanID(nationalId).formatted || nationalId,
                fullName,
                phone,
                email: email || undefined,
            },
            payment: {
                ...paymentData,
                amount: totalAmount,
            },
        });
    };

    const handlePaymentCancel = () => {
        setShowPaymentForm(false);
    };

    const formatCurrency = (amount: number) => {
        return `$${amount.toLocaleString()}`;
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Payment & Contact Details
                </h2>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    Enter your details required for delivery and complete payment
                </p>
            </div>

            {error && (
                <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                        <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <h4 className="font-semibold text-sm text-red-900 dark:text-red-100 mb-1">
                                Purchase Failed
                            </h4>
                            <p className="text-sm text-red-700 dark:text-red-300">{error}</p>
                        </div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Left Column - Customer Details */}
                <div className="space-y-4">
                    <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC] flex items-center gap-2">
                        <User className="h-5 w-5 text-emerald-600" />
                        Your Details
                    </h3>

                    {/* National ID */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            National ID <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            value={nationalId}
                            onChange={(e) => {
                                setNationalId(e.target.value.toUpperCase());
                                setErrors((prev) => ({ ...prev, nationalId: '' }));
                            }}
                            placeholder="XX-XXXXXXX-Y-ZZ"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.nationalId ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={loading || showPaymentForm}
                        />
                        {errors.nationalId && (
                            <p className="mt-1 text-sm text-red-600">{errors.nationalId}</p>
                        )}
                    </div>

                    {/* Surname */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            Surname <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            value={fullName.split(' ')[0] || ''}
                            onChange={(e) => {
                                const surname = e.target.value;
                                const firstNames = fullName.split(' ').slice(1).join(' ');
                                setFullName(surname + (firstNames ? ' ' + firstNames : ''));
                                setErrors((prev) => ({ ...prev, fullName: '' }));
                            }}
                            placeholder="Enter your surname"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.fullName ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={loading || showPaymentForm}
                        />
                        {errors.fullName && (
                            <p className="mt-1 text-sm text-red-600">{errors.fullName}</p>
                        )}
                    </div>

                    {/* First Names */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            First Names <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            value={fullName.split(' ').slice(1).join(' ') || ''}
                            onChange={(e) => {
                                const surname = fullName.split(' ')[0] || '';
                                const firstNames = e.target.value;
                                setFullName(surname + (firstNames ? ' ' + firstNames : ''));
                                setErrors((prev) => ({ ...prev, fullName: '' }));
                            }}
                            placeholder="Enter your first names"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.fullName ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={loading || showPaymentForm}
                        />
                        {errors.fullName && (
                            <p className="mt-1 text-sm text-red-600">{errors.fullName}</p>
                        )}
                    </div>

                    {/* Phone Number */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            Phone Number <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="tel"
                            value={phone}
                            onChange={(e) => {
                                setPhone(e.target.value);
                                setErrors((prev) => ({ ...prev, phone: '' }));
                            }}
                            placeholder="+263-771234567"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.phone ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={loading || showPaymentForm}
                        />
                        {errors.phone && (
                            <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                        )}
                    </div>

                    {/* Email (Optional) */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            Email (Optional)
                        </label>
                        <input
                            type="email"
                            value={email}
                            onChange={(e) => {
                                setEmail(e.target.value);
                                setErrors((prev) => ({ ...prev, email: '' }));
                            }}
                            placeholder="your.email@example.com"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={loading || showPaymentForm}
                        />
                        {errors.email && (
                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                        )}
                    </div>
                </div>

                {/* Right Column - Payment */}
                <div className="space-y-4">
                    <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC] flex items-center gap-2">
                        <CreditCard className="h-5 w-5 text-emerald-600" />
                        Payment Details
                    </h3>

                    {/* Payment Summary Box */}
                    <div className="bg-emerald-50 dark:bg-emerald-950/20 border-2 border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                        <div className="flex items-start gap-3 mb-3">
                            <DollarSign className="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                            <h3 className="font-semibold text-base text-[#1b1b18] dark:text-[#EDEDEC]">
                                Payment Summary
                            </h3>
                        </div>

                        <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-[#706f6c] dark:text-[#A1A09A]">Product Price:</span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {formatCurrency(product.cashPrice)}
                                </span>
                            </div>

                            {includesMESystem && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-[#706f6c] dark:text-[#A1A09A]">M&E System:</span>
                                    <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {formatCurrency(meSystemFee)}
                                    </span>
                                </div>
                            )}

                            {includesTraining && (
                            <div className="flex justify-between text-sm">
                                    <span className="text-[#706f6c] dark:text-[#A1A09A]">Training:</span>
                                    <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {formatCurrency(trainingFee)}
                                    </span>
                                </div>
                            )}

                            <div className="flex justify-between text-sm">
                                <span className="text-[#706f6c] dark:text-[#A1A09A]">Delivery Fee:</span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {deliveryFee > 0 ? formatCurrency(deliveryFee) : 'FREE'}
                                </span>
                            </div>

                            <div className="pt-2 border-t-2 border-emerald-300 dark:border-emerald-700">
                                <div className="flex justify-between items-center">
                                    <span className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Total Amount:</span>
                                    <span className="text-2xl font-bold text-emerald-600">
                                        {formatCurrency(totalAmount)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {!showPaymentForm ? (
                        <div className="space-y-4">
                            <div>
                                <h4 className="font-semibold text-sm mb-3 text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Select Payment Method <span className="text-red-600">*</span>
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {/* Visa */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('visa');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'visa'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <CreditCard className={`h-6 w-6 ${paymentMethod === 'visa' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Visa/Mastercard</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    International cards • 3D Secure
                                                </p>
                                            </div>
                                            {paymentMethod === 'visa' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* Zimswitch */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('zimswitch');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'zimswitch'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Shield className={`h-6 w-6 ${paymentMethod === 'zimswitch' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Zimswitch</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Local bank cards • USD/ZIG
                                                </p>
                                            </div>
                                            {paymentMethod === 'zimswitch' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* EcoCash */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('ecocash');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'ecocash'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Smartphone className={`h-6 w-6 ${paymentMethod === 'ecocash' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">EcoCash</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Mobile wallet • Instant
                                                </p>
                                            </div>
                                            {paymentMethod === 'ecocash' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* OneMoney */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('onemoney');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'onemoney'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Wallet className={`h-6 w-6 ${paymentMethod === 'onemoney' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">OneMoney</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    NetOne wallet • USD/ZIG
                                                </p>
                                            </div>
                                            {paymentMethod === 'onemoney' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* InnBucks */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('innbucks');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'innbucks'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Smartphone className={`h-6 w-6 ${paymentMethod === 'innbucks' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">InnBucks</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Auth code • USD only
                                                </p>
                                            </div>
                                            {paymentMethod === 'innbucks' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* Omari */}
                                    <button
                                        onClick={() => {
                                            setPaymentMethod('omari');
                                            setErrors((prev) => ({ ...prev, paymentMethod: '' }));
                                        }}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'omari'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading}
                                    >
                                        <div className="flex items-center gap-3">
                                            <MessageSquare className={`h-6 w-6 ${paymentMethod === 'omari' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Omari</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    OTP verification • USD/ZIG
                                                </p>
                                            </div>
                                            {paymentMethod === 'omari' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>
                                </div>
                                {errors.paymentMethod && (
                                    <p className="mt-2 text-sm text-red-600">{errors.paymentMethod}</p>
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {paymentMethod === 'visa' && (
                                <VisaPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                            {paymentMethod === 'zimswitch' && (
                                <ZimswitchPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                            {paymentMethod === 'ecocash' && (
                                <EcoCashPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                            {paymentMethod === 'onemoney' && (
                                <OneMoneyPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                            {paymentMethod === 'innbucks' && (
                                <InnBucksPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                            {paymentMethod === 'omari' && (
                                <OmariPayment
                                    amount={totalAmount}
                                    onSuccess={handlePaymentSuccess}
                                    onCancel={handlePaymentCancel}
                                    loading={loading}
                                />
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Terms and Conditions */}
            <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                <label className="flex items-start gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={agreedToTerms}
                        onChange={(e) => {
                            setAgreedToTerms(e.target.checked);
                            setErrors((prev) => ({ ...prev, terms: '' }));
                        }}
                        className="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                        disabled={loading}
                    />
                    <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        I agree to the delivery{' '}
                        <a href="#" className="text-emerald-600 hover:text-emerald-700 underline">
                            terms and conditions
                        </a>{' '}
                        and confirm that all information provided is accurate
                    </span>
                </label>
                {errors.terms && (
                    <p className="mt-1 ml-7 text-sm text-red-600">{errors.terms}</p>
                )}
            </div>

            {/* Actions */}
            {!showPaymentForm && (
                <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                    <Button onClick={onBack} variant="outline" size="lg" disabled={loading}>
                        <ChevronLeft className="mr-2 h-5 w-5" />
                        Back
                    </Button>
                    <Button
                        onClick={handleContinueToPayment}
                        disabled={loading}
                        size="lg"
                        className="bg-emerald-600 hover:bg-emerald-700"
                    >
                        <CreditCard className="mr-2 h-5 w-5" />
                        Continue to Payment
                    </Button>
                </div>
            )}
        </div>
    );
}
