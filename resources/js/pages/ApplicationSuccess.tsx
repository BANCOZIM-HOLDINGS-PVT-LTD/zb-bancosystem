import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Footer from '@/components/Footer';

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

            <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-green-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-3 sm:px-4 py-6 sm:py-12">
                <div className="max-w-2xl w-full">
                    {/* Success Card */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                        {/* Success Icon */}
                        <div className="bg-white dark:bg-gray-800 pt-8 sm:pt-12 pb-6 sm:pb-8 text-center px-4">
                            <div className="inline-flex items-center justify-center w-16 h-16 sm:w-24 sm:h-24 bg-green-50 dark:bg-green-900/30 rounded-full mb-4 sm:mb-6 relative">
                                <svg className="w-8 h-8 sm:w-12 sm:h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <div className="absolute inset-0 rounded-full border-4 border-green-100 dark:border-green-800 animate-pulse"></div>
                            </div>
                            <h1 className="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2 tracking-tight">
                                Thank You for Your Application!
                            </h1>
                            <p className="text-gray-500 dark:text-gray-400 text-sm sm:text-lg">
                                Your application has been submitted successfully
                            </p>
                        </div>

                        {/* Content */}
                        <div className="px-4 sm:px-8 pb-6 sm:pb-10">
                            {/* Reference Code Display */}
                            <div className="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 sm:p-8 mb-6 sm:mb-8 text-center border border-gray-200 dark:border-gray-700">
                                <p className="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-3 sm:mb-4 font-medium">
                                    Please Note - Your application reference number is
                                </p>
                                <div className="text-lg sm:text-2xl md:text-4xl font-bold text-gray-900 dark:text-white font-mono tracking-wide sm:tracking-widest break-all overflow-wrap-anywhere">
                                    {referenceCode}
                                </div>
                            </div>

                            {/* Tracking Info */}
                            <div className="text-center mb-6 sm:mb-10">
                                <p className="text-sm sm:text-base text-gray-600 dark:text-gray-400 leading-relaxed max-w-lg mx-auto">
                                    You can track your application status after <span className="font-bold text-gray-900 dark:text-white">48 hours</span> by simply using the login menu instead of register selection.
                                </p>
                            </div>

                            {/* What Happens Next */}
                            <div className="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-4 sm:p-6 mb-6 sm:mb-8 border border-blue-100 dark:border-blue-900/30">
                                <h3 className="text-base sm:text-lg font-bold text-gray-900 dark:text-white mb-3 sm:mb-4">
                                    What Happens Next
                                </h3>
                                <ol className="space-y-3 sm:space-y-4">
                                    <li className="flex gap-3 sm:gap-4">
                                        <div className="flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs sm:text-sm font-bold mt-0.5">1</div>
                                        <p className="text-sm sm:text-base text-gray-700 dark:text-gray-300">
                                            Your application has been sent to SSB/ZB for review
                                        </p>
                                    </li>
                                    <li className="flex gap-3 sm:gap-4">
                                        <div className="flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs sm:text-sm font-bold mt-0.5">2</div>
                                        <p className="text-sm sm:text-base text-gray-700 dark:text-gray-300">
                                            You will be notified of the ZB/SSB response when you revisit this app within a 48 hours period
                                        </p>
                                    </li>
                                    <li className="flex gap-3 sm:gap-4">
                                        <div className="flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs sm:text-sm font-bold mt-0.5">3</div>
                                        <p className="text-sm sm:text-base text-gray-700 dark:text-gray-300">
                                            Once ZB/SSB has approved your application then your product will be delivered to the depot you have selected within a 7 day period.
                                        </p>
                                    </li>
                                </ol>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex flex-col gap-3 sm:gap-4">
                                <button
                                    onClick={handleCompleteApplication}
                                    disabled={loading}
                                    className="w-full inline-flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-semibold rounded-xl text-white bg-green-600 hover:bg-green-700 transition-all shadow-lg shadow-green-200 dark:shadow-none hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed group"
                                >
                                    {loading ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Processing...
                                        </>
                                    ) : (
                                        <>
                                            Click here to complete your application
                                            <svg className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Footer />
        </>
    );
};

export default ApplicationSuccess;
