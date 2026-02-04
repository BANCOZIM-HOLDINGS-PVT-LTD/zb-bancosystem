import { useState } from 'react';
import { ChevronRight, ChevronLeft, ShoppingBasket, MapPin, DollarSign, Tag, Truck, Monitor, GraduationCap, Building2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import type { CashPurchaseData } from '../CashPurchaseWizard';

interface SummaryStepProps {
    data: CashPurchaseData;
    onNext: (data: Partial<CashPurchaseData>) => void;
    onBack: () => void;
}

const ME_SYSTEM_PERCENTAGE = 0.10; // 10% of cash price
const TRAINING_PERCENTAGE = 0.055; // 5.5%

export default function SummaryStep({ data, onNext, onBack }: SummaryStepProps) {
    const cart = data.cart || [];
    const delivery = data.delivery!;
    const isMicroBiz = data.purchaseType === 'microbiz';

    const [includesMESystem, setIncludesMESystem] = useState<boolean>(data.includesMESystem || false);
    const [includesTraining, setIncludesTraining] = useState<boolean>(data.includesTraining || false);

    // Calculate totals
    const cartTotal = cart.reduce((sum, item) => sum + (item.cashPrice * item.quantity), 0);
    const loanTotal = cart.reduce((sum, item) => sum + (item.loanPrice * item.quantity), 0);

    // Calculates fees based on total logic
    const deliveryFee = 0; // Delivery is free for depot collection
    const meSystemFee = includesMESystem ? (cartTotal * ME_SYSTEM_PERCENTAGE) : 0;
    const trainingFee = includesTraining ? (cartTotal * TRAINING_PERCENTAGE) : 0;
    const totalAmount = cartTotal + deliveryFee + meSystemFee + trainingFee;

    const formatCurrency = (amount: number) => {
        // Use currency from first item or wizard default, assuming consistent currency
        // Actually wizard handles currency, but here we just format
        return `$${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    const handleContinue = () => {
        onNext({
            includesMESystem,
            meSystemFee,
            includesTraining,
            trainingFee,
            delivery: {
                ...delivery,
                includesMESystem,
                includesTraining,
            },
        });
    };

    return (
        <div className="space-y-6 pb-24 sm:pb-8">
            <div>
                <h2 className="text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Review Your Order
                </h2>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    Please review your order details before proceeding to payment
                </p>
            </div>

            <div className="space-y-6">
                {/* Product Summary */}
                <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 sm:p-6">
                    <div className="flex items-start gap-3 sm:gap-4 mb-4">
                        <ShoppingBasket className="h-5 w-5 sm:h-6 sm:w-6 text-emerald-600 flex-shrink-0 mt-1" />
                        <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-base sm:text-lg mb-1 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Shopping Basket Items ({cart.length})
                            </h3>
                        </div>
                    </div>

                    <div className="space-y-4 ml-8 sm:ml-10">
                        {cart.map((item, index) => (
                            <div key={`${item.id}-${index}`} className="flex justify-between items-start border-b border-gray-200 dark:border-gray-700 pb-3 last:border-0 last:pb-0 gap-2">
                                <div className="min-w-0 flex-1">
                                    <div className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm sm:text-base truncate">{item.name}</div>
                                    <div className="text-xs sm:text-sm text-[#706f6c] dark:text-[#A1A09A]">{item.category}</div>
                                    <div className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                        Qty: {item.quantity} x {formatCurrency(item.cashPrice)}
                                    </div>
                                </div>
                                <div className="font-bold text-emerald-600 text-sm sm:text-base flex-shrink-0">
                                    {formatCurrency(item.cashPrice * item.quantity)}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ME System Option for MicroBiz */}
                {isMicroBiz && cartTotal > 0 && (
                    <>
                        <Card className="p-4 sm:p-6 bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
                            <div className="flex items-start gap-3 sm:gap-4">
                                <input
                                    type="checkbox"
                                    id="me-system-cash"
                                    checked={includesMESystem}
                                    onChange={(e) => setIncludesMESystem(e.target.checked)}
                                    className="mt-1 h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div className="flex-1 min-w-0">
                                    <label htmlFor="me-system-cash" className="font-semibold text-base sm:text-lg cursor-pointer flex items-center gap-2 flex-wrap">
                                        <Monitor className="h-5 w-5" />
                                        <span>Add M&E System</span>
                                    </label>
                                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Track your business performance, monitor inventory, and get insights.
                                    </p>
                                    <div className="mt-2 flex items-center gap-2 flex-wrap">
                                        <span className="text-lg sm:text-xl font-bold text-emerald-600">+${meSystemFee.toFixed(2)}</span>
                                        <span className="text-xs sm:text-sm text-gray-500">(10%)</span>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Training Option for MicroBiz */}
                        <Card className="p-4 sm:p-6 bg-purple-50 dark:bg-purple-950/20 border-purple-200 dark:border-purple-800">
                            <div className="flex items-start gap-3 sm:gap-4">
                                <input
                                    type="checkbox"
                                    id="training-cash"
                                    checked={includesTraining}
                                    onChange={(e) => setIncludesTraining(e.target.checked)}
                                    className="mt-1 h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <div className="flex-1 min-w-0">
                                    <label htmlFor="training-cash" className="font-semibold text-base sm:text-lg cursor-pointer flex items-center gap-2 flex-wrap">
                                        <GraduationCap className="h-5 w-5" />
                                        <span>Add Training</span>
                                    </label>
                                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Comprehensive technical and business management training.
                                    </p>
                                    <div className="mt-2 flex items-center gap-2 flex-wrap">
                                        <span className="text-lg sm:text-xl font-bold text-purple-600">+${trainingFee.toFixed(2)}</span>
                                        <span className="text-xs sm:text-sm text-gray-500">(5.5%)</span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </>
                )}

                {/* Delivery Summary */}
                <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 sm:p-6">
                    <div className="flex items-start gap-3 sm:gap-4 mb-4">
                        <Building2 className="h-5 w-5 sm:h-6 sm:w-6 text-emerald-600 flex-shrink-0 mt-1" />
                        <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-base sm:text-lg mb-1 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Delivery Details
                            </h3>
                        </div>
                    </div>

                    <div className="space-y-3 ml-8 sm:ml-10">
                        <div className="flex justify-between gap-2">
                            <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Method:</span>
                            <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm text-right">
                                {delivery.type}
                            </span>
                        </div>

                        {delivery.type === 'Zim Post Office' ? (
                            <>
                                <div className="flex justify-between gap-2">
                                    <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">City:</span>
                                    <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm text-right">{delivery.city}</span>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Branch:</span>
                                    <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm text-right">{delivery.depot}</span>
                                </div>
                            </>
                        ) : (
                            <div className="flex justify-between gap-2">
                                <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Depot:</span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm text-right">{delivery.depot}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Price Breakdown */}
                <div className="bg-emerald-50 dark:bg-emerald-950/20 rounded-lg p-4 sm:p-6 border-2 border-emerald-200 dark:border-emerald-800">
                    <div className="flex items-start gap-3 sm:gap-4 mb-4">
                        <DollarSign className="h-5 w-5 sm:h-6 sm:w-6 text-emerald-600 flex-shrink-0 mt-1" />
                        <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-base sm:text-lg mb-1 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Payment Summary
                            </h3>
                        </div>
                    </div>

                    <div className="space-y-3 ml-8 sm:ml-10">
                        <div className="flex justify-between gap-2">
                            <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Subtotal:</span>
                            <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm">{formatCurrency(cartTotal)}</span>
                        </div>

                        {includesMESystem && (
                            <div className="flex justify-between gap-2">
                                <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">M&E System:</span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm">{formatCurrency(meSystemFee)}</span>
                            </div>
                        )}

                        {includesTraining && (
                            <div className="flex justify-between gap-2">
                                <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Training:</span>
                                <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm">{formatCurrency(trainingFee)}</span>
                            </div>
                        )}

                        <div className="flex justify-between gap-2">
                            <span className="text-[#706f6c] dark:text-[#A1A09A] text-sm">Delivery Fee:</span>
                            <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] text-sm">
                                {deliveryFee > 0 ? formatCurrency(deliveryFee) : 'FREE'}
                            </span>
                        </div>

                        <div className="pt-3 border-t-2 border-emerald-300 dark:border-emerald-700">
                            <div className="flex justify-between items-center gap-2">
                                <span className="text-base sm:text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Total:</span>
                                <span className="text-xl sm:text-2xl font-bold text-emerald-600">{formatCurrency(totalAmount)}</span>
                            </div>
                        </div>

                        {/* Savings Indicator */}
                        <div className="pt-3 border-t border-emerald-200 dark:border-emerald-800">
                            <div className="flex items-center justify-between bg-green-100 dark:bg-green-900/30 px-3 py-2 rounded gap-2">
                                <span className="text-xs sm:text-sm text-green-700 dark:text-green-300 flex items-center gap-1 sm:gap-2">
                                    <Tag className="h-4 w-4 flex-shrink-0" />
                                    <span>Savings:</span>
                                </span>
                                <span className="text-xs sm:text-sm font-bold text-green-700 dark:text-green-300">
                                    {formatCurrency(loanTotal - cartTotal)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Important Notes */}
                <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 className="font-semibold text-sm mb-2 text-blue-900 dark:text-blue-100">
                        Important Notes:
                    </h4>
                    <ul className="text-xs sm:text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• Payment via Paynow to process</li>
                        <li>• Confirmation number after payment</li>
                        <li>• Track delivery within 24 hours</li>
                        <li>• Ready in 2-3 business days</li>
                        {includesMESystem && (
                            <li>• M&E access after confirmation</li>
                        )}
                        {includesTraining && (
                            <li>• Training details within 7 days</li>
                        )}
                    </ul>
                </div>
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 mb-32">
                <Button onClick={onBack} variant="outline" size="lg">
                    <ChevronLeft className="mr-2 h-5 w-5" />
                    Back
                </Button>
                <Button onClick={handleContinue} size="lg" className="bg-emerald-600 hover:bg-emerald-700">
                    Payment
                    <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
            </div>
        </div>
    );
}

