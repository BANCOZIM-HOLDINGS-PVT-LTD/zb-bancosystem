import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CreditCard, Smartphone, Wallet, Check, Loader2, AlertCircle, DollarSign } from 'lucide-react';

interface DepositPaymentStepProps {
    data: any;
    onNext: (paymentData: any) => void;
    onBack: () => void;
    loading?: boolean;
}

type PaymentMethod = 'ecocash' | 'onemoney' | 'card';

const DepositPaymentStep: React.FC<DepositPaymentStepProps> = ({ data, onNext, onBack, loading }) => {
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(null);
    const [paymentInitiated, setPaymentInitiated] = useState(false);
    const [paymentError, setPaymentError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    // Calculate 25% deposit
    const loanAmount = data.amount || data.loanAmount || 0;
    const depositAmount = (loanAmount * 0.25).toFixed(2);

    const handleInitiatePayment = async () => {
        if (!paymentMethod) return;

        setProcessing(true);
        setPaymentError(null);

        try {
            // Simulate payment initiation
            // In production, this would call your Paynow API
            await new Promise(resolve => setTimeout(resolve, 1500));

            setPaymentInitiated(true);

            // Auto-proceed after payment confirmation (in production, this would be triggered by Paynow webhook)
            setTimeout(() => {
                onNext({
                    depositPaid: true,
                    depositAmount: parseFloat(depositAmount),
                    paymentMethod: paymentMethod,
                    paymentStatus: 'paid',
                    // In production, you'd get these from Paynow
                    transactionId: `TXN${Date.now()}`,
                    paidAt: new Date().toISOString(),
                });
            }, 2000);
        } catch (error: any) {
            setPaymentError(error.message || 'Payment failed. Please try again.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">25% Deposit Payment</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Pay your deposit to proceed with your application
                </p>
            </div>

            {/* Deposit Amount Display */}
            <Card className="max-w-2xl mx-auto p-6 bg-emerald-50 dark:bg-emerald-950/20 border-2 border-emerald-200 dark:border-emerald-800">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <DollarSign className="h-8 w-8 text-emerald-600" />
                        <div>
                            <p className="text-sm text-gray-600 dark:text-gray-400">Deposit Amount (25%)</p>
                            <p className="text-3xl font-bold text-emerald-600">
                                ${parseFloat(depositAmount).toLocaleString()}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Remaining Balance</p>
                        <p className="text-xl font-semibold text-gray-700 dark:text-gray-300">
                            ${((loanAmount * 0.75).toFixed(2))}
                        </p>
                        <p className="text-xs text-gray-500">Financed over {data.creditTerm} months</p>
                    </div>
                </div>
            </Card>

            {!paymentInitiated ? (
                <div className="max-w-2xl mx-auto space-y-6">
                    {/* Payment Method Selection */}
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
                                disabled={processing || paymentInitiated}
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
                                disabled={processing || paymentInitiated}
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
                                disabled={processing || paymentInitiated}
                            >
                                <div className="flex items-center gap-3">
                                    <CreditCard className={`h-6 w-6 ${paymentMethod === 'card' ? 'text-emerald-600' : 'text-gray-400'}`} />
                                    <div className="flex-1">
                                        <h5 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Debit/Credit Card</h5>
                                        <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                            Pay with Visa, Mastercard, or other cards
                                        </p>
                                    </div>
                                    {paymentMethod === 'card' && (
                                        <Check className="h-5 w-5 text-emerald-600" />
                                    )}
                                </div>
                            </button>
                        </div>
                    </div>

                    {/* Payment Button */}
                    {paymentMethod && (
                        <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <p className="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                All payments are processed securely via Paynow gateway
                            </p>
                            <Button
                                onClick={handleInitiatePayment}
                                disabled={processing}
                                className="w-full bg-emerald-600 hover:bg-emerald-700"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                        Processing Payment...
                                    </>
                                ) : (
                                    <>
                                        <CreditCard className="mr-2 h-5 w-5" />
                                        Pay ${parseFloat(depositAmount).toLocaleString()} Deposit
                                    </>
                                )}
                            </Button>
                        </div>
                    )}

                    {/* Error Display */}
                    {paymentError && (
                        <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div className="flex gap-3">
                                <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0" />
                                <div>
                                    <p className="font-semibold text-red-900 dark:text-red-100">Payment Failed</p>
                                    <p className="text-sm text-red-700 dark:text-red-300 mt-1">{paymentError}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                <div className="max-w-2xl mx-auto">
                    <Card className="p-8 text-center bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800">
                        <div className="flex justify-center mb-4">
                            <div className="bg-green-100 dark:bg-green-900 p-4 rounded-full">
                                <Check className="h-12 w-12 text-green-600" />
                            </div>
                        </div>
                        <h3 className="text-xl font-bold mb-2 text-green-900 dark:text-green-100">
                            Payment Successful!
                        </h3>
                        <p className="text-green-700 dark:text-green-300 mb-4">
                            Your deposit payment of ${parseFloat(depositAmount).toLocaleString()} has been received.
                        </p>
                        <div className="flex items-center justify-center gap-2 text-sm text-green-600">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            <span>Proceeding to application form...</span>
                        </div>
                    </Card>
                </div>
            )}

            {/* Information */}
            <div className="max-w-2xl mx-auto">
                <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 className="font-semibold text-sm mb-2 text-blue-900 dark:text-blue-100">
                        What happens next?
                    </h4>
                    <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• Complete the application form with your personal details</li>
                        <li>• Upload required documents for verification</li>
                        <li>• Your application will be reviewed within 24-48 hours</li>
                        <li>• Upon approval, your product will be dispatched to the selected depot</li>
                        <li>• The remaining balance (${((loanAmount * 0.75).toFixed(2))}) will be paid in {data.creditTerm} monthly installments</li>
                    </ul>
                </div>
            </div>

            {/* Navigation */}
            {!paymentInitiated && (
                <div className="flex justify-between pt-4 max-w-2xl mx-auto">
                    <Button
                        variant="outline"
                        onClick={onBack}
                        disabled={processing}
                        className="flex items-center gap-2"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back
                    </Button>
                </div>
            )}
        </div>
    );
};

export default DepositPaymentStep;