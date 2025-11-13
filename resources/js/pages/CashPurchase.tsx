import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CashPurchaseWizard from '@/components/CashPurchase/CashPurchaseWizard';

interface CashPurchaseProps {
    type: 'personal' | 'microbiz';
    language?: string;
}

export default function CashPurchase({ type, language = 'en' }: CashPurchaseProps) {
    return (
        <>
            <Head title={`Buy with Cash - ${type === 'personal' ? 'Personal Products' : 'MicroBiz Starter Pack'}`}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <CashPurchaseWizard purchaseType={type} language={language} />
            </div>
        </>
    );
}