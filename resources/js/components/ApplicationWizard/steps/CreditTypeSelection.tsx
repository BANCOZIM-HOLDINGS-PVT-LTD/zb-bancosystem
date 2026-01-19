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

    // Currency formatting based on selected currency
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';

    const formatCurrency = (amount: number) => {
        return `${currencySymbol}${amount.toLocaleString()}`;
    };

    // Calculate 25% deposit for PDC
    const loanAmount = data.amount || data.loanAmount || 0;
    const depositAmount = loanAmount * 0.25;

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
                    Select your preferred credit payment option
                </p>
                <div className="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    Loan Amount: <span className="font-bold">{formatCurrency(loanAmount)}</span>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl mx-auto">
                {/* ZDC - Zero Deposit Credit */}
                <Card
                    className={`
                        cursor-pointer p-4 transition-all border-2 relative
                        ${selectedType === 'ZDC'
                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 shadow-lg scale-105'
                            : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400 hover:shadow-md'
                        }
                    `}
                    onClick={() => setSelectedType('ZDC')}
                >
                    <div className="absolute top-4 right-4">
                        <div className={`
                            h-6 w-6 rounded border-2 flex items-center justify-center transition-colors
                            ${selectedType === 'ZDC'
                                ? 'bg-emerald-600 border-emerald-600'
                                : 'border-gray-300 bg-white'
                            }
                        `}>
                            {selectedType === 'ZDC' && <Check className="h-4 w-4 text-white" />}
                        </div>
                    </div>

                    <div className="flex flex-col items-center text-center space-y-4 pt-2">
                        <div className={`p-3 rounded-full ${selectedType === 'ZDC' ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-gray-100 dark:bg-gray-800'}`}>
                            <CreditCard className={`h-10 w-10 ${selectedType === 'ZDC' ? 'text-emerald-600' : 'text-gray-500'}`} />
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
                                    {formatCurrency(data.monthlyPayment || 0)}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    for {data.creditTerm || 'N/A'} months
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* DPC - Deposit Paid Credit */}
                <Card
                    className={`
                        cursor-pointer p-4 transition-all border-2 relative
                        ${selectedType === 'PDC'
                            ? 'border-blue-600 bg-blue-50 dark:bg-blue-950/20 shadow-lg scale-105'
                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-400 hover:shadow-md'
                        }
                    `}
                    onClick={() => setSelectedType('PDC')}
                >
                    <div className="absolute top-4 right-4">
                        <div className={`
                            h-6 w-6 rounded border-2 flex items-center justify-center transition-colors
                            ${selectedType === 'PDC'
                                ? 'bg-blue-600 border-blue-600'
                                : 'border-gray-300 bg-white'
                            }
                        `}>
                            {selectedType === 'PDC' && <Check className="h-4 w-4 text-white" />}
                        </div>
                    </div>

                    <div className="flex flex-col items-center text-center space-y-4 pt-2">
                        <div className={`p-3 rounded-full ${selectedType === 'PDC' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-gray-100 dark:bg-gray-800'}`}>
                            <Wallet className={`h-10 w-10 ${selectedType === 'PDC' ? 'text-blue-600' : 'text-gray-500'}`} />
                        </div>

                        <div>
                            <h3 className="text-xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Deposit Paid Credit (DPC)
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
                                    {formatCurrency(depositAmount)}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    25% of loan amount
                                </p>
                            </div>
                            <div className="text-center pt-2">
                                <p className="text-xs text-gray-500 mb-1">Monthly Payment</p>
                                <p className="text-lg font-bold text-blue-600">
                                    {formatCurrency((loanAmount * 0.75) / (data.creditTerm || 12))}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    for {data.creditTerm || 'N/A'} months
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            {/* PDC Note */}
            <div className="max-w-3xl mx-auto mt-4 p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p className="text-sm text-blue-700 dark:text-blue-300">
                    <span className="font-semibold">NB (PDC):</span> Please note that the deposit will only be required once the application has been successfully approved. This will be reported on the application status page.
                </p>
            </div>


            {/* Navigation */}
            <div className="flex justify-between pt-4 max-w-3xl mx-auto">
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
