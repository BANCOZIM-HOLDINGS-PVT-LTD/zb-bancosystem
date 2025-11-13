import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CreditCard, Briefcase, FileText, Package, ChevronLeft, ChevronRight } from 'lucide-react';

interface IntentSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const intents = [
    {
        id: 'hirePurchase',
        name: 'Apply for Personal products',
        icon: CreditCard,
        description: 'Get credit for phones, furniture, appliances and more'
    },
    {
        id: 'microBiz',
        name: 'Apply for Small Business Starter Pack (MicroBiz)',
        icon: Briefcase,
        description: 'A jump start into the world of entrepreneurship'
    },
    {
        id: 'checkStatus',
        name: 'Check Application Status',
        icon: FileText,
        description: 'Track your existing application'
    },
    {
        id: 'trackDelivery',
        name: 'Track Delivery',
        icon: Package,
        description: 'Monitor your order delivery'
    }
];

const IntentSelection: React.FC<IntentSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const handleIntentSelect = (intent: string) => {
        onNext({ intent });
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">How can we help you today?</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Select what you would like to do
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                {intents.map((intent) => {
                    const Icon = intent.icon;
                    return (
                        <Card
                            key={intent.id}
                            className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg"
                            onClick={() => !loading && handleIntentSelect(intent.id)}
                        >
                            <div className="flex items-start space-x-4">
                                <Icon className="h-8 w-8 text-emerald-600 flex-shrink-0" />
                                <div className="flex-1">
                                    <h3 className="text-lg font-medium mb-1">{intent.name}</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        {intent.description}
                                    </p>
                                </div>
                                <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0" />
                            </div>
                        </Card>
                    );
                })}
            </div>

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={onBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
        </div>
    );
};

export default IntentSelection;
