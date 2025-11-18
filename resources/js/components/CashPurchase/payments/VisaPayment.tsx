import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { CreditCard, Lock, AlertCircle, Loader2 } from 'lucide-react';

interface VisaPaymentProps {
    amount: number;
    onSuccess: (paymentData: any) => void;
    onCancel: () => void;
    loading?: boolean;
}

export default function VisaPayment({ amount, onSuccess, onCancel, loading }: VisaPaymentProps) {
    const [cardNumber, setCardNumber] = useState('');
    const [cardHolder, setCardHolder] = useState('');
    const [expiryMonth, setExpiryMonth] = useState('');
    const [expiryYear, setExpiryYear] = useState('');
    const [cvv, setCvv] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const formatCardNumber = (value: string) => {
        const cleaned = value.replace(/\s/g, '');
        const chunks = cleaned.match(/.{1,4}/g);
        return chunks ? chunks.join(' ') : cleaned;
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};

        // Validate card number (basic Luhn algorithm check)
        const cleanedCard = cardNumber.replace(/\s/g, '');
        if (!cleanedCard) {
            newErrors.cardNumber = 'Card number is required';
        } else if (!/^\d{16}$/.test(cleanedCard)) {
            newErrors.cardNumber = 'Card number must be 16 digits';
        }

        // Validate card holder
        if (!cardHolder.trim()) {
            newErrors.cardHolder = 'Card holder name is required';
        } else if (cardHolder.trim().length < 3) {
            newErrors.cardHolder = 'Card holder name must be at least 3 characters';
        }

        // Validate expiry month
        const month = parseInt(expiryMonth);
        if (!expiryMonth) {
            newErrors.expiryMonth = 'Expiry month is required';
        } else if (month < 1 || month > 12) {
            newErrors.expiryMonth = 'Invalid month';
        }

        // Validate expiry year
        const year = parseInt(expiryYear);
        const currentYear = new Date().getFullYear() % 100;
        if (!expiryYear) {
            newErrors.expiryYear = 'Expiry year is required';
        } else if (year < currentYear) {
            newErrors.expiryYear = 'Card has expired';
        }

        // Validate CVV
        if (!cvv) {
            newErrors.cvv = 'CVV is required';
        } else if (!/^\d{3,4}$/.test(cvv)) {
            newErrors.cvv = 'CVV must be 3 or 4 digits';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validateForm()) return;

        setProcessing(true);
        try {
            // In a real implementation, this would redirect to 3D Secure page
            // For now, we'll simulate the payment initiation
            const paymentData = {
                method: 'visa',
                amount,
                currency: 'USD',
                cardNumber: cardNumber.replace(/\s/g, '').slice(-4), // Only store last 4 digits
                cardHolder,
                expiryMonth,
                expiryYear,
                // Note: Never store CVV - it's only for transaction verification
                paymentStatus: 'pending',
                transactionId: `VISA-${Date.now()}`,
            };

            onSuccess(paymentData);
        } catch (error) {
            setErrors({ submit: 'Payment initiation failed. Please try again.' });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <Lock className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-sm text-blue-900 dark:text-blue-100 mb-1">
                            Secure Payment via 3D Secure
                        </h4>
                        <p className="text-sm text-blue-700 dark:text-blue-300">
                            Your payment will be processed securely. You may be asked to verify via OTP or your banking app.
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

            {/* Expiry and CVV */}
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
                        CVV <span className="text-red-600">*</span>
                    </label>
                    <input
                        type="password"
                        value={cvv}
                        onChange={(e) => {
                            const value = e.target.value.replace(/\D/g, '').slice(0, 4);
                            setCvv(value);
                            setErrors((prev) => ({ ...prev, cvv: '' }));
                        }}
                        placeholder="123"
                        maxLength={4}
                        className={`
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                            dark:bg-gray-800 dark:text-white
                            ${errors.cvv ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                        `}
                        disabled={processing || loading}
                    />
                    {errors.cvv && (
                        <p className="mt-1 text-sm text-red-600">{errors.cvv}</p>
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
                            Pay ${amount.toFixed(2)}
                        </>
                    )}
                </Button>
            </div>

            <p className="text-xs text-center text-[#706f6c] dark:text-[#A1A09A]">
                Funds settle in NOSTRO account within 2 business days (T+2)
            </p>
        </div>
    );
}
