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
    Package,
    Upload,
    FileText
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import Footer from '@/components/Footer';
import ApplicationResubmission from '@/components/ApplicationResubmission';

interface ApplicationDetails {
    sessionId: string;
    status: string;
    currentStep?: string;
    applicationType?: 'account_opening' | 'loan';
    applicantName: string;
    productName: string;
    loanAmount: string;
    submittedAt: string;
    lastUpdated?: string;
    nextAction?: string;
    rejectionReason?: string;
    unclearDocuments?: string[];
    progressPercentage?: number;
    timeline?: {
        title: string;
        description: string;
        timestamp: string;
        status: 'completed' | 'current' | 'pending';
    }[];
    deliveryTracking?: {
        status: string;
        statusLabel: string;
        courierType?: string;
        depot?: string;
        dispatchedAt?: string;
        estimatedDelivery?: string;
    };
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
            const config = (accountStatusConfig as any)[status] || accountStatusConfig.pending;
            const Icon = config.icon;
            return { Icon, ...config };
        }

        // Loan application status flow
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
            'document_verification': {
                icon: Clock,
                color: 'text-amber-600 dark:text-amber-400',
                bg: 'bg-amber-50 dark:bg-amber-900/20',
                label: 'Stage 1: Document Verification',
                description: 'Bancozim Admin is currently verifying your submitted documents.'
            },
            'resubmission_required': {
                icon: AlertCircle,
                color: 'text-orange-600 dark:text-orange-400',
                bg: 'bg-orange-50 dark:bg-orange-900/20',
                label: 'Action Required: Re-upload Documents',
                description: 'Some of your documents were unclear. Please re-upload them below to proceed.'
            },
            'employment_proof_required': {
                icon: FileText,
                color: 'text-blue-600 dark:text-blue-400',
                bg: 'bg-blue-50 dark:bg-blue-900/20',
                label: 'Action Required: Proof of Employment',
                description: 'Please upload your Confirmation of Employment letter from your HR.'
            },
            'deposit_payment_required': {
                icon: DollarSign,
                color: 'text-blue-600 dark:text-blue-400',
                bg: 'bg-blue-50 dark:bg-blue-900/20',
                label: 'Action Required: Deposit Payment',
                description: 'Please upload your proof of deposit payment below.'
            },
            'allocation': {
                icon: Clock,
                color: 'text-purple-600 dark:text-purple-400',
                bg: 'bg-purple-50 dark:bg-purple-900/20',
                label: 'Being Allocated',
                description: 'Your application is being assigned to a branch for review.'
            },
            'under_review': {
                icon: AlertCircle,
                color: 'text-indigo-600 dark:text-indigo-400',
                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                label: 'Stage 2: Loan Officer Review',
                description: 'A Qupa Loan Officer is currently assessing your financial eligibility.'
            },
            'final_approval': {
                icon: Clock,
                color: 'text-blue-600 dark:text-blue-400',
                bg: 'bg-blue-50 dark:bg-blue-900/20',
                label: 'Stage 3: Manager Approval',
                description: 'Your application is with the Branch Manager for final sign-off.'
            },
            'approved': {
                icon: CheckCircle,
                color: 'text-green-600 dark:text-green-400',
                bg: 'bg-green-50 dark:bg-green-900/20',
                label: 'Approved',
                description: applicationDetails?.nextAction || 'Congratulations! Your application has been approved and delivery initiated.'
            },
            'rejected': {
                icon: XCircle,
                color: 'text-red-600 dark:text-red-400',
                bg: 'bg-red-50 dark:bg-red-900/20',
                label: 'Rejected',
                description: applicationDetails?.nextAction || 'Your application was not approved at this time.'
            },
            'completed': {
                icon: CheckCircle,
                color: 'text-emerald-600 dark:text-emerald-400',
                bg: 'bg-emerald-50 dark:bg-emerald-900/20',
                label: 'Completed',
                description: 'Your application has been completed successfully.'
            },
        };

        const config = (statusConfig as any)[status] || statusConfig.pending;
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
                                                    <p className="text-gray-600 dark:text-gray-400 font-medium">{description}</p>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })()}

                                {/* Progress Bar */}
                                {applicationDetails.progressPercentage !== undefined && (
                                    <div className="mb-8">
                                        <div className="flex justify-between mb-2 text-sm font-medium">
                                            <span>Application Progress</span>
                                            <span>{applicationDetails.progressPercentage}%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2.5">
                                            <div 
                                                className="bg-emerald-600 h-2.5 rounded-full transition-all duration-500" 
                                                style={{ width: `${applicationDetails.progressPercentage}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                )}

                                {/* Delivery Tracking Details */}
                                {applicationDetails.deliveryTracking && (
                                    <div className="mb-8 p-4 border border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/30 dark:bg-emerald-900/10 rounded-xl">
                                        <div className="flex items-center gap-3 mb-4">
                                            <Package className="h-6 w-6 text-emerald-600" />
                                            <h3 className="text-lg font-semibold text-emerald-800 dark:text-emerald-300">Delivery Information</h3>
                                        </div>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                            <div className="space-y-1">
                                                <p className="text-gray-500">Current Status</p>
                                                <p className="font-bold text-emerald-700 dark:text-emerald-400 text-base">{applicationDetails.deliveryTracking.statusLabel}</p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-gray-500">Courier / Method</p>
                                                <p className="font-semibold text-gray-800 dark:text-gray-200">{applicationDetails.deliveryTracking.courierType || 'Pending Assignment'}</p>
                                            </div>
                                            {applicationDetails.deliveryTracking.depot && (
                                                <div className="space-y-1">
                                                    <p className="text-gray-500">Collection Point / Depot</p>
                                                    <p className="font-semibold text-gray-800 dark:text-gray-200">{applicationDetails.deliveryTracking.depot}</p>
                                                </div>
                                            )}
                                            {applicationDetails.deliveryTracking.estimatedDelivery && (
                                                <div className="space-y-1">
                                                    <p className="text-gray-500">Estimated Delivery Date</p>
                                                    <p className="font-semibold text-emerald-600 dark:text-emerald-400">{applicationDetails.deliveryTracking.estimatedDelivery}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Application Timeline */}
                                {applicationDetails.timeline && applicationDetails.timeline.length > 0 && (
                                    <div className="mb-8">
                                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                                            <Clock className="h-5 w-5 text-gray-500" />
                                            Application Timeline
                                        </h3>
                                        <div className="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-emerald-500 before:via-gray-200 before:to-gray-200 dark:before:via-gray-800 dark:before:to-gray-800">
                                            {applicationDetails.timeline.map((item, index) => (
                                                <div key={index} className="relative flex items-start gap-6">
                                                    <div className={`absolute left-0 mt-1.5 h-10 w-10 flex items-center justify-center rounded-full border-4 border-white dark:border-[#1a1a1a] shadow-sm z-10 
                                                        ${item.status === 'completed' ? 'bg-emerald-500' : 
                                                          item.status === 'current' ? 'bg-emerald-100 dark:bg-emerald-900 animate-pulse' : 'bg-gray-100 dark:bg-gray-800'}`}>
                                                        {item.status === 'completed' ? (
                                                            <CheckCircle className="h-5 w-5 text-white" />
                                                        ) : item.status === 'current' ? (
                                                            <Clock className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                                        ) : (
                                                            <div className="h-2 w-2 rounded-full bg-gray-400" />
                                                        )}
                                                    </div>
                                                    <div className="ml-12">
                                                        <h4 className={`font-bold ${item.status === 'pending' ? 'text-gray-400' : 'text-gray-900 dark:text-gray-100'}`}>
                                                            {item.title}
                                                        </h4>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">{item.description}</p>
                                                        {item.timestamp && (
                                                            <span className="text-xs font-medium text-emerald-600 dark:text-emerald-400 mt-1 block">
                                                                {item.timestamp}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Resubmission Forms */}
                                {applicationDetails.currentStep === 'awaiting_document_reupload' && (
                                    <ApplicationResubmission 
                                        sessionId={applicationDetails.sessionId}
                                        type="reupload"
                                        unclearDocuments={applicationDetails.unclearDocuments}
                                        onSuccess={(msg) => {
                                            setSuccessMessage(msg);
                                            handleSearchWithRef(applicationDetails.sessionId);
                                        }}
                                    />
                                )}

                                {applicationDetails.currentStep === 'awaiting_proof_of_employment' && (
                                    <ApplicationResubmission
                                        sessionId={applicationDetails.sessionId}
                                        type="employment_proof"
                                        onSuccess={(msg) => {
                                            setSuccessMessage(msg);
                                            handleSearchWithRef(applicationDetails.sessionId);
                                        }}
                                    />
                                )}

                                {applicationDetails.currentStep === 'awaiting_deposit_payment' && (
                                    <ApplicationResubmission
                                        sessionId={applicationDetails.sessionId}
                                        type="deposit_payment"
                                        onSuccess={(msg) => {
                                            setSuccessMessage(msg);
                                            handleSearchWithRef(applicationDetails.sessionId);
                                        }}
                                    />
                                )}

                                <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Reference Number</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.sessionId}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Applicant Name</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.applicantName}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Product/Business</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.productName}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600 dark:text-gray-400">Submitted On</span>
                                        <span className="font-semibold text-gray-900 dark:text-gray-100">{applicationDetails.submittedAt}</span>
                                    </div>
                                </div>
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
                </div>
            </div >
        </>
    );
}
