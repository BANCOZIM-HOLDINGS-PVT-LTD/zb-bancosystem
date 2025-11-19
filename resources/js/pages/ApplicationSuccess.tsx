import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

interface ApplicationSuccessProps {
    referenceCode: string;
    phoneNumber?: string;
}

const ApplicationSuccess: React.FC<ApplicationSuccessProps> = ({ referenceCode, phoneNumber }) => {
    const [loading, setLoading] = useState(false);
    const [showSuccessNotification, setShowSuccessNotification] = useState(false);

    const handleCompleteApplication = async () => {
        setLoading(true);

        try {
            // If no phone number available, still show success but skip SMS
            if (!phoneNumber) {
                console.warn('No phone number available for SMS');
                // Show success notification anyway
                setShowSuccessNotification(true);

                // Close window after 2 seconds
                setTimeout(() => {
                    window.close();
                    // If window.close() doesn't work (some browsers block it), redirect to home
                    if (!window.closed) {
                        window.location.href = '/';
                    }
                }, 2000);
                return;
            }

            // Send thank you SMS
            const response = await fetch('/api/send-application-sms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    phoneNumber: phoneNumber,
                    referenceCode: referenceCode,
                    message: `Thank you for your application! Your reference code is ${referenceCode}. You can track your application status after 48 hours using your National ID number. BancoZim`
                }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Show success notification
                setShowSuccessNotification(true);

                // Close window after 2 seconds
                setTimeout(() => {
                    window.close();
                    // If window.close() doesn't work (some browsers block it), redirect to home
                    if (!window.closed) {
                        window.location.href = '/';
                    }
                }, 2000);
            } else {
                throw new Error(result.message || 'Failed to send SMS');
            }
        } catch (error: any) {
            console.error('Error sending SMS:', error);
            // Show success notification anyway for better UX
            setShowSuccessNotification(true);

            // Close window after 2 seconds even if SMS failed
            setTimeout(() => {
                window.close();
                if (!window.closed) {
                    window.location.href = '/';
                }
            }, 2000);
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="BancoSystem - Application Submitted" />

            {/* Success Notification Popup */}
            {showSuccessNotification && (
                <div className="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 max-w-md mx-4 animate-bounce-in">
                        <div className="text-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-4">
                                <svg className="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                Application Completed!
                            </h3>
                            <p className="text-gray-600 dark:text-gray-400 mb-4">
                                Thank you for your application. {phoneNumber ? 'A confirmation SMS has been sent to your phone number.' : 'Your application has been successfully submitted.'}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-500">
                                This window will close automatically...
                            </p>
                        </div>
                    </div>
                </div>
            )}

            <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-green-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12">
                <div className="max-w-2xl w-full">
                    {/* Success Card */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                        {/* Success Icon */}
                        <div className="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-12 text-center">
                            <div className="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-6">
                                <svg className="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h1 className="text-3xl md:text-4xl font-bold text-white mb-2">
                                Thank You for Your Application!
                            </h1>
                            <p className="text-emerald-100 text-lg">
                                Your application has been submitted successfully
                            </p>
                        </div>

                        {/* Content */}
                        <div className="px-8 py-10">
                            {/* Reference Code Display */}
                            <div className="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-8">
                                <div className="text-center">
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                        Please Note: Your National ID is your Reference Code
                                    </p>
                                    <div className="bg-white dark:bg-gray-900 rounded-lg px-6 py-4 inline-block shadow-sm">
                                        <p className="text-3xl font-bold text-gray-900 dark:text-white font-mono tracking-wider">
                                            {referenceCode}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Important Information */}
                            <div className="space-y-6">
                                {/* Main Message */}
                                <div className="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-5 rounded-r-lg">
                                    <div className="flex items-start">
                                        <div className="flex-shrink-0">
                                            <svg className="h-6 w-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <h3 className="text-lg font-semibold text-amber-800 dark:text-amber-300 mb-2">
                                                Application Under Review
                                            </h3>
                                            <p className="text-amber-700 dark:text-amber-400 text-base leading-relaxed">
                                                You can track your application after <strong>48 hours</strong>. Use your National ID number to track your application status.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* What Happens Next */}
                                <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <svg className="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        What Happens Next?
                                    </h3>
                                    <ol className="space-y-3 text-gray-700 dark:text-gray-300">
                                        <li className="flex items-start">
                                            <span className="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center text-sm font-semibold mr-3">1</span>
                                            <span>Your application is now under review</span>
                                        </li>
                                        <li className="flex items-start">
                                            <span className="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center text-sm font-semibold mr-3">2</span>
                                            <span>Please come back to this app to check the application status after 48 hours via the login menu</span>
                                        </li>
                                        <li className="flex items-start">
                                            <span className="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center text-sm font-semibold mr-3">3</span>
                                            <span>Once approved, delivery will be effected 14 days after approval</span>
                                        </li>
                                        <li className="flex items-start">
                                            <span className="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center text-sm font-semibold mr-3">4</span>
                                            <span>You can track your delivery status on the login menu</span>
                                        </li>
                                    </ol>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="mt-10 flex flex-col gap-4">
                                <button
                                    onClick={handleCompleteApplication}
                                    disabled={loading}
                                    className="w-full inline-flex items-center justify-center px-6 py-4 border-2 border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {loading ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Sending SMS...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Complete Application
                                        </>
                                    )}
                                </button>
                                <p className="text-center text-sm text-gray-500 dark:text-gray-400">
                                    {phoneNumber ? 'Click to receive a confirmation SMS and complete your application' : 'Click to complete your application'}
                                </p>
                            </div>

                            {/* Note */}
                            <p className="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                                Please note your reference code is you National ID number
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default ApplicationSuccess;
