import { useState, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { MessageSquare, CheckCircle, AlertCircle, Loader2, RefreshCw } from 'lucide-react';
import { convertUSDtoZIG, formatCurrency } from '@/utils/currency';

interface OmariPaymentProps {
    amount: number;
    onSuccess: (paymentData: any) => void;
    onCancel: () => void;
    loading?: boolean;
}

export default function OmariPayment({ amount, onSuccess, onCancel, loading }: OmariPaymentProps) {
    const [mobileNumber, setMobileNumber] = useState('');
    const [currency, setCurrency] = useState<'USD' | 'ZIG'>('USD');
    const [otpSent, setOtpSent] = useState(false);
    const [otpCode, setOtpCode] = useState('');
    const [processing, setProcessing] = useState(false);
    const [resendCooldown, setResendCooldown] = useState(0);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Calculate amount in selected currency
    const paymentAmount = useMemo(() => {
        return currency === 'ZIG' ? convertUSDtoZIG(amount) : amount;
    }, [amount, currency]);

    const validateMobileNumber = () => {
        const newErrors: Record<string, string> = {};

        if (!mobileNumber.trim()) {
            newErrors.mobileNumber = 'Mobile number is required';
        } else if (!/^\+263-7[0-9]{8}$/.test(mobileNumber.replace(/\s/g, ''))) {
            newErrors.mobileNumber = 'Invalid Zimbabwe mobile number (e.g., +263-771234567)';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const validateOtp = () => {
        const newErrors: Record<string, string> = {};

        if (!otpCode.trim()) {
            newErrors.otpCode = 'OTP code is required';
        } else if (!/^\d{6}$/.test(otpCode)) {
            newErrors.otpCode = 'OTP must be 6 digits';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSendOtp = async () => {
        if (!validateMobileNumber()) return;

        setProcessing(true);
        try {
            // Simulate API call to send OTP
            await new Promise((resolve) => setTimeout(resolve, 1500));

            setOtpSent(true);
            setResendCooldown(60); // 60 seconds cooldown

            // Start countdown
            const interval = setInterval(() => {
                setResendCooldown((prev) => {
                    if (prev <= 1) {
                        clearInterval(interval);
                        return 0;
                    }
                    return prev - 1;
                });
            }, 1000);

            setProcessing(false);
        } catch (error) {
            setErrors({ submit: 'Failed to send OTP. Please try again.' });
            setProcessing(false);
        }
    };

    const handleResendOtp = () => {
        setOtpCode('');
        handleSendOtp();
    };

    const handleConfirmPayment = async () => {
        if (!validateOtp()) return;

        setProcessing(true);
        try {
            // In production, verify OTP with backend
            const paymentData = {
                method: 'omari',
                amount: paymentAmount,
                currency,
                mobileNumber,
                otpCode,
                paymentStatus: 'pending',
                transactionId: `OMARI-${Date.now()}`,
            };

            onSuccess(paymentData);
        } catch (error) {
            setErrors({ submit: 'Payment confirmation failed. Please verify your OTP and try again.' });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <MessageSquare className="h-5 w-5 text-indigo-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-sm text-indigo-900 dark:text-indigo-100 mb-1">
                            Omari Payment with OTP
                        </h4>
                        <p className="text-sm text-indigo-700 dark:text-indigo-300">
                            Verify your payment using a One-Time Password sent to your mobile number.
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

            {!otpSent ? (
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
                            Mobile Number <span className="text-red-600">*</span>
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
                            An OTP will be sent to this number
                        </p>
                    </div>

                    {/* Payment Amount */}
                    <div className="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                Payment Amount:
                            </span>
                            <span className="text-xl font-bold text-emerald-600">
                                {formatCurrency(paymentAmount, currency)}
                            </span>
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
                            onClick={handleSendOtp}
                            className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                            disabled={processing || loading}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                    Sending OTP...
                                </>
                            ) : (
                                <>
                                    <MessageSquare className="mr-2 h-5 w-5" />
                                    Send OTP
                                </>
                            )}
                        </Button>
                    </div>
                </>
            ) : (
                <>
                    <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                            <MessageSquare className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <h4 className="font-semibold text-sm text-blue-900 dark:text-blue-100 mb-1">
                                    OTP Sent!
                                </h4>
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    A 6-digit code has been sent to <strong>{mobileNumber}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* OTP Input */}
                    <div>
                        <label className="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            Enter OTP Code <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            value={otpCode}
                            onChange={(e) => {
                                const value = e.target.value.replace(/\D/g, '').slice(0, 6);
                                setOtpCode(value);
                                setErrors((prev) => ({ ...prev, otpCode: '' }));
                            }}
                            placeholder="123456"
                            maxLength={6}
                            className={`
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500
                                dark:bg-gray-800 dark:text-white text-center text-2xl tracking-widest font-mono
                                ${errors.otpCode ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}
                            `}
                            disabled={processing || loading}
                            autoFocus
                        />
                        {errors.otpCode && (
                            <p className="mt-1 text-sm text-red-600">{errors.otpCode}</p>
                        )}
                    </div>

                    {/* Resend OTP */}
                    <div className="text-center">
                        {resendCooldown > 0 ? (
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Resend OTP in <strong>{resendCooldown}s</strong>
                            </p>
                        ) : (
                            <Button
                                onClick={handleResendOtp}
                                variant="link"
                                size="sm"
                                className="text-emerald-600 hover:text-emerald-700"
                                disabled={processing || loading}
                            >
                                <RefreshCw className="mr-2 h-4 w-4" />
                                Resend OTP
                            </Button>
                        )}
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            onClick={() => {
                                setOtpSent(false);
                                setOtpCode('');
                                setResendCooldown(0);
                            }}
                            variant="outline"
                            className="flex-1"
                            disabled={processing || loading}
                        >
                            Back
                        </Button>
                        <Button
                            onClick={handleConfirmPayment}
                            className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                            disabled={processing || loading || otpCode.length !== 6}
                        >
                            {processing || loading ? (
                                <>
                                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                    Verifying...
                                </>
                            ) : (
                                <>
                                    <CheckCircle className="mr-2 h-5 w-5" />
                                    Verify & Pay
                                </>
                            )}
                        </Button>
                    </div>
                </>
            )}

            <p className="text-xs text-center text-[#706f6c] dark:text-[#A1A09A]">
                Funds settle to merchant {currency} account
                {currency === 'ZIG' && ` • Rate: 1 USD = 26.35 ZIG`}
            </p>
        </div>
    );
}
