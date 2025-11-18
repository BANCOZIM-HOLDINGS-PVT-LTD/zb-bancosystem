import { useState, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { CreditCard, Shield, AlertCircle, Loader2 } from 'lucide-react';
import { convertUSDtoZIG, formatCurrency } from '@/utils/currency';

interface ZimswitchPaymentProps {
    amount: number;
    onSuccess: (paymentData: any) => void;
    onCancel: () => void;
    loading?: boolean;
}

export default function ZimswitchPayment({ amount, onSuccess, onCancel, loading }: ZimswitchPaymentProps) {
    const [cardNumber, setCardNumber] = useState('');
    const [cardHolder, setCardHolder] = useState('');
    const [expiryMonth, setExpiryMonth] = useState('');
    const [expiryYear, setExpiryYear] = useState('');
    const [pin, setPin] = useState('');
    const [currency, setCurrency] = useState<'USD' | 'ZIG'>('USD');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Calculate amount in selected currency
    const paymentAmount = useMemo(() => {
        return currency === 'ZIG' ? convertUSDtoZIG(amount) : amount;
    }, [amount, currency]);

    const formatCardNumber = (value: string) => {
        const cleaned = value.replace(/\s/g, '');
        const chunks = cleaned.match(/.{1,4}/g);
        return chunks ? chunks.join(' ') : cleaned;
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};

        // Validate card number
        const cleanedCard = cardNumber.replace(/\s/g, '');
        if (!cleanedCard) {
            newErrors.cardNumber = 'Card number is required';
        } else if (!/^\d{16}$/.test(cleanedCard)) {
            newErrors.cardNumber = 'Card number must be 16 digits';
        }

        // Validate card holder
        if (!cardHolder.trim()) {
            newErrors.cardHolder = 'Card holder name is required';
        }

        // Validate expiry
        const month = parseInt(expiryMonth);
        if (!expiryMonth || month < 1 || month > 12) {
            newErrors.expiryMonth = 'Invalid month';
        }

        const year = parseInt(expiryYear);
        const currentYear = new Date().getFullYear() % 100;
        if (!expiryYear || year < currentYear) {
            newErrors.expiryYear = 'Invalid or expired year';
        }

        // Validate PIN
        if (!pin) {
            newErrors.pin = 'PIN is required';
        } else if (!/^\d{4}$/.test(pin)) {
            newErrors.pin = 'PIN must be 4 digits';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validateForm()) return;

        setProcessing(true);
        try {
            const paymentData = {
                method: 'zimswitch',
                amount: paymentAmount,
                currency,
                cardNumber: cardNumber.replace(/\s/g, '').slice(-4), // Only store last 4 digits
                cardHolder,
                expiryMonth,
                expiryYear,
                paymentStatus: 'pending',
                transactionId: `ZIMSWITCH-${Date.now()}`,
            };

            onSuccess(paymentData);
        } catch (error) {
            setErrors({ submit: 'Payment failed. Please try again.' });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <Shield className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-sm text-blue-900 dark:text-blue-100 mb-1">
                            Zimswitch Local Bank Card
                        </h4>
                        <p className="text-sm text-blue-700 dark:text-blue-300">
                            Pay with your local bank card. Supports both USD and ZIG accounts.
                        </p>
                    </div>
                </div>
            </div>

            {errors.submit && (
                <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                        <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
                        <p className="text-sm text-red-700 dark:text-red-300">{errors.submit}</p>
                    </div>
                </div>
            )}

            {/* Currency Selection */}
            <div>
                <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Currency <span className="text-red-600">*</span>
                </label>
                <div className="grid grid-cols-2 gap-3">
                    <button
                        onClick={() => setCurrency('USD')}
                        className={`
                            p-3 rounded-lg border-2 text-center transition-all
                            ${currency === 'USD'
                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                            }
                        `}
                        disabled={processing || loading}
                    >
                        <span className={`font-semibold ${currency === 'USD' ? 'text-emerald-600' : 'text-[#1b1b18] dark:text-[#EDEDEC]'}`}>
                            USD
                        </span>
                    </button>
                    <button
                        onClick={() => setCurrency('ZIG')}
                        className={`
                            p-3 rounded-lg border-2 text-center transition-all
                            ${currency === 'ZIG'
                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400'
                            }
                        `}
                        disabled={processing || loading}
                    >
                        <span className={`font-semibold ${currency === 'ZIG' ? 'text-emerald-600' : 'text-[#1b1b18] dark:text-[#EDEDEC]'}`}>
                            ZIG
                        </span>
                    </button>
                </div>
            </div>

            {/* Card Number */}
            <div>
                <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Card Number <span className="text-red-600">*</span>
                </label>
                <input
                    type="text"
                    value={cardNumber}
                    onChange={(e) => {
                        const formatted = formatCardNumber(e.target.value.replace(/\D/g, '').slice(0, 16));
                        setCardNumber(formatted);
                        setErrors((prev) => ({ ...prev, cardNumber: '' }));
                    }}
                    placeholder="1234 5678 9012 3456"
                    maxLength={19}
                    className={`
                        w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                        dark:bg-gray-800 dark:text-white
                        ${errors.cardNumber ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                    `}
                    disabled={processing || loading}
                />
                {errors.cardNumber && (
                    <p className="mt-1 text-sm text-red-600">{errors.cardNumber}</p>
                )}
            </div>

            {/* Card Holder */}
            <div>
                <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Card Holder Name <span className="text-red-600">*</span>
                </label>
                <input
                    type="text"
                    value={cardHolder}
                    onChange={(e) => {
                        setCardHolder(e.target.value.toUpperCase());
                        setErrors((prev) => ({ ...prev, cardHolder: '' }));
                    }}
                    placeholder="JOHN DOE"
                    className={`
                        w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                        dark:bg-gray-800 dark:text-white
                        ${errors.cardHolder ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                    `}
                    disabled={processing || loading}
                />
                {errors.cardHolder && (
                    <p className="mt-1 text-sm text-red-600">{errors.cardHolder}</p>
                )}
            </div>

            {/* Expiry and PIN */}
            <div className="grid grid-cols-3 gap-4">
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Month <span className="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        value={expiryMonth}
                        onChange={(e) => {
                            const value = e.target.value.replace(/\D/g, '').slice(0, 2);
                            setExpiryMonth(value);
                            setErrors((prev) => ({ ...prev, expiryMonth: '' }));
                        }}
                        placeholder="MM"
                        maxLength={2}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.expiryMonth ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                        disabled={processing || loading}
                    />
                    {errors.expiryMonth && (
                        <p className="mt-1 text-sm text-red-600">{errors.expiryMonth}</p>
                    )}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Year <span className="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        value={expiryYear}
                        onChange={(e) => {
                            const value = e.target.value.replace(/\D/g, '').slice(0, 2);
                            setExpiryYear(value);
                            setErrors((prev) => ({ ...prev, expiryYear: '' }));
                        }}
                        placeholder="YY"
                        maxLength={2}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.expiryYear ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                        disabled={processing || loading}
                    />
                    {errors.expiryYear && (
                        <p className="mt-1 text-sm text-red-600">{errors.expiryYear}</p>
                    )}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        PIN <span className="text-red-600">*</span>
                    </label>
                    <input
                        type="password"
                        value={pin}
                        onChange={(e) => {
                            const value = e.target.value.replace(/\D/g, '').slice(0, 4);
                            setPin(value);
                            setErrors((prev) => ({ ...prev, pin: '' }));
                        }}
                        placeholder="****"
                        maxLength={4}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.pin ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                        disabled={processing || loading}
                    />
                    {errors.pin && (
                        <p className="mt-1 text-sm text-red-600">{errors.pin}</p>
                    )}
                </div>
            </div>

            {/* Action Buttons */}
            <div className="flex gap-3 pt-4">
                <Button
                    onClick={onCancel}
                    variant="outline"
                    className="flex-1"
                    disabled={processing || loading}
                >
                    Cancel
                </Button>
                <Button
                    onClick={handleSubmit}
                    className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                    disabled={processing || loading}
                >
                    {processing || loading ? (
                        <>
                            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                            Processing...
                        </>
                    ) : (
                        <>
                            <CreditCard className="mr-2 h-5 w-5" />
                            Pay {formatCurrency(paymentAmount, currency)}
                        </>
                    )}
                </Button>
            </div>

            <p className="text-xs text-center text-[#706f6c] dark:text-[#A1A09A]">
                Funds settle directly into merchant's {currency} bank account
                {currency === 'ZIG' && ` • Rate: 1 USD = 26.35 ZIG`}
            </p>
        </div>
    );
}
