import React, { useState } from 'react';
import axios from 'axios';
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
    const [processing, setProcessing] = useState(false);
    const [paymentInitiated, setPaymentInitiated] = useState(false);
    const [paymentError, setPaymentError] = useState<string | null>(null);
    // Removed paymentMethod state as it's now just Paynow

    // Calculate deposit based on selected credit type (30% or 50%)
    const loanAmount = data.amount || data.loanAmount || 0;
    const depositPercent = data.creditType === 'PDC50' ? 0.50 : 0.30;
    const depositPercentLabel = data.creditType === 'PDC50' ? '50%' : '30%';
    const remainingPercent = data.creditType === 'PDC50' ? 0.50 : 0.70;
    const depositAmount = (loanAmount * depositPercent).toFixed(2);

    const handleInitiatePayment = async () => {
        setProcessing(true);
        setPaymentError(null);

        try {
            // Initiate payment via backend
            const response = await axios.post('/api/loan-deposits/initiate', {
                amount: parseFloat(depositAmount),
                email: data.personalDetails?.email, // Make sure we have email or fallback
                phone: data.personalDetails?.phone,
                loanAmount: loanAmount,
                purchaseType: 'loan_deposit'
            });

            if (response.data.success && response.data.redirectUrl) {
                // Redirect to Paynow
                window.location.href = response.data.redirectUrl;
                setPaymentInitiated(true);
            } else {
                setPaymentError('Failed to generate payment link.');
            }

        } catch (error: any) {
            console.error('Payment initiation error:', error);
            setPaymentError(error.response?.data?.message || error.message || 'Payment initiation failed.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">{depositPercentLabel} Deposit Payment</h2>
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
                            <p className="text-sm text-gray-600 dark:text-gray-400">Deposit Amount ({depositPercentLabel})</p>
                            <p className="text-3xl font-bold text-emerald-600">
                                ${parseFloat(depositAmount).toLocaleString()}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Remaining Balance</p>
                        <p className="text-xl font-semibold text-gray-700 dark:text-gray-300">
                            ${((loanAmount * remainingPercent).toFixed(2))}
                        </p>
                        <p className="text-xs text-gray-500">Financed over {data.creditTerm} months</p>
                    </div>
                </div>
            </Card>

            {!paymentInitiated ? (
                <div className="max-w-2xl mx-auto space-y-6">
                    {/* Payment Method Selection */}
                    {/* Payment Method Selection */}
                    <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h4 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC] mb-4 text-center">
                            Select Payment Method
                        </h4>
                        <div className="grid grid-cols-2 gap-4 mb-6">
                            {/* EcoCash */}
                            <button
                                onClick={handleInitiatePayment}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-blue-600 text-white font-bold p-2 rounded mb-2 w-full text-center">EcoCash</div>
                                <span className="text-sm font-medium">EcoCash</span>
                            </button>

                            {/* OneMoney */}
                            <button
                                onClick={handleInitiatePayment}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-orange-500 text-white font-bold p-2 rounded mb-2 w-full text-center">OneMoney</div>
                                <span className="text-sm font-medium">OneMoney</span>
                            </button>

                            {/* Visa */}
                            <button
                                onClick={handleInitiatePayment}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-blue-900 text-white font-bold p-2 rounded mb-2 w-full text-center">VISA</div>
                                <span className="text-sm font-medium">Visa</span>
                            </button>

                            {/* Mastercard */}
                            <button
                                onClick={handleInitiatePayment}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-red-600 text-white font-bold p-2 rounded mb-2 w-full text-center">Mastercard</div>
                                <span className="text-sm font-medium">Mastercard</span>
                            </button>
                        </div>

                        {processing && (
                            <div className="flex justify-center items-center gap-2 mb-4 text-emerald-600">
                                <Loader2 className="h-5 w-5 animate-spin" />
                                <span>Processing Payment...</span>
                            </div>
                        )}

                        <p className="text-xs text-center text-gray-500 dark:text-gray-400">
                            Security provided by Paynow. You will be redirected to complete payment.
                        </p>
                    </div>



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
                        <li>• The remaining balance (${((loanAmount * remainingPercent).toFixed(2))}) will be paid in {data.creditTerm} monthly installments</li>
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