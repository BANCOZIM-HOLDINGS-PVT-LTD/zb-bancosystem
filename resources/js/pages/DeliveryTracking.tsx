import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    Search,
    Package,
    MapPin,
    ArrowLeft,
    Phone,
    CheckCircle,
    Clock,
    Truck,
    User,
    Calendar,
    ShoppingBag
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import Footer from '@/components/Footer';

interface DeliveryDetails {
    sessionId: string;
    customerName: string;
    product: string;
    status: string;
    depot: string;
    estimatedDelivery: string;
    trackingNumber: string;
    trackingType: string;
    purchaseType?: string;
    deliveredAt?: string | null;
    // Holiday Package Details
    zimparksVoucher?: string;
    bookingNames?: string;
    datesBooked?: string;
    packageDetails?: string;
}

export default function DeliveryTracking() {
    const [searchQuery, setSearchQuery] = useState('');
    const [searching, setSearching] = useState(false);
    const [deliveryDetails, setDeliveryDetails] = useState<DeliveryDetails | null>(null);
    const [error, setError] = useState('');

    // Check if reference number is passed in the URL
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');
        if (ref) {
            setSearchQuery(ref);
            handleSearch(ref);
        }
    }, []);

    const handleSearch = async (reference?: string) => {
        const searchRef = reference || searchQuery;

        if (!searchRef.trim()) {
            setError('Please enter a reference or tracking number');
            return;
        }

        setSearching(true);
        setError('');

        try {
            const response = await fetch(`/api/delivery/tracking/${searchRef}`);
            if (response.ok) {
                const data = await response.json();
                setDeliveryDetails(data);
            } else if (response.status === 404) {
                setError('Delivery not found. Please check your reference number.');
                setDeliveryDetails(null);
            } else {
                setError('An error occurred. Please try again.');
                setDeliveryDetails(null);
            }
        } catch (err) {
            setError('Failed to fetch delivery status. Please try again.');
            setDeliveryDetails(null);
        } finally {
            setSearching(false);
        }
    };

    const getStatusDisplay = (status: string) => {
        const statusConfig = {
            'processing': { icon: Clock, color: 'text-yellow-600 dark:text-yellow-400', bg: 'bg-yellow-50 dark:bg-yellow-900/20', label: 'Processing' },
            'dispatched': { icon: Truck, color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-50 dark:bg-blue-900/20', label: 'Dispatched' },
            'in_transit': { icon: Truck, color: 'text-purple-600 dark:text-purple-400', bg: 'bg-purple-50 dark:bg-purple-900/20', label: 'In Transit' },
            'out_for_delivery': { icon: Truck, color: 'text-orange-600 dark:text-orange-400', bg: 'bg-orange-50 dark:bg-orange-900/20', label: 'Out for Delivery' },
            'delivered': { icon: CheckCircle, color: 'text-green-600 dark:text-green-400', bg: 'bg-green-50 dark:bg-green-900/20', label: 'Delivered' },
            'pending': { icon: Clock, color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-50 dark:bg-blue-900/20', label: 'Order Processing' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
        const Icon = config.icon;

        return { Icon, ...config };
    };

    return (
        <>
            <Head title="Delivery Tracking" />
            <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <div className="max-w-2xl mx-auto px-4 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <Link
                            href="/"
                            className="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mb-4"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Home
                        </Link>
                        <h1 className="text-3xl font-semibold text-gray-900 dark:text-gray-100">
                            Track Your Delivery
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 mt-2">
                            Enter your reference number to track your delivery
                        </p>
                    </div>

                    {/* Search Section */}
                    <Card className="p-6 mb-8">
                        <div className="max-w-xl mx-auto">
                            <Label htmlFor="tracking" className="text-lg mb-2 block">
                                Reference Number
                            </Label>
                            <div className="flex gap-3">
                                <Input
                                    id="tracking"
                                    placeholder="Enter your reference number"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    className="text-lg"
                                />
                                <Button
                                    onClick={() => handleSearch()}
                                    disabled={searching}
                                    size="lg"
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                >
                                    <Search className="h-5 w-5 mr-2" />
                                    {searching ? 'Searching...' : 'Track'}
                                </Button>
                            </div>
                            {error && (
                                <p className="text-red-600 dark:text-red-400 mt-2 text-sm">
                                    {error}
                                </p>
                            )}
                        </div>
                    </Card>

                    {/* Results Section */}
                    {deliveryDetails && (
                        <div className="space-y-6">
                            {/* Delivery Status */}
                            <Card className="p-8">
                                <h2 className="text-xl font-semibold mb-6 text-center">Delivery Status</h2>

                                {(() => {
                                    const { Icon, color, bg, label } = getStatusDisplay(deliveryDetails.status);
                                    return (
                                        <div className={`p-6 rounded-lg ${bg} mb-6`}>
                                            <div className="flex items-center justify-center gap-3">
                                                <Icon className={`h-8 w-8 ${color}`} />
                                                <span className={`text-2xl font-medium ${color}`}>{label}</span>
                                            </div>
                                        </div>
                                    );
                                })()}

                                <div className="space-y-4">
                                    {/* Customer Name */}
                                    <div className="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                        <div className="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-lg">
                                            <User className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Customer Name
                                            </p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {deliveryDetails.customerName}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Product */}
                                    <div className="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                        <div className="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-lg">
                                            <ShoppingBag className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Product
                                            </p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {deliveryDetails.product}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Depot */}
                                    <div className="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                        <div className="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
                                            <MapPin className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Delivery Depot
                                            </p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {deliveryDetails.depot}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Estimated Delivery */}
                                    <div className="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg">
                                            <Calendar className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Estimated Delivery Date
                                            </p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {deliveryDetails.estimatedDelivery}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Tracking Number */}
                                    <div className="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                        <div className="p-2 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg">
                                            <Package className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                {deliveryDetails.trackingType}
                                            </p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100 font-mono">
                                                {deliveryDetails.trackingNumber}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Holiday Package Details */}
                                {deliveryDetails.zimparksVoucher && (
                                    <div className="mt-6 border-t pt-6">
                                        <h3 className="text-lg font-semibold mb-4 text-emerald-800 dark:text-emerald-400">
                                            Holiday Package Details
                                        </h3>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
                                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                    Zimparks Voucher
                                                </p>
                                                <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {deliveryDetails.zimparksVoucher}
                                                </p>
                                            </div>
                                            <div className="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
                                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                    Dates Booked
                                                </p>
                                                <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {deliveryDetails.datesBooked}
                                                </p>
                                            </div>
                                            <div className="md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
                                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                    Names
                                                </p>
                                                <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {deliveryDetails.bookingNames}
                                                </p>
                                            </div>
                                            <div className="md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
                                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                    Package Details
                                                </p>
                                                <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {deliveryDetails.packageDetails}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Delivery Status Messages */}
                                {deliveryDetails.purchaseType === 'cash' && deliveryDetails.status !== 'delivered' && (
                                    <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg">
                                        <div className="flex items-start gap-3">
                                            <Clock className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                                            <div>
                                                <p className="font-medium text-blue-900 dark:text-blue-100">
                                                    Expected Delivery Time
                                                </p>
                                                <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                                    Expect delivery within 72 hours
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {deliveryDetails.status === 'delivered' && deliveryDetails.deliveredAt && (
                                    <div className="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-lg">
                                        <div className="flex items-start gap-3">
                                            <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5" />
                                            <div>
                                                <p className="font-medium text-green-900 dark:text-green-100">
                                                    Thank you for collection!
                                                </p>
                                                <p className="text-sm text-green-700 dark:text-green-300 mt-1">
                                                    You can apply again after 90 days.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </Card>

                            {/* Customer Support */}
                            <Card className="p-6 bg-gradient-to-br from-emerald-50 to-blue-50 dark:from-emerald-900/20 dark:to-blue-900/20">
                                <div className="text-center">
                                    <Phone className="h-12 w-12 text-emerald-600 dark:text-emerald-400 mx-auto mb-4" />
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        Need Help?
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        Contact our customer support team for assistance
                                    </p>
                                    <Button
                                        size="lg"
                                        className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                        onClick={() => window.location.href = 'tel:+263000000000'}
                                    >
                                        <Phone className="h-5 w-5 mr-2" />
                                        Call Customer Support
                                    </Button>
                                </div>
                            </Card>
                        </div>
                    )}
                </div >
            </div >

            <Footer />
        </>
    );
}