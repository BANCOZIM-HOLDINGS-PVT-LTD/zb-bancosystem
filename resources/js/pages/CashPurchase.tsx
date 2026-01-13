import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CashPurchaseWizard from '@/components/CashPurchase/CashPurchaseWizard';

interface CashPurchaseProps {
    type: string;
    language?: string;
    currency?: string;
}

const getTitle = (type: string) => {
    switch (type) {
        case 'microBiz':
        case 'microbiz':
            return 'MicroBiz Starter Pack';
        case 'homeConstruction':
            return 'Home Construction';
        case 'personalServices':
            return 'Personal Services';
        case 'personalGadgets':
        case 'personal':
            return 'Personal Products';
        default:
            return 'Cash Purchase';
    }
};

export default function CashPurchase({ type, language = 'en', currency = 'USD' }: CashPurchaseProps) {
    return (
        <>
            <Head title={`Buy with Cash - ${getTitle(type)}`}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <CashPurchaseWizard purchaseType={type} language={language} currency={currency} />
            </div>
        </>
    );
}