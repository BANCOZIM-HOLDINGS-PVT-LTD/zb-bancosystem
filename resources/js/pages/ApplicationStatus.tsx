import { Head } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    Search,
    CheckCircle,
    Clock,
    XCircle,
    AlertCircle,
    ArrowLeft,
    Phone,
    CreditCard,
    DollarSign,
    Package
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import Footer from '@/components/Footer';

interface ApplicationDetails {
    sessionId: string;
    status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'completed' | 'account_opened';
    applicationType?: 'account_opening' | 'loan';
    applicantName: string;
    business: string;
    loanAmount: string;
    submittedAt: string;
    creditType?: string;
    depositAmount?: number;
    depositPaid?: boolean;
    depositPaidAt?: string;
    depositTransactionId?: string;
    depositPaymentMethod?: string;
    zbAccountNumber?: string;
    loanEligible?: boolean;
    loanEligibleAt?: string;
    nextAction?: string;
    rejectionReason?: string;
}

export default function ApplicationStatus() {
    const [searchQuery, setSearchQuery] = useState('');
    const [searching, setSearching] = useState(false);
    const [applicationDetails, setApplicationDetails] = useState<ApplicationDetails | null>(null);
    const [error, setError] = useState('');
    const [successMessage, setSuccessMessage] = useState('');
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>('ecocash');
    const [processingPayment, setProcessingPayment] = useState(false);
    const [processingLoan, setProcessingLoan] = useState(false);

    // Check for success redirect from application submission
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');
        const success = urlParams.get('success');

        if (ref) {
            setSearchQuery(ref);
            if (success === '1') {
                setSuccessMessage(`Thank you for submitting your application. To track if your application has been successful or rejected, check after 48 hours on this platform and quote your reference no: ${ref}`);
                // Auto-search for the application
                setTimeout(() => {
                    handleSearchWithRef(ref);
                }, 1000);
            }
        }
    }, []);

    const handleSearchWithRef = async (reference: string) => {
        setSearching(true);
        setError('');
        setSuccessMessage('');

        try {
            // Sanitize the reference by removing spaces and special characters (keep only alphanumeric)
            const sanitizedReference = reference.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

            if (!sanitizedReference || sanitizedReference.length < 5) {
                setError('Please enter a valid National ID or reference code (minimum 5 characters).');
                setApplicationDetails(null);
                setSearching(false);
                return;
            }

            // Validate the reference code
            const validateResponse = await fetch('/api/reference-code/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ code: sanitizedReference }),
            });

            const validateData = await validateResponse.json();

            if (!validateData.success) {
                setError('Invalid National ID or reference code. Please check and try again.');
                setApplicationDetails(null);
                setSearching(false);
                return;
            }

            // Now get the application status
            const response = await fetch(`/api/application/status/${sanitizedReference}`);
            if (response.ok) {
                const data = await response.json();
                setApplicationDetails(data);
            } else if (response.status === 404) {
                setError('Application not found. Please check your reference number.');
                setApplicationDetails(null);
            } else {
                setError('An error occurred. Please try again.');
                setApplicationDetails(null);
            }
        } catch (err) {
            setError('Failed to fetch application status. Please try again.');
            setApplicationDetails(null);
        } finally {
            setSearching(false);
        }
    };

    const handleSearch = async () => {
        if (!searchQuery.trim()) {
            setError('Please enter a reference number');
            return;
        }

        await handleSearchWithRef(searchQuery);
    };

    const getStatusDisplay = (status: string) => {
        // Account opening has a simplified 3-stage flow
        if (applicationDetails?.applicationType === 'account_opening') {
            const accountStatusConfig = {
                'pending': {
                    icon: Clock,
                    color: 'text-amber-600 dark:text-amber-400',
                    bg: 'bg-amber-50 dark:bg-amber-900/20',
                    label: 'Pending — Visit Your Nearest Branch',
                    description: 'Please go to your nearest ZB Bank branch and sign your account opening documents. Bring your National ID and reference number.'
                },
                'submitted': {
                    icon: Clock,
                    color: 'text-amber-600 dark:text-amber-400',
                    bg: 'bg-amber-50 dark:bg-amber-900/20',
                    label: 'Pending — Visit Your Nearest Branch',
                    description: 'Please go to your nearest ZB Bank branch and sign your account opening documents. Bring your National ID and reference number.'
                },
                'referred': {
                    icon: Clock,
                    color: 'text-blue-600 dark:text-blue-400',
                    bg: 'bg-blue-50 dark:bg-blue-900/20',
                    label: 'Referred to Branch',
                    description: 'Your application has been sent to your nearest ZB Bank branch. Please visit the branch to sign your documents.'
                },
                'rejected': {
                    icon: XCircle,
                    color: 'text-red-600 dark:text-red-400',
                    bg: 'bg-red-50 dark:bg-red-900/20',
                    label: 'Documents Not Approved',
                    description: applicationDetails?.rejectionReason || 'Your account opening documents were not approved. Please contact your nearest ZB Bank branch for assistance.'
                },
                'account_opened': {
                    icon: CheckCircle,
                    color: 'text-green-600 dark:text-green-400',
                    bg: 'bg-green-50 dark:bg-green-900/20',
                    label: 'Account Opened',
                    description: 'Your ZB Bank account has been successfully opened! You can now use your account for banking services.'
                },
            };
            const config = accountStatusConfig[status as keyof typeof accountStatusConfig] || accountStatusConfig.pending;
            const Icon = config.icon;
            return { Icon, ...config };
        }

        // Loan application status flow (unchanged)
        const statusConfig = {
            'pending': {
                icon: Clock,
                color: 'text-yellow-600 dark:text-yellow-400',
                bg: 'bg-yellow-50 dark:bg-yellow-900/20',
                label: 'Pending Review',
                description: 'Your application is in queue and will be reviewed soon.'
            },
            'submitted': {
                icon: Clock,
                color: 'text-yellow-600 dark:text-yellow-400',
                bg: 'bg-yellow-50 dark:bg-yellow-900/20',
                label: 'Application Submitted',
                description: 'Your application has been received and is pending review.'
            },
            'pending_verification': {
                icon: Clock,
                color: 'text-amber-600 dark:text-amber-400',
                bg: 'bg-amber-50 dark:bg-amber-900/20',
                label: 'Document Verification',
                description: 'We are currently verifying your submitted documents.'
            },
            'sent_for_checks': {
                icon: AlertCircle,
                color: 'text-indigo-600 dark:text-indigo-400',
                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                label: 'Processing Checks',
                description: 'Your application has been verified and is undergoing automated checks.'
            },
            'awaiting_credit_check': {
                icon: AlertCircle,
                color: 'text-indigo-600 dark:text-indigo-400',
                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                label: 'Credit Check in Progress',
                description: 'We are currently assessing your credit eligibility.'
            },
            'awaiting_ssb_approval': {
                icon: AlertCircle,
                color: 'text-indigo-600 dark:text-indigo-400',
                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                label: 'SSB Approval in Progress',
                description: 'Your application has been sent to SSB for approval.'
            },
            'under_review': {
                icon: AlertCircle,
                color: 'text-blue-600 dark:text-blue-400',
                bg: 'bg-blue-50 dark:bg-blue-900/20',
                label: 'Under Review',
                description: 'Our team is currently reviewing your application.'
            },
            'approved': {
                icon: CheckCircle,
                color: 'text-green-600 dark:text-green-400',
                bg: 'bg-green-50 dark:bg-green-900/20',
                label: 'Approved',
                description: 'Congratulations! Your application has been approved.'
            },
            'ssb_approved': {
                icon: CheckCircle,
                color: 'text-green-600 dark:text-green-400',
                bg: 'bg-green-50 dark:bg-green-900/20',
                label: 'Approved',
                description: 'Congratulations! Your SSB loan application has been approved.'
            },
            'approved_awaiting_delivery': {
                icon: Package,
                color: 'text-emerald-600 dark:text-emerald-400',
                bg: 'bg-emerald-50 dark:bg-emerald-900/20',
                label: 'Approved - Awaiting Delivery',
                description: 'Your application is approved and being prepared for delivery.'
            },
            'credit_check_good_approved': {
                icon: CheckCircle,
                color: 'text-green-600 dark:text-green-400',
                bg: 'bg-green-50 dark:bg-green-900/20',
                label: 'Credit Check Passed',
                description: 'Your credit check was successful. Finalizing approval.'
            },
            'credit_check_poor_rejected': {
                icon: XCircle,
                color: 'text-red-600 dark:text-red-400',
                bg: 'bg-red-50 dark:bg-red-900/20',
                label: 'Application Rejected',
                description: 'We regret to inform you that your application was not successful due to credit check results.'
            },
            'rejected': {
                icon: XCircle,
                color: 'text-red-600 dark:text-red-400',
                bg: 'bg-red-50 dark:bg-red-900/20',
                label: 'Rejected',
                description: 'Your application was not approved at this time.'
            },
            'completed': {
                icon: CheckCircle,
                color: 'text-emerald-600 dark:text-emerald-400',
                bg: 'bg-emerald-50 dark:bg-emerald-900/20',
                label: 'Completed',
                description: 'Your application has been completed successfully.'
            },
            'account_opened': {
                icon: CheckCircle,
                color: 'text-green-600 dark:text-green-400',
                bg: 'bg-green-50 dark:bg-green-900/20',
                label: 'Account Opened',
                description: 'Your ZB Bank account has been successfully opened.'
            },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
        const Icon = config.icon;

        return { Icon, ...config };
    };

    const handleApplyForLoan = async () => {
        if (!applicationDetails) return;

        setProcessingLoan(true);
        setError('');

        try {
            const response = await fetch('/application/convert-account', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    reference_code: applicationDetails.sessionId
                }),
            });

            const data = await response.json();

            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                setError(data.message || 'Failed to start loan application. Please try again.');
                setProcessingLoan(false);
            }
        } catch (err) {
            setError('Failed to start loan application. Please try again.');
            setProcessingLoan(false);
        }
    };

    const handleDepositPayment = async () => {
        if (!applicationDetails) return;

        setProcessingPayment(true);
        setError('');

        try {
            const response = await fetch('/deposit/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    reference_code: applicationDetails.sessionId,
                    payment_method: selectedPaymentMethod,
                }),
            });

            const data = await response.json();

            if (data.success && data.data.redirect_url) {
                // Redirect to Paynow payment page
                window.location.href = data.data.redirect_url;
            } else {
                setError(data.message || 'Failed to initiate payment. Please try again.');
                setProcessingPayment(false);
            }
        } catch (err) {
            setError('Failed to process payment. Please try again.');
            setProcessingPayment(false);
        }
    };

    return (
        <>
            <Head title="BancoSystem - Application Status" />
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
                            Check Application Status
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 mt-2">
                            Enter your reference number to check your application status
                        </p>
                    </div>

                    {/* Search Section */}
                    <Card className="p-6 mb-8">
                        <div className="max-w-xl mx-auto">
                            <Label htmlFor="reference" className="text-lg mb-2 block">
                                Reference Number
                            </Label>
                            <div className="flex gap-3">
                                <Input
                                    id="reference"
                                    placeholder="Enter your reference number"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    className="text-lg"
                                />
                                <Button
                                    onClick={handleSearch}
                                    disabled={searching}
                                    size="lg"
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                >
                                    <Search className="h-5 w-5 mr-2" />
                                    {searching ? 'Searching...' : 'Search'}
                                </Button>
                            </div>
                            {error && (
                                <p className="text-red-600 dark:text-red-400 mt-2 text-sm">
                                    {error}
                                </p>
                            )}
                            {successMessage && (
                                <p className="text-green-600 dark:text-green-400 mt-2 text-sm font-medium">
                                    {successMessage}
                                </p>
                            )}
                        </div>
                    </Card>

                    {/* Results Section */}
                    {applicationDetails && (
                        <div className="space-y-6">
                            {/* Application Status */}
                            <Card className="p-8">
                                <h2 className="text-xl font-semibold mb-6 text-center">Application Status</h2>

                                {(() => {
                                    const { Icon, color, bg, label, description } = getStatusDisplay(applicationDetails.status);
                                    return (
                                        <div className={`p-6 rounded-lg ${bg} mb-6`}>
                                            <div className="flex flex-col items-center text-center gap-4">
                                                <Icon className={`h-16 w-16 ${color}`} />
                                                <div>
                                                    <h3 className={`text-2xl font-semibold ${color} mb-2`}>{label}</h3>
                                                    <p className="text-gray-600 dark:text-gray-400">{description}</p>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })()}

                                <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Reference Number</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.sessionId}</span>
                                    </div>
                                    {applicationDetails.zbAccountNumber && (
                                        <div className="flex justify-between">
                                            <span className="text-gray-600 dark:text-gray-400">ZB Account Number</span>
                                            <span className="font-semibold text-emerald-600 dark:text-emerald-400">{applicationDetails.zbAccountNumber}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Applicant Name</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.applicantName}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Product/Business</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.business}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Submitted On</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.submittedAt}</span>
                                    </div>
                                </div>
                            </Card>



                            {/* Apply for Loan Section - Only for loan-type applications that are approved */}
                            {applicationDetails.applicationType !== 'account_opening' &&
                                ['account_opened', 'approved', 'completed'].includes(applicationDetails.status) && (
                                    <Card className="p-8 bg-gradient-to-br from-emerald-50 to-blue-50 dark:from-emerald-900/20 dark:to-blue-900/20 border-2 border-emerald-200 dark:border-emerald-800">
                                        <div className="text-center mb-6">
                                            <div className="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 dark:bg-emerald-900 rounded-full mb-4">
                                                <CreditCard className="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                                Apply for a Loan
                                            </h2>
                                            <p className="text-gray-600 dark:text-gray-400">
                                                {applicationDetails.loanEligible
                                                    ? "You are eligible for a loan! Proceed to apply now."
                                                    : "Your account is open. You can now apply for a loan."}
                                            </p>
                                        </div>

                                        <Button
                                            onClick={handleApplyForLoan}
                                            disabled={processingLoan}
                                            size="lg"
                                            className="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-lg py-6"
                                        >
                                            {processingLoan ? 'Processing...' : 'Apply for Loan Now'}
                                        </Button>
                                    </Card>
                                )}

                            {/* Deposit Payment Section - Only for approved PDC applications with unpaid deposit */}
                            {applicationDetails.status === 'approved' &&
                                applicationDetails.creditType === 'PDC' &&
                                !applicationDetails.depositPaid &&
                                applicationDetails.depositAmount &&
                                applicationDetails.depositAmount > 0 && (
                                    <Card className="p-8 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 border-2 border-emerald-200 dark:border-emerald-800">
                                        <div className="text-center mb-6">
                                            <div className="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 dark:bg-emerald-900 rounded-full mb-4">
                                                <DollarSign className="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                                Pay Your Deposit
                                            </h2>
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Your application has been approved! Please pay your deposit to proceed with delivery.
                                            </p>
                                        </div>

                                        <div className="max-w-md mx-auto space-y-6">
                                            {/* Deposit Amount */}
                                            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 text-center">
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">Deposit Amount</p>
                                                <p className="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                                                    ${applicationDetails.depositAmount.toFixed(2)}
                                                </p>
                                            </div>

                                            {/* Payment Method Selector */}
                                            <div>
                                                <Label className="text-base font-semibold mb-3 block">Select Payment Method</Label>
                                                <div className="grid grid-cols-2 gap-3">
                                                    {[
                                                        { id: 'ecocash', label: 'EcoCash', icon: Phone },
                                                        { id: 'smilecash', label: 'OneMoney', icon: Phone },
                                                        { id: 'card', label: 'Debit Card', icon: CreditCard },
                                                        { id: 'mastercard', label: 'Mastercard', icon: CreditCard },
                                                    ].map((method) => {
                                                        const MethodIcon = method.icon;
                                                        const isSelected = selectedPaymentMethod === method.id;
                                                        return (
                                                            <button
                                                                key={method.id}
                                                                onClick={() => setSelectedPaymentMethod(method.id)}
                                                                className={`p-4 rounded-lg border-2 transition-all ${isSelected
                                                                    ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:border-emerald-500'
                                                                    : 'border-gray-200 hover:border-emerald-300 dark:border-gray-700 dark:hover:border-emerald-700'
                                                                    }`}
                                                            >
                                                                <div className="flex flex-col items-center gap-2">
                                                                    <MethodIcon className={`h-6 w-6 ${isSelected ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-400'}`} />
                                                                    <span className={`text-sm font-medium ${isSelected ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-300'}`}>
                                                                        {method.label}
                                                                    </span>
                                                                </div>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>

                                            {/* Pay Now Button */}
                                            <Button
                                                onClick={handleDepositPayment}
                                                disabled={processingPayment}
                                                size="lg"
                                                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-lg py-6"
                                            >
                                                {processingPayment ? (
                                                    <>Processing...</>
                                                ) : (
                                                    <>
                                                        <CreditCard className="h-5 w-5 mr-2" />
                                                        Pay ${applicationDetails.depositAmount.toFixed(2)} Now
                                                    </>
                                                )}
                                            </Button>

                                            <p className="text-xs text-center text-gray-500 dark:text-gray-400">
                                                Secure payment powered by Paynow. Your delivery will be initiated once payment is confirmed.
                                            </p>
                                        </div>
                                    </Card>
                                )}

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
                </div>
            </div >
        </>
    );
}