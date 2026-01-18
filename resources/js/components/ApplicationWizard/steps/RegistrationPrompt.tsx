import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { UserPlus, ChevronLeft, LogIn, AlertCircle, Package, MapPin } from 'lucide-react';

interface RegistrationPromptProps {
    data: any;
    onBack: () => void;
    sessionId: string;
}

interface PendingApplication {
    reference_code: string;
    status: string;
    created_at: string;
}

const RegistrationPrompt: React.FC<RegistrationPromptProps> = ({ data, onBack, sessionId }) => {
    const [showPendingModal, setShowPendingModal] = useState(false);
    const [pendingApp, setPendingApp] = useState<PendingApplication | null>(null);
    const [checkingPending, setCheckingPending] = useState(false);

    // Check for pending applications after component mounts (in case user is already authenticated)
    useEffect(() => {
        const checkPending = async () => {
            try {
                const response = await fetch('/api/user/pending-applications');
                const data = await response.json();

                if (!data.can_apply && data.has_pending) {
                    setPendingApp(data.pending_application);
                    setShowPendingModal(true);
                }
            } catch (error) {
                console.error('Failed to check pending applications:', error);
            }
        };

        // Only check if we detect the user might be authenticated
        // This happens when they return from login
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('resume') === 'true') {
            checkPending();
        }
    }, []);

    const handleRegister = () => {
        // Save current wizard state to localStorage
        const wizardState = {
            sessionId,
            data,
            returnUrl: window.location.pathname + window.location.search
        };
        localStorage.setItem('pendingWizardState', JSON.stringify(wizardState));

        // Redirect to registration
        const returnUrl = encodeURIComponent(`/application?session=${sessionId}&resume=true&check_pending=true`);
        router.visit(`/client/register?returnUrl=${returnUrl}`);
    };

    const handleLogin = () => {
        // Save current wizard state to localStorage
        const wizardState = {
            sessionId,
            data,
            returnUrl: window.location.pathname + window.location.search
        };
        localStorage.setItem('pendingWizardState', JSON.stringify(wizardState));

        // Redirect to login
        const returnUrl = encodeURIComponent(`/application?session=${sessionId}&resume=true&check_pending=true`);
        router.visit(`/client/login?returnUrl=${returnUrl}`);
    };

    const handleTrackStatus = () => {
        if (pendingApp) {
            window.location.href = `/application/status?ref=${pendingApp.reference_code}`;
        }
    };

    const handleTrackDelivery = () => {
        if (pendingApp) {
            window.location.href = `/delivery/tracking?ref=${pendingApp.reference_code}`;
        }
    };

    const handleBackToWelcome = () => {
        window.location.href = '/';
    };

    if (showPendingModal && pendingApp) {
        return (
            <div className="space-y-6">
                <Card className="p-8 max-w-2xl mx-auto border-2 border-amber-200 dark:border-amber-800">
                    <div className="text-center mb-6">
                        <AlertCircle className="mx-auto h-16 w-16 text-amber-600 mb-4" />
                        <h2 className="text-2xl font-semibold mb-2 text-gray-900 dark:text-gray-100">
                            Existing Application Found
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            You have an existing application that hasn't been delivered yet
                        </p>
                    </div>

                    <div className="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 mb-6">
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="font-medium text-gray-700 dark:text-gray-300">Reference Code:</span>
                                <span className="font-semibold text-amber-700 dark:text-amber-400">
                                    {pendingApp.reference_code}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                <span className="font-semibold capitalize text-gray-900 dark:text-gray-100">
                                    {pendingApp.status}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="font-medium text-gray-700 dark:text-gray-300">Applied On:</span>
                                <span className="text-gray-900 dark:text-gray-100">
                                    {new Date(pendingApp.created_at).toLocaleDateString()}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <p className="text-sm text-blue-900 dark:text-blue-200">
                            <strong>Note:</strong> You can only start a new application after your current order has been delivered successfully.
                            Please track your application status or delivery below.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <Button
                            onClick={handleTrackStatus}
                            className="w-full bg-emerald-600 hover:bg-emerald-700 flex items-center justify-center gap-2"
                        >
                            <Package className="h-5 w-5" />
                            Track Application Status
                        </Button>
                        <Button
                            onClick={handleTrackDelivery}
                            variant="outline"
                            className="w-full flex items-center justify-center gap-2"
                        >
                            <MapPin className="h-5 w-5" />
                            Track Delivery
                        </Button>
                    </div>

                    <Button
                        onClick={handleBackToWelcome}
                        variant="ghost"
                        className="w-full"
                    >
                        Go Back to Home
                    </Button>
                </Card>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="text-center">
                <UserPlus className="mx-auto h-16 w-16 text-emerald-600 mb-4" />
                <h2 className="text-2xl font-semibold mb-2">Register or Login to Continue</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    To proceed with your application, please login or create an account
                </p>
            </div>

            <Card className="p-8 max-w-md mx-auto">
                <div className="space-y-4">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        With an account you can:
                    </p>
                    <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Complete your hire purchase application</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Track your application status</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Monitor your delivery</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-emerald-600 mr-2">✓</span>
                            <span>Access your application history</span>
                        </li>
                    </ul>

                    <div className="pt-4 space-y-3">
                        <Button
                            onClick={handleRegister}
                            className="w-full bg-emerald-600 hover:bg-emerald-700 flex items-center justify-center gap-2"
                        >
                            <UserPlus className="h-5 w-5" />
                            Create New Account
                        </Button>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t border-gray-300 dark:border-gray-600" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-white dark:bg-gray-900 px-2 text-gray-500">
                                    Or
                                </span>
                            </div>
                        </div>

                        <Button
                            onClick={handleLogin}
                            variant="outline"
                            className="w-full flex items-center justify-center gap-2"
                        >
                            <LogIn className="h-5 w-5" />
                            Login with ID Number
                        </Button>
                    </div>
                </div>
            </Card>

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={onBack}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
        </div>
    );
};

export default RegistrationPrompt;

