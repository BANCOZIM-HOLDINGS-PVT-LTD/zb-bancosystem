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
    Phone
} from 'lucide-react';
import { Link } from '@inertiajs/react';

interface ApplicationDetails {
    sessionId: string;
    status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'completed';
    applicantName: string;
    business: string;
    loanAmount: string;
    submittedAt: string;
}

export default function ApplicationStatus() {
    const [searchQuery, setSearchQuery] = useState('');
    const [searching, setSearching] = useState(false);
    const [applicationDetails, setApplicationDetails] = useState<ApplicationDetails | null>(null);
    const [error, setError] = useState('');
    const [successMessage, setSuccessMessage] = useState('');

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
        const statusConfig = {
            'pending': {
                icon: Clock,
                color: 'text-yellow-600 dark:text-yellow-400',
                bg: 'bg-yellow-50 dark:bg-yellow-900/20',
                label: 'Pending Review',
                description: 'Your application is in queue and will be reviewed soon.'
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
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
        const Icon = config.icon;

        return { Icon, ...config };
    };

    return (
        <>
            <Head title="Application Status" />
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
            </div>
        </>
    );
}