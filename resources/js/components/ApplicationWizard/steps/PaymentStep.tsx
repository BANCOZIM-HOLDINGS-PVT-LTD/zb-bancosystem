import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CreditCard, Smartphone, Wallet, Check, Loader2, AlertCircle, DollarSign } from 'lucide-react';

interface PaymentStepProps {
    data: any;
    onNext: (paymentData: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const PaymentStep: React.FC<PaymentStepProps> = ({ data, onNext, onBack, loading }) => {
    const [processing, setProcessing] = useState(false);
    const [paymentInitiated, setPaymentInitiated] = useState(false);
    const [paymentError, setPaymentError] = useState<string | null>(null);
    const [pollInterval, setPollInterval] = useState<NodeJS.Timeout | null>(null);

    const totalAmount = data.finalPrice || data.amount || 0;
    const referenceCode = data.referenceCode;

    // Cleanup polling on unmount
    useEffect(() => {
        return () => {
            if (pollInterval) clearInterval(pollInterval);
        };
    }, [pollInterval]);

    const handleInitiatePayment = async (method: string) => {
        setProcessing(true);
        setPaymentError(null);

        try {
            // Reusing the deposit initiation logic but it will handle full amount if the app is marked as 'cash'
            // We'll need to ensure the backend supports this or create a new endpoint.
            // For now, let's assume we'll update the backend to handle cash applications in this endpoint.
            const response = await axios.post('/api/deposit/initiate', {
                reference_code: referenceCode,
                payment_method: method,
                payment_type: data.paymentType || 'cash'
            });

            if (response.data.success && response.data.data.redirect_url) {
                // For card/mastercard, redirect. For mobile, we can poll or redirect.
                // Best to redirect to Paynow's gateway for all for simplicity.
                window.open(response.data.data.redirect_url, '_blank');
                setPaymentInitiated(true);
                
                // Start polling for status
                const interval = setInterval(async () => {
                    try {
                        const statusRes = await axios.get(`/api/deposit/status/${referenceCode}`);
                        if (statusRes.data.success && statusRes.data.data.deposit_paid) {
                            clearInterval(interval);
                            setProcessing(false);
                            onNext({ paymentStatus: 'paid', depositPaid: true });
                        }
                    } catch (err) {
                        console.error("Polling error", err);
                    }
                }, 5000);
                setPollInterval(interval);

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
                <h2 className="text-2xl font-semibold mb-2">Complete Your Payment</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Pay in full to finalize your order and initiate delivery
                </p>
            </div>

            <Card className="max-w-2xl mx-auto p-6 bg-emerald-50 dark:bg-emerald-950/20 border-2 border-emerald-200 dark:border-emerald-800">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <DollarSign className="h-8 w-8 text-emerald-600" />
                        <div>
                            <p className="text-sm text-gray-600 dark:text-gray-400">Total Amount Due</p>
                            <p className="text-3xl font-bold text-emerald-600">
                                ${parseFloat(totalAmount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Order Reference</p>
                        <p className="text-lg font-mono font-bold text-gray-700 dark:text-gray-300">
                            {referenceCode}
                        </p>
                    </div>
                </div>
            </Card>

            {!paymentInitiated ? (
                <div className="max-w-2xl mx-auto space-y-6">
                    <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h4 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC] mb-4 text-center">
                            Select Payment Method
                        </h4>
                        <div className="grid grid-cols-2 gap-4 mb-6">
                            <button
                                onClick={() => handleInitiatePayment('ecocash')}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-blue-600 text-white font-bold p-2 rounded mb-2 w-full text-center">EcoCash</div>
                                <span className="text-sm font-medium">EcoCash / Mobile</span>
                            </button>

                            <button
                                onClick={() => handleInitiatePayment('card')}
                                disabled={processing}
                                className="flex flex-col items-center justify-center p-4 border rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all border-gray-200 dark:border-gray-700"
                            >
                                <div className="bg-blue-900 text-white font-bold p-2 rounded mb-2 w-full text-center">VISA / Master</div>
                                <span className="text-sm font-medium">Bank Card</span>
                            </button>
                        </div>

                        {processing && (
                            <div className="flex justify-center items-center gap-2 mb-4 text-emerald-600">
                                <Loader2 className="h-5 w-5 animate-spin" />
                                <span>Waiting for payment...</span>
                            </div>
                        )}

                        <p className="text-xs text-center text-gray-500 dark:text-gray-400">
                            Security provided by Paynow. Payment is secure and encrypted.
                        </p>
                    </div>

                    {paymentError && (
                        <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div className="flex gap-3">
                                <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0" />
                                <div>
                                    <p className="font-semibold text-red-900 dark:text-red-100">Payment Error</p>
                                    <p className="text-sm text-red-700 dark:text-red-300 mt-1">{paymentError}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                <div className="max-w-2xl mx-auto text-center space-y-6">
                    <Card className="p-8 bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
                        <Loader2 className="h-12 w-12 text-blue-600 animate-spin mx-auto mb-4" />
                        <h3 className="text-xl font-bold mb-2">Payment Window Opened</h3>
                        <p className="text-gray-600 dark:text-gray-400 mb-4">
                            Please complete the payment in the new window. Once finished, this page will automatically update.
                        </p>
                        <Button 
                            variant="link" 
                            onClick={() => setPaymentInitiated(false)}
                            className="text-blue-600"
                        >
                            Try another payment method
                        </Button>
                    </Card>
                </div>
            )}

            <div className="max-w-2xl mx-auto">
                <div className="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                    <h4 className="font-semibold text-sm mb-2 text-emerald-900 dark:text-emerald-100">
                        Express Delivery Benefits
                    </h4>
                    <ul className="text-sm text-emerald-700 dark:text-emerald-300 space-y-1">
                        <li>• Instant order processing upon payment</li>
                        <li>• Automatic delivery initiation to your address</li>
                        <li>• Real-time tracking from dispatch to delivery</li>
                        <li>• SMS notifications at every milestone</li>
                    </ul>
                </div>
            </div>

            {!paymentInitiated && (
                <div className="flex justify-between pt-4 max-w-2xl mx-auto">
                    <Button
                        variant="outline"
                        onClick={onBack}
                        disabled={processing}
                        className="flex items-center gap-2"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back to Summary
                    </Button>
                </div>
            )}
        </div>
    );
};

export default PaymentStep;
