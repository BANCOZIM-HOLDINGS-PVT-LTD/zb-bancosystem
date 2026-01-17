import { CheckCircle, Package, MapPin, DollarSign, User, Phone, Mail, CreditCard, Printer, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import Footer from '@/components/Footer';

interface CashPurchaseSuccessProps {
    purchase: {
        purchase_number: string;
        purchase_type: string;

        // Product
        product_name: string;
        category: string;
        cash_price: number;

        // Customer
        national_id: string;
        full_name: string;
        phone: string;
        email?: string;

        // Delivery
        delivery_type: string;
        depot_name?: string;
        region?: string;
        city?: string;
        delivery_address?: string;

        // Payment
        amount_paid: number;
        transaction_id?: string;
        payment_status: string;

        // Timestamps
        created_at: string;
    };
}

export default function CashPurchaseSuccess({ purchase }: CashPurchaseSuccessProps) {
    const formatCurrency = (amount: number) => {
        return `$${amount.toLocaleString()}`;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <>
            <Head title="Purchase Successful" />

            <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-green-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
                <div className="max-w-3xl mx-auto">
                    {/* Success Header */}
                    <div className="text-center mb-8 print:mb-4">
                        <div className="inline-flex items-center justify-center w-20 h-20 bg-emerald-100 dark:bg-emerald-900 rounded-full mb-4 print:hidden">
                            <CheckCircle className="h-12 w-12 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h1 className="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">
                            Purchase Successful!
                        </h1>
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            Your order has been confirmed and payment received
                        </p>
                    </div>

                    {/* Main Content Card */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 space-y-6 print:shadow-none">
                        {/* Purchase Number */}
                        <div className="text-center pb-6 border-b border-gray-200 dark:border-gray-700">
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-1">
                                Purchase Confirmation Number
                            </p>
                            <p className="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] font-mono">
                                {purchase.purchase_number}
                            </p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-2">
                                {formatDate(purchase.created_at)}
                            </p>
                        </div>

                        {/* Delivery Tracking Notice */}
                        <div className="bg-blue-50 dark:bg-blue-950/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-6 print:border print:border-blue-200">
                            <div className="flex items-start gap-4">
                                <Package className="h-6 w-6 text-blue-600 flex-shrink-0 mt-1" />
                                <div className="flex-1">
                                    <h3 className="font-semibold text-lg mb-2 text-blue-900 dark:text-blue-100">
                                        Track Your Delivery Within 24 Hours
                                    </h3>
                                    <p className="text-sm text-blue-700 dark:text-blue-300 mb-4">
                                        You may track your delivery within 24 hours.
                                        You can track your delivery status using your National ID number.
                                    </p>
                                    <Link
                                        href={`/delivery-tracking?id=${purchase.national_id}`}
                                        className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors print:hidden"
                                    >
                                        Track Your Delivery
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Customer Details */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-3 mb-3">
                                <User className="h-5 w-5 text-emerald-600" />
                                <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Customer Details
                                </h3>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 ml-8">
                                <div>
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">Full Name</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.full_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">National ID</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] font-mono">{purchase.national_id}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">Phone Number</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.phone}</p>
                                </div>
                                {purchase.email && (
                                    <div>
                                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">Email</p>
                                        <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.email}</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Product Details */}
                        <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="flex items-center gap-3 mb-3">
                                <Package className="h-5 w-5 text-emerald-600" />
                                <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Product Details
                                </h3>
                            </div>
                            <div className="ml-8">
                                <div className="flex justify-between mb-2">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Product</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.product_name}</p>
                                </div>
                                <div className="flex justify-between mb-2">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Category</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.category}</p>
                                </div>
                                <div className="flex justify-between">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Price</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{formatCurrency(purchase.cash_price)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Delivery Details */}
                        <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="flex items-center gap-3 mb-3">
                                <MapPin className="h-5 w-5 text-emerald-600" />
                                <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Delivery Details
                                </h3>
                            </div>
                            <div className="ml-8">
                                <div className="flex justify-between mb-2">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Method</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {purchase.delivery_type === 'gain_outlet' ? 'Collect from Gain Depot' : 'Collect from Post Office'}
                                    </p>
                                </div>
                                {purchase.delivery_type === 'gain_outlet' ? (
                                    <>
                                        <div className="flex justify-between mb-2">
                                            <p className="text-[#706f6c] dark:text-[#A1A09A]">Depot</p>
                                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.depot_name}</p>
                                        </div>
                                        <div className="flex justify-between">
                                            <p className="text-[#706f6c] dark:text-[#A1A09A]">Region</p>
                                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.region}</p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="flex justify-between mb-2">
                                            <p className="text-[#706f6c] dark:text-[#A1A09A]">Branch</p>
                                            <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.city}</p>
                                        </div>
                                        <div className="pt-2">
                                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-1">Address</p>
                                            <p className="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">{purchase.delivery_address || 'Collection at Post Office'}</p>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Payment Details */}
                        <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="flex items-center gap-3 mb-3">
                                <DollarSign className="h-5 w-5 text-emerald-600" />
                                <h3 className="font-semibold text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Payment Details
                                </h3>
                            </div>
                            <div className="ml-8">
                                <div className="flex justify-between mb-2">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Payment Method</p>
                                    <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Paynow</p>
                                </div>
                                <div className="flex justify-between mb-2">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Amount Paid</p>
                                    <p className="font-medium text-emerald-600">{formatCurrency(purchase.amount_paid)}</p>
                                </div>
                                {purchase.transaction_id && (
                                    <div className="flex justify-between mb-2">
                                        <p className="text-[#706f6c] dark:text-[#A1A09A]">Transaction ID</p>
                                        <p className="font-medium text-[#1b1b18] dark:text-[#EDEDEC] font-mono text-sm">{purchase.transaction_id}</p>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <p className="text-[#706f6c] dark:text-[#A1A09A]">Status</p>
                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {purchase.payment_status === 'completed' ? 'Paid' : purchase.payment_status}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Important Information */}
                        <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mt-6">
                            <h4 className="font-semibold text-sm mb-2 text-amber-900 dark:text-amber-100">
                                Important Information:
                            </h4>
                            <ul className="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                <li>• Keep your purchase confirmation number safe for tracking and reference</li>
                                <li>• {purchase.delivery_type === 'gain_outlet'
                                    ? 'Your order will be ready for collection within 2-3 business days'
                                    : 'Your order will be ready for collection at your nearest Post Office within 48-72 hours'}
                                </li>
                                <li>• You will be notified via SMS when your order is ready</li>
                                <li>• For any queries, contact our support team with your purchase number</li>
                                {purchase.delivery_type === 'gain_outlet' && (
                                    <li>• Please bring your National ID when collecting from the depot</li>
                                )}
                            </ul>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 print:hidden">
                            <Button
                                onClick={handlePrint}
                                variant="outline"
                                size="lg"
                                className="flex-1"
                            >
                                <Printer className="mr-2 h-5 w-5" />
                                Print Receipt
                            </Button>
                            <Link
                                href="/"
                                className="flex-1"
                            >
                                <Button
                                    size="lg"
                                    className="w-full bg-emerald-600 hover:bg-emerald-700"
                                >
                                    Return to Home
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Print-only Footer */}
                    <div className="hidden print:block mt-8 text-center text-sm text-gray-600">
                        <p>Thank you for your purchase!</p>
                        <p className="mt-2">For support, contact us at support@bancoZim.com</p>
                    </div>
                </div>
            </div>

            <Footer />
        </>
    );
}