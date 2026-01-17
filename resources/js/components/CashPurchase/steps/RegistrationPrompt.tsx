import React from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { UserPlus, ChevronLeft } from 'lucide-react';

interface RegistrationPromptProps {
    data: any;
    onBack: () => void;
}

const RegistrationPrompt: React.FC<RegistrationPromptProps> = ({ data, onBack }) => {
    const handleRegister = () => {
        // Save current cart and delivery data to localStorage
        const purchaseState = {
            cart: data.cart,
            delivery: data.delivery,
            purchaseType: data.purchaseType,
            language: data.language,
            payment: data.payment,
            returnUrl: window.location.pathname + window.location.search
        };
        localStorage.setItem('pendingCashPurchaseState', JSON.stringify(purchaseState));

        // Redirect to registration with return URL
        const returnUrl = encodeURIComponent('/cash-purchase');
        router.visit(`/client/register?returnUrl=${returnUrl}`);
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <UserPlus className="mx-auto h-16 w-16 text-emerald-600 mb-4" />
                <h2 className="text-2xl font-semibold mb-2">Register to Continue</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    To proceed with your purchase, please register your account
                </p>
            </div>

            <Card className="p-8 max-w-md mx-auto">
                <div className="space-y-4">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Creating an account will allow you to:
                    </p>
                    <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Complete your cash purchase</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Track your order delivery</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>View your purchase history</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Access receipts and invoices</span>
                        </li>
                    </ul>

                    <Button
                        onClick={handleRegister}
                        className="w-full bg-emerald-600 hover:bg-emerald-700"
                    >
                        Register Now
                    </Button>
                </div>
            </Card>

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={onBack}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
        </div>
    );
};

export default RegistrationPrompt;
