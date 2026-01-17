import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, Calendar } from 'lucide-react';

interface CreditTermSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
}

const CreditTermSelection: React.FC<CreditTermSelectionProps> = ({ data, onNext, onBack }) => {
    const [selectedTermMonths, setSelectedTermMonths] = useState<number | null>(data.creditTerm || null);
    const [finalAmount, setFinalAmount] = useState<number>(0);

    const isZiG = data.currency === 'ZiG';
    const currencySymbol = isZiG ? 'ZiG' : '$';
    // const ZIG_RATE = 13.5; // Should ideally come from props or global constant

    const formatCurrency = (amount: number) => {
        return `${currencySymbol}${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    // Generate months from 3 to 24
    const creditTerms = Array.from({ length: 22 }, (_, i) => ({ months: i + 3 }));

    useEffect(() => {
        // Calculate base amount from selected product/scale
        let amount = 0;

        // Priority 1: Use explicitly set amount (e.g. from Core House flow)
        if (data.amount) {
            amount = data.amount;
        }
        // Priority 2: Use cart total if valid cart exists
        else if (data.cart && data.cart.length > 0) {
            amount = data.cart.reduce((sum: number, item: any) => sum + (item.price * item.quantity), 0);
        }
        // Priority 3: Fallback to selected business/scale logic
        else if (data.selectedScale) {
            if (data.selectedScale.custom_price) {
                amount = data.selectedScale.custom_price;
            } else if (data.selectedBusiness) {
                amount = data.selectedBusiness.basePrice * data.selectedScale.multiplier;
            }
        } else if (data.selectedBusiness) {
            // Fallback for single-price items like Company Reg if passed without scale
            amount = data.selectedBusiness.basePrice || 195.00;
        }

        setFinalAmount(amount);
    }, [data]);

    const handleContinue = () => {
        if (selectedTermMonths) {
            // Calculate final loan values to pass forward if needed, 
            // or just pass the term and let next steps handle it.
            // But we should probably calculate the monthly repayment for display/storage.
            const grossLoan = finalAmount * 1.06; // Assuming standard markup
            const interestRate = 0.96; // 96% annual
            const monthlyInterestRate = interestRate / 12;
            const monthlyPayment = grossLoan > 0
                ? (grossLoan * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                : 0;

            onNext({
                creditTerm: selectedTermMonths,
                monthlyPayment: parseFloat(monthlyPayment.toFixed(2)),
                loanAmount: grossLoan, // or net loan? Usually loanAmount = gross loan
                amount: finalAmount // Base amount
            });
        }
    };

    return (
        <div className="space-y-6 max-w-4xl mx-auto">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Select Loan Duration</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Choose a repayment period that suits you
                </p>
            </div>

            {/* Loan Duration Dropdown/Grid */}
            <div className="max-w-md mx-auto">
                <label htmlFor="loan-duration" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select Duration
                </label>
                <select
                    id="loan-duration"
                    value={selectedTermMonths || ''}
                    onChange={(e) => setSelectedTermMonths(Number(e.target.value))}
                    className="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                >
                    <option value="">Select duration</option>
                    {creditTerms.map((term) => (
                        <option key={term.months} value={term.months}>
                            {term.months} months
                        </option>
                    ))}
                </select>
            </div>

            {/* Loan Details Card */}
            {selectedTermMonths && (
                <div className="max-w-2xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <Card className="p-6 border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/10">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-emerald-600" />
                            Estimated Repayment
                        </h3>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Net Loan Amount</p>
                                <p className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                    {formatCurrency(finalAmount)}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Monthly Installment</p>
                                <p className="text-2xl font-bold text-blue-600">
                                    {(() => {
                                        const grossLoan = finalAmount * 1.06;
                                        const interestRate = 0.96;
                                        const monthlyInterestRate = interestRate / 12;
                                        const monthlyPayment = grossLoan > 0
                                            ? (grossLoan * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                                            (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                                            : 0;
                                        return formatCurrency(monthlyPayment);
                                    })()}
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>
            )}

            <div className="flex justify-between pt-4">
                <Button variant="outline" onClick={onBack} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
                <Button
                    onClick={handleContinue}
                    disabled={!selectedTermMonths}
                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
};

export default CreditTermSelection;
