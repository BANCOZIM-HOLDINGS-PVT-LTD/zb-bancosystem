import { XCircle, RefreshCcw, Phone, Mail, MessageCircle, Home } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface CashPurchaseErrorProps {
    error?: {
        message: string;
        code?: string;
        details?: string;
    };
    type?: 'personal' | 'microbiz';
}

export default function CashPurchaseError({ error, type = 'personal' }: CashPurchaseErrorProps) {
    const defaultError = {
        message: 'We encountered an issue processing your purchase',
        code: 'PURCHASE_FAILED',
        details: 'Please try again or contact our support team for assistance.',
    };

    const errorInfo = error || defaultError;

    return (
        <>
            <Head title="Purchase Failed" />

            <div className="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
                <div className="max-w-2xl mx-auto">
                    {/* Error Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-20 h-20 bg-red-100 dark:bg-red-900 rounded-full mb-4">
                            <XCircle className="h-12 w-12 text-red-600 dark:text-red-400" />
                        </div>
                        <h1 className="text-3xl font-bold text-red-600 dark:text-red-400 mb-2">
                            Purchase Failed
                        </h1>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            We're sorry, but we couldn't complete your purchase
                        </p>
                    </div>

                    {/* Main Content Card */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 space-y-6">
                        {/* Error Details */}
                        <div className="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                            <h3 className="font-semibold text-lg mb-2 text-red-900 dark:text-red-100">
                                What Happened?
                            </h3>
                            <p className="text-red-700 dark:text-red-300 mb-4">
                                {errorInfo.message}
                            </p>
                            {errorInfo.details && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errorInfo.details}
                                </p>
                            )}
                            {errorInfo.code && (
                                <div className="mt-4 pt-4 border-t border-red-200 dark:border-red-800">
                                    <p className="text-xs text-red-500 dark:text-red-500">
                                        Error Code: <span className="font-mono">{errorInfo.code}</span>
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Common Reasons */}
                        <div>
                            <h3 className="font-semibold text-lg mb-3 text-[#1b1b18] dark:text-[#EDEDEC]">
                                Common Reasons for Failed Purchases:
                            </h3>
                            <ul className="space-y-2 text-[#706f6c] dark:text-[#A1A09A]">
                                <li className="flex items-start gap-2">
                                    <span className="text-red-500 mt-1">•</span>
                                    <span>Payment was not completed or was declined</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-500 mt-1">•</span>
                                    <span>Network connection was interrupted during submission</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-500 mt-1">•</span>
                                    <span>Invalid or incorrect payment transaction ID</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-500 mt-1">•</span>
                                    <span>System maintenance or temporary service unavailability</span>
                                </li>
                            </ul>
                        </div>

                        {/* What to Do Next */}
                        <div className="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                            <h3 className="font-semibold text-lg mb-3 text-blue-900 dark:text-blue-100">
                                What Should You Do?
                            </h3>
                            <ol className="space-y-2 text-blue-700 dark:text-blue-300">
                                <li className="flex items-start gap-2">
                                    <span className="font-semibold mt-0.5">1.</span>
                                    <span>If you made a payment, verify your transaction status on your mobile banking app</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="font-semibold mt-0.5">2.</span>
                                    <span>Try the purchase process again by clicking "Try Again" below</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="font-semibold mt-0.5">3.</span>
                                    <span>If the problem persists, contact our support team with your transaction details</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="font-semibold mt-0.5">4.</span>
                                    <span>Do not make duplicate payments - wait for confirmation or contact support first</span>
                                </li>
                            </ol>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link
                                href={`/cash-purchase?type=${type}`}
                                className="flex-1"
                            >
                                <Button
                                    size="lg"
                                    className="w-full bg-emerald-600 hover:bg-emerald-700"
                                >
                                    <RefreshCcw className="mr-2 h-5 w-5" />
                                    Try Again
                                </Button>
                            </Link>
                            <Link
                                href="/"
                                className="flex-1"
                            >
                                <Button
                                    variant="outline"
                                    size="lg"
                                    className="w-full"
                                >
                                    <Home className="mr-2 h-5 w-5" />
                                    Return to Home
                                </Button>
                            </Link>
                        </div>

                        {/* Support Information */}
                        <div className="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 className="font-semibold text-lg mb-4 text-[#1b1b18] dark:text-[#EDEDEC] text-center">
                                Need Help? Contact Us
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <a
                                    href="tel:+263123456789"
                                    className="flex flex-col items-center gap-2 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <Phone className="h-6 w-6 text-emerald-600" />
                                    <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Call Us
                                    </span>
                                    <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        +263 123 456 789
                                    </span>
                                </a>

                                <a
                                    href="mailto:support@bancoZim.com"
                                    className="flex flex-col items-center gap-2 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <Mail className="h-6 w-6 text-emerald-600" />
                                    <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Email Us
                                    </span>
                                    <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        support@bancoZim.com
                                    </span>
                                </a>

                                <a
                                    href="https://wa.me/263123456789"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex flex-col items-center gap-2 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <MessageCircle className="h-6 w-6 text-emerald-600" />
                                    <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        WhatsApp
                                    </span>
                                    <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        Chat with us
                                    </span>
                                </a>
                            </div>
                        </div>

                        {/* Additional Note */}
                        <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <p className="text-sm text-center text-[#706f6c] dark:text-[#A1A09A]">
                                <strong>Important:</strong> If money was deducted from your account but the purchase failed,
                                please contact our support team immediately with your transaction details.
                                We will resolve this within 24 hours.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}