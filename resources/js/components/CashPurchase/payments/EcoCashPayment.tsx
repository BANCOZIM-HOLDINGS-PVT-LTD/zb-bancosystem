import { useState, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Smartphone, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';
import { convertUSDtoZIG, formatCurrency } from '@/utils/currency';

interface EcoCashPaymentProps {
    amount: number;
    email?: string; // Added email prop
    onSuccess: (paymentData: any) => void;
    onCancel: () => void;
    loading?: boolean;
}

export default function EcoCashPayment({ amount, email, onSuccess, onCancel, loading }: EcoCashPaymentProps) {
    const [mobileNumber, setMobileNumber] = useState('');
    const [currency, setCurrency] = useState<'USD' | 'ZIG'>('USD');
    const [paymentInitiated, setPaymentInitiated] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Calculate amount in selected currency
    const paymentAmount = useMemo(() => {
        return currency === 'ZIG' ? convertUSDtoZIG(amount) : amount;
    }, [amount, currency]);

    const validateForm = () => {
        const newErrors: Record<string, string> = {};

        // Validate mobile number (Zimbabwe format)
        if (!mobileNumber.trim()) {
            newErrors.mobileNumber = 'Mobile number is required';
        } else if (!/^\+263-7[0-9]{8}$/.test(mobileNumber.replace(/\s/g, ''))) {
            newErrors.mobileNumber = 'Invalid EcoCash number (e.g., +263-771234567)';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const [pollUrl, setPollUrl] = useState<string | null>(null);
    const [reference, setReference] = useState<string | null>(null);

    const handleInitiatePayment = async () => {
        if (!validateForm()) return;

        setProcessing(true);
        setErrors({});

        try {
            const response = await fetch('/api/paynow/mobile-initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    phone: mobileNumber,
                    amount: paymentAmount,
                    method: 'ecocash',
                    email: email || 'customer@example.com', // Fallback email
                }),
            });

            const data = await response.json();

            if (data.success) {
                setPollUrl(data.poll_url);
                setReference(data.reference);
                setPaymentInitiated(true);
            } else {
                setErrors({ submit: data.message || 'Failed to initiate payment.' });
            }
        } catch (error) {
            setErrors({ submit: 'Network error. Please try again.' });
            console.error(error);
        } finally {
            setProcessing(false);
        }
    };

    const handleConfirmPayment = async () => {
        if (!pollUrl) return;

        setProcessing(true);
        try {
            const response = await fetch('/api/paynow/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ poll_url: pollUrl }),
            });

            const data = await response.json();

            if (data.success && data.is_paid) {
                const paymentData = {
                    method: 'ecocash',
                    amount: paymentAmount,
                    currency,
                    mobileNumber,
                    paymentStatus: 'success',
                    transactionId: reference,
                    paynowReference: data.paynow_reference,
                };
                onSuccess(paymentData);
            } else {
                setErrors({ submit: 'Payment not yet confirmed. Please perform the transaction on your phone and try again.' });
            }
        } catch (error) {
            setErrors({ submit: 'Verification failed. Please check connection.' });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <Smartphone className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-sm text-green-900 dark:text-green-100 mb-1">
                            EcoCash Mobile Wallet
                        </h4>
                        <p className="text-sm text-green-700 dark:text-green-300">
                            You'll receive a prompt on your mobile device to authorize this payment.
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

            {!paymentInitiated ? (
                <>
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

                    {/* Mobile Number */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            EcoCash Mobile Number <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="tel"
                            value={mobileNumber}
                            onChange={(e) => {
                                setMobileNumber(e.target.value);
                                setErrors((prev) => ({ ...prev, mobileNumber: '' }));
                            }}
                            placeholder="+263-771234567"
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white
                                ${errors.mobileNumber ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={processing || loading}
                        />
                        {errors.mobileNumber && (
                            <p className="mt-1 text-sm text-red-600">{errors.mobileNumber}</p>
                        )}
                        <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Make sure this is the number registered with your EcoCash account
                        </p>
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
                            onClick={handleInitiatePayment}
                            className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                            disabled={processing || loading}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                    Initiating...
                                </>
                            ) : (
                                <>
                                    <Smartphone className="mr-2 h-5 w-5" />
                                    Initiate Payment
                                </>
                            )}
                        </Button>
                    </div>
                </>
            ) : (
                <>
                    <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                            <Smartphone className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5 animate-pulse" />
                            <div>
                                <h4 className="font-semibold text-sm text-blue-900 dark:text-blue-100 mb-1">
                                    Check Your Phone
                                </h4>
                                <p className="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                    A payment prompt has been sent to <strong>{mobileNumber}</strong>
                                </p>
                                <ol className="text-sm text-blue-700 dark:text-blue-300 space-y-1 ml-4">
                                    <li className="list-decimal">Open the EcoCash prompt on your phone</li>
                                    <li className="list-decimal">Verify the amount: <strong>{formatCurrency(paymentAmount, currency)}</strong></li>
                                    <li className="list-decimal">Enter your EcoCash PIN</li>
                                    <li className="list-decimal">Confirm the transaction</li>
                                    <li className="list-decimal">Click "I've Authorized Payment" below</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            onClick={() => setPaymentInitiated(false)}
                            variant="outline"
                            className="flex-1"
                            disabled={processing || loading}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleConfirmPayment}
                            className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                            disabled={processing || loading}
                        >
                            {processing || loading ? (
                                <>
                                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                    Confirming...
                                </>
                            ) : (
                                <>
                                    <CheckCircle className="mr-2 h-5 w-5" />
                                    I've Authorized Payment
                                </>
                            )}
                        </Button>
                    </div>
                </>
            )}

            <p className="text-xs text-center text-[#706f6c] dark:text-[#A1A09A]">
                Instant processing • Funds settle to merchant {currency} account
                {currency === 'ZIG' && ` • Rate: 1 USD = 26.35 ZIG`}
            </p>
        </div>
    );
}
