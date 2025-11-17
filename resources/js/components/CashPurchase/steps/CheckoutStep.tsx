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
    DollarSign
} from 'lucide-react';

interface CheckoutStepProps {
    data: CashPurchaseData;
    onComplete: (checkoutData: Partial<CashPurchaseData>) => void;
    onBack: () => void;
    loading: boolean;
    error?: string;
}

type PaymentMethod = 'ecocash' | 'onemoney' | 'card';

const ME_SYSTEM_FEE = 9.99;
const TRAINING_PERCENTAGE = 0.055; // 5.5%

export default function CheckoutStep({ data, onComplete, onBack, loading, error }: CheckoutStepProps) {
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(null);
    const [nationalId, setNationalId] = useState('');
    const [fullName, setFullName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [transactionId, setTransactionId] = useState('');
    const [agreedToTerms, setAgreedToTerms] = useState(false);
    const [paymentInitiated, setPaymentInitiated] = useState(false);
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

    const validateForm = () => {
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

        // Validate Payment
        if (paymentInitiated && !transactionId.trim()) {
            newErrors.transactionId = 'Please enter your Paynow transaction ID';
        }

        // Validate Terms
        if (!agreedToTerms) {
            newErrors.terms = 'You must agree to the terms and conditions';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleInitiatePayment = () => {
        if (validateForm()) {
            setPaymentInitiated(true);
        }
    };

    const handleCompleteCheckout = () => {
        if (validateForm()) {
            onComplete({
                customer: {
                    nationalId: validateZimbabweanID(nationalId).formatted || nationalId,
                    fullName,
                    phone,
                    email: email || undefined,
                },
                payment: {
                    method: 'paynow',
                    amount: totalAmount,
                    transactionId: transactionId || undefined,
                },
            });
        }
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
                            disabled={loading || paymentInitiated}
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
                            disabled={loading || paymentInitiated}
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
                            disabled={loading || paymentInitiated}
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
                            disabled={loading || paymentInitiated}
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
                            disabled={loading || paymentInitiated}
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

                    {!paymentInitiated ? (
                        <div className="space-y-4">
                            <div>
                                <h4 className="font-semibold text-sm mb-3 text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Select Payment Method
                                </h4>
                                <div className="grid grid-cols-1 gap-3">
                                    {/* EcoCash */}
                                    <button
                                        onClick={() => setPaymentMethod('ecocash')}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'ecocash'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading || paymentInitiated}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Smartphone className={`h-6 w-6 ${paymentMethod === 'ecocash' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">EcoCash</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Pay with your EcoCash mobile wallet
                                                </p>
                                            </div>
                                            {paymentMethod === 'ecocash' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* OneMoney */}
                                    <button
                                        onClick={() => setPaymentMethod('onemoney')}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'onemoney'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading || paymentInitiated}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Wallet className={`h-6 w-6 ${paymentMethod === 'onemoney' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">OneMoney</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Pay with your OneMoney mobile wallet
                                                </p>
                                            </div>
                                            {paymentMethod === 'onemoney' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>

                                    {/* Card */}
                                    <button
                                        onClick={() => setPaymentMethod('card')}
                                        className={`
                                            p-4 rounded-lg border-2 text-left transition-all
                                            ${paymentMethod === 'card'
                                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                                            }
                                        `}
                                        disabled={loading || paymentInitiated}
                                    >
                                        <div className="flex items-center gap-3">
                                            <CreditCard className={`h-6 w-6 ${paymentMethod === 'card' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Visa/Mastercard/Zimswitch</h5>
                                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    Pay with Credit,Debit or other cards
                                                </p>
                                            </div>
                                            {paymentMethod === 'card' && (
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            )}
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {paymentMethod && (
                                <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <p className="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                        All payments are processed securely via Paynow gateway
                                    </p>
                                    <Button
                                        onClick={handleInitiatePayment}
                                        disabled={loading}
                                        className="w-full bg-emerald-600 hover:bg-emerald-700"
                                    >
                                        <CreditCard className="mr-2 h-5 w-5" />
                                        Continue to Payment
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div className="flex items-start gap-3 mb-3">
                                    <Check className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <h4 className="font-semibold text-sm text-green-900 dark:text-green-100 mb-1">
                                            Complete Your Payment
                                        </h4>
                                        <p className="text-sm text-green-700 dark:text-green-300">
                                            Follow these steps to complete payment:
                                        </p>
                                    </div>
                                </div>
                                <ol className="text-sm text-green-700 dark:text-green-300 space-y-2 ml-8">
                                    <li className="list-decimal">Dial <strong>*151#</strong> on your phone</li>
                                    <li className="list-decimal">Select <strong>Send Money</strong></li>
                                    <li className="list-decimal">Select <strong>Paynow</strong></li>
                                    <li className="list-decimal">Enter Merchant Code: <strong>15444</strong></li>
                                    <li className="list-decimal">Enter Amount: <strong>{formatCurrency(totalAmount)}</strong></li>
                                    <li className="list-decimal">Enter your PIN and confirm</li>
                                    <li className="list-decimal">Copy the transaction ID from the SMS you receive</li>
                                </ol>
                            </div>

                            {/* Transaction ID Input */}
                            <div>
                                <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Paynow Transaction ID <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={transactionId}
                                    onChange={(e) => {
                                        setTransactionId(e.target.value);
                                        setErrors((prev) => ({ ...prev, transactionId: '' }));
                                    }}
                                    placeholder="Enter transaction ID from SMS"
                                    className={`
                                        w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                        dark:bg-gray-800 dark:text-white
                                        ${errors.transactionId ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                                    `}
                                    disabled={loading}
                                />
                                {errors.transactionId && (
                                    <p className="mt-1 text-sm text-red-600">{errors.transactionId}</p>
                                )}
                                <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    <Hash className="h-3 w-3 inline mr-1" />
                                    You'll receive this via SMS after completing payment
                                </p>
                            </div>
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
                        I agree to the{' '}
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
            <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button onClick={onBack} variant="outline" size="lg" disabled={loading}>
                    <ChevronLeft className="mr-2 h-5 w-5" />
                    Back
                </Button>
                <Button
                    onClick={handleCompleteCheckout}
                    disabled={!paymentInitiated || loading}
                    size="lg"
                    className="bg-emerald-600 hover:bg-emerald-700"
                >
                    {loading ? (
                        <>
                            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                            Processing...
                        </>
                    ) : (
                        <>
                            <Check className="mr-2 h-5 w-5" />
                            Complete Purchase
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
}
