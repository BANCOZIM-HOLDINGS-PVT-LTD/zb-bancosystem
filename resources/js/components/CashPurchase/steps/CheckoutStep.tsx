import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type { CashPurchaseData } from '../CashPurchaseWizard';
import { validateZimbabweanID } from '@/utils/zimbabwean-id-validator';
import {
    AlertCircle,
    User,
    CreditCard,
    DollarSign,
    Shield,
    ChevronLeft
} from 'lucide-react';

interface CheckoutStepProps {
    data: CashPurchaseData;
    onComplete: (checkoutData: Partial<CashPurchaseData>) => void;
    onBack: () => void;
    loading: boolean;
    error?: string;
}

const ME_SYSTEM_PERCENTAGE = 0.10; // 10% of cash price
const TRAINING_PERCENTAGE = 0.055; // 5.5%

export default function CheckoutStep({ data, onComplete, onBack, loading, error }: CheckoutStepProps) {
    const [nationalId, setNationalId] = useState(data.customer?.nationalId || '');
    const [fullName, setFullName] = useState(data.customer?.fullName || '');
    const [phone, setPhone] = useState(data.customer?.phone || '');
    const [email, setEmail] = useState(data.customer?.email || '');
    const [agreedToTerms, setAgreedToTerms] = useState(false);
    const [showPaymentForm, setShowPaymentForm] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const cart = data.cart || [];
    const delivery = data.delivery!;

    // Calculate totals
    const cartTotal = cart.reduce((sum, item) => sum + (item.cashPrice * item.quantity), 0);
    const isMicrobiz = data.purchaseType === 'microbiz';
    const includesMESystem = isMicrobiz && (data.includesMESystem || false);
    const includesTraining = isMicrobiz && (data.includesTraining || false);

    const meSystemFee = includesMESystem ? (cartTotal * ME_SYSTEM_PERCENTAGE) : 0;
    const trainingFee = includesTraining ? (cartTotal * TRAINING_PERCENTAGE) : 0;
    const deliveryFee = delivery.type === 'swift' ? 10 : 0;
    const totalAmount = cartTotal + meSystemFee + trainingFee + deliveryFee;

    const handleNationalIdChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let value = e.target.value.toUpperCase();
        value = value.replace(/[^A-Z0-9-]/g, '');
        const clean = value.replace(/-/g, '');

        let formatted = '';
        if (clean.length > 0) {
            formatted += clean.substring(0, 2);
            if (clean.length > 2) {
                formatted += '-';
                const remaining = clean.substring(2);
                const letterMatch = remaining.match(/[A-Z]/);

                if (letterMatch && letterMatch.index !== undefined) {
                    const letterIndex = letterMatch.index;
                    formatted += remaining.substring(0, letterIndex);
                    formatted += '-';
                    formatted += remaining.charAt(letterIndex);
                    if (remaining.length > letterIndex + 1) {
                        formatted += '-';
                        formatted += remaining.substring(letterIndex + 1, letterIndex + 3);
                    }
                } else {
                    formatted += remaining.substring(0, 7);
                }
            }
        }

        setNationalId(formatted);
        setErrors((prev) => ({ ...prev, nationalId: '' }));
    };

    const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let value = e.target.value;
        if (!value) {
            setPhone('');
            return;
        }

        if (!value.startsWith('+263')) {
            if (value.startsWith('0')) {
                value = '+263' + value.substring(1);
            } else if (value.startsWith('263')) {
                value = '+' + value;
            } else {
                value = '+263' + value.replace(/[^0-9]/g, '');
            }
        }

        const parts = value.split('+');
        const numberPart = parts[1] ? parts[1].replace(/[^0-9]/g, '') : '';
        setPhone('+' + numberPart);
        setErrors((prev) => ({ ...prev, phone: '' }));
    };

    const validateCustomerInfo = () => {
        const newErrors: Record<string, string> = {};

        if (!nationalId.trim()) {
            newErrors.nationalId = 'National ID is required';
        } else {
            const idValidation = validateZimbabweanID(nationalId);
            if (!idValidation.valid) {
                newErrors.nationalId = idValidation.message || 'Invalid National ID format';
            }
        }

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

        if (!phone.trim()) {
            newErrors.phone = 'Phone number is required';
        } else if (!/^\+263[0-9]{9}$/.test(phone.replace(/[\s-]/g, ''))) {
            newErrors.phone = 'Invalid Zimbabwe phone number (e.g., +263771234567)';
        }

        if (email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            newErrors.email = 'Invalid email address';
        }

        if (!agreedToTerms) {
            newErrors.terms = 'You must agree to the terms and conditions';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleProceedToPayment = async () => {
        if (validateCustomerInfo()) {
            // Generate invoice number from national ID (remove dashes)
            const invoiceNumber = nationalId.replace(/-/g, '');

            // Get product names for SMS
            const productNames = cart.map(item => item.name).join(', ');

            // Send SMS notification
            if (nationalId && phone) {
                try {
                    await fetch('/api/invoice-sms/cash-purchase', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            national_id: nationalId,
                            phone: phone,
                            product_name: productNames || 'Items',
                            amount: totalAmount,
                            currency: 'USD'
                        })
                    });
                    console.log('[CheckoutStep] SMS notification sent for invoice:', invoiceNumber);
                } catch (error) {
                    console.error('[CheckoutStep] Failed to send SMS:', error);
                    // Continue even if SMS fails
                }
            }

            setShowPaymentForm(true);
        }
    };

    // Actually handle payment selection (renamed slightly for clarity in flow)
    const handleSelectPaymentMethod = (method: string) => {
        onComplete({
            customer: {
                nationalId: validateZimbabweanID(nationalId).formatted || nationalId,
                fullName,
                phone,
                email: email || undefined,
            },
            payment: {
                method: method as any,
                amount: totalAmount,
                currency: 'USD',
            },
        });
    };

    const handlePaymentCancel = () => {
        setShowPaymentForm(false);
    };

    const formatCurrency = (amount: number) => {
        return `$${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    return (
        <div className="space-y-6 pb-24 sm:pb-8">
            <div>
                <h2 className="text-xl sm:text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Payment & Details
                </h2>
                <p className="text-sm sm:text-base text-[#706f6c] dark:text-[#A1A09A]">
                    Enter your details for delivery and payment
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
                            onChange={handleNationalIdChange}
                            placeholder="XX-XXXXXXX-Y-ZZ"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.nationalId ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                                ${data.customer?.nationalId ? 'bg-transparent' : ''}
                            `}
                            disabled={loading || showPaymentForm}
                            readOnly={!!data.customer?.nationalId}
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
                            onChange={handlePhoneChange}
                            placeholder="+263771234567"
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
                                <span className="text-[#706f6c] dark:text-[#A1A09A]">
                                    Subtotal ({cart.length} items):
                                </span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {formatCurrency(cartTotal)}
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

                    {/* Payment Method Selection & Confirmation */}
                    {showPaymentForm ? (
                        <div className="space-y-4">
                            <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                <h4 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC] mb-4 text-center">
                                    Select Payment Method
                                </h4>
                                <div className="grid grid-cols-2 gap-4 mb-6">
                                    {/* EcoCash */}
                                    <button
                                        onClick={() => handleSelectPaymentMethod('ecocash')}
                                        disabled={loading}
                                        className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700 disabled:opacity-50"
                                    >
                                        <div className="bg-blue-600 text-white font-bold p-2 rounded mb-2 w-full text-center">EcoCash</div>
                                        <span className="text-sm font-medium">EcoCash</span>
                                    </button>

                                    {/* OneMoney */}
                                    <button
                                        onClick={() => handleSelectPaymentMethod('onemoney')}
                                        disabled={loading}
                                        className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700 disabled:opacity-50"
                                    >
                                        <div className="bg-orange-500 text-white font-bold p-2 rounded mb-2 w-full text-center">OneMoney</div>
                                        <span className="text-sm font-medium">OneMoney</span>
                                    </button>

                                    {/* O'Mari */}
                                    <button
                                        onClick={() => handleSelectPaymentMethod('paynow')}
                                        disabled={loading}
                                        className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700 disabled:opacity-50"
                                    >
                                        <div className="bg-red-500 text-white font-bold p-2 rounded mb-2 w-full text-center">O'Mari</div>
                                        <span className="text-sm font-medium">O'Mari</span>
                                    </button>

                                    {/* Visa / Mastercard */}
                                    <button
                                        onClick={() => handleSelectPaymentMethod('paynow')}
                                        disabled={loading}
                                        className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700 disabled:opacity-50"
                                    >
                                        <div className="bg-blue-900 text-white font-bold p-2 rounded mb-2 w-full text-center">VISA / Mastercard</div>
                                        <span className="text-sm font-medium">Card</span>
                                    </button>
                                </div>

                                <p className="text-xs text-center text-gray-500 dark:text-gray-400 mb-6">
                                    Secure payments processing by Paynow.
                                </p>

                                <div className="flex justify-center">
                                    <Button variant="outline" onClick={handlePaymentCancel} className="w-full" disabled={loading}>
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-6 text-center">
                            <h4 className="font-semibold text-lg text-emerald-900 dark:text-emerald-100 mb-2">
                                Pay securely online
                            </h4>
                            <p className="text-sm text-emerald-700 dark:text-emerald-300 mb-4">
                                Choose from EcoCash, OneMoney, Visa, or Mastercard on the next step.
                            </p>
                            <div className="flex justify-center">
                                <Shield className="h-10 w-10 text-emerald-600 opacity-80" />
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

            {/* Navigation Buttons */}
            {!showPaymentForm && (
                <div className="flex justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 mb-32">
                    <Button onClick={onBack} variant="outline" size="lg" disabled={loading}>
                        <ChevronLeft className="mr-2 h-5 w-5" />
                        Back
                    </Button>
                    <Button onClick={handleProceedToPayment} disabled={loading} size="lg" className="bg-emerald-600 hover:bg-emerald-700">
                        <CreditCard className="mr-2 h-5 w-5" />
                        Continue to Pay
                    </Button>
                </div>
            )}
        </div>
    );
}
