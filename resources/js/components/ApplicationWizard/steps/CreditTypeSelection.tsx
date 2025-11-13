import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, CreditCard, Wallet, Check } from 'lucide-react';

interface CreditTypeSelectionProps {
    data: any;
    onNext: (creditType: 'ZDC' | 'PDC') => void;
    onBack: () => void;
    loading?: boolean;
}

type CreditType = 'ZDC' | 'PDC';

const CreditTypeSelection: React.FC<CreditTypeSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const [selectedType, setSelectedType] = React.useState<CreditType | null>(data.creditType || null);

    // Calculate 25% deposit for PDC
    const loanAmount = data.amount || data.loanAmount || 0;
    const depositAmount = (loanAmount * 0.25).toFixed(2);

    const handleContinue = () => {
        if (selectedType) {
            onNext(selectedType);
        }
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Select Credit Type</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Choose your preferred credit payment option
                </p>
                <div className="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    Loan Amount: <span className="font-bold">${loanAmount.toLocaleString()}</span>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                {/* ZDC - Zero Deposit Credit */}
                <Card
                    className={`
                        cursor-pointer p-6 transition-all border-2
                        ${selectedType === 'ZDC'
                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 shadow-lg scale-105'
                            : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400 hover:shadow-md'
                        }
                    `}
                    onClick={() => setSelectedType('ZDC')}
                >
                    <div className="flex flex-col items-center text-center space-y-4">
                        {selectedType === 'ZDC' && (
                            <div className="absolute top-4 right-4">
                                <div className="bg-emerald-600 text-white p-2 rounded-full">
                                    <Check className="h-5 w-5" />
                                </div>
                            </div>
                        )}

                        <div className={`p-4 rounded-full ${selectedType === 'ZDC' ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-gray-100 dark:bg-gray-800'}`}>
                            <CreditCard className={`h-12 w-12 ${selectedType === 'ZDC' ? 'text-emerald-600' : 'text-gray-500'}`} />
                        </div>

                        <div>
                            <h3 className="text-xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Zero Deposit Credit (ZDC)
                            </h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Start your purchase with no upfront payment
                            </p>
                        </div>

                        <div className="w-full space-y-3 text-left">
                            <div className="flex items-center gap-2 text-sm">
                                <Check className="h-4 w-4 text-emerald-600 flex-shrink-0" />
                                <span>No deposit required</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Check className="h-4 w-4 text-emerald-600 flex-shrink-0" />
                                <span>Full amount financed</span>
                            </div>
                        </div>

                        <div className="w-full pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="text-center">
                                <p className="text-xs text-gray-500 mb-1">Monthly Payment</p>
                                <p className="text-2xl font-bold text-emerald-600">
                                    ${data.monthlyPayment?.toLocaleString() || 'N/A'}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    for {data.creditTerm || 'N/A'} months
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* PDC - Paid Deposit Credit */}
                <Card
                    className={`
                        cursor-pointer p-6 transition-all border-2
                        ${selectedType === 'PDC'
                            ? 'border-blue-600 bg-blue-50 dark:bg-blue-950/20 shadow-lg scale-105'
                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-400 hover:shadow-md'
                        }
                    `}
                    onClick={() => setSelectedType('PDC')}
                >
                    <div className="flex flex-col items-center text-center space-y-4">
                        {selectedType === 'PDC' && (
                            <div className="absolute top-4 right-4">
                                <div className="bg-blue-600 text-white p-2 rounded-full">
                                    <Check className="h-5 w-5" />
                                </div>
                            </div>
                        )}

                        <div className={`p-4 rounded-full ${selectedType === 'PDC' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-gray-100 dark:bg-gray-800'}`}>
                            <Wallet className={`h-12 w-12 ${selectedType === 'PDC' ? 'text-blue-600' : 'text-gray-500'}`} />
                        </div>

                        <div>
                            <h3 className="text-xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Paid Deposit Credit (PDC)
                            </h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Pay 25% upfront and reduce your monthly payments
                            </p>
                        </div>

                        <div className="w-full space-y-3 text-left">
                            <div className="flex items-center gap-2 text-sm">
                                <Check className="h-4 w-4 text-blue-600 flex-shrink-0" />
                                <span>25% deposit payment required</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Check className="h-4 w-4 text-blue-600 flex-shrink-0" />
                                <span>Balance amount financed</span>
                            </div>
                        </div>

                        <div className="w-full space-y-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="text-center">
                                <p className="text-xs text-gray-500 mb-1">Deposit Payment</p>
                                <p className="text-2xl font-bold text-blue-600">
                                    ${parseFloat(depositAmount).toLocaleString()}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    25% of loan amount
                                </p>
                            </div>
                            <div className="text-center pt-2">
                                <p className="text-xs text-gray-500 mb-1">Monthly Payment</p>
                                <p className="text-lg font-bold text-blue-600">
                                    ${((loanAmount * 0.75) / (data.creditTerm || 12)).toFixed(2)}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    for {data.creditTerm || 'N/A'} months
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            {/* Important Information */}
            <div className="max-w-4xl mx-auto">
                <div className="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <h4 className="font-semibold text-sm mb-2 text-yellow-900 dark:text-yellow-100">
                        Important Information:
                    </h4>
                    <ul className="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                        <li>• <strong>ZDC:</strong> No upfront payment required. Proceed directly to application form.</li>
                        <li>• <strong>PDC:</strong> After selecting delivery details, you'll be redirected to make the 25% deposit payment via Paynow (EcoCash, OneMoney, or Card).</li>
                        <li>• <strong>PDC:</strong> Once payment is confirmed, you'll continue with the application form.</li>
                        <li>• Both options require verification and approval before delivery.</li>
                    </ul>
                </div>
            </div>

            {/* Navigation */}
            <div className="flex justify-between pt-4 max-w-4xl mx-auto">
                <Button
                    variant="outline"
                    onClick={onBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
                <Button
                    onClick={handleContinue}
                    disabled={!selectedType || loading}
                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
};

export default CreditTypeSelection;
