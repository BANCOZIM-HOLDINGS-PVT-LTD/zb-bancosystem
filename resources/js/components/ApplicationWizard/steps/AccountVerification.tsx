import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CheckCircle, XCircle, AlertCircle, X } from 'lucide-react';

interface AccountVerificationProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const AccountVerification: React.FC<AccountVerificationProps> = ({ data, onNext, onBack, loading }) => {
    const [currentStep, setCurrentStep] = useState<'account-check' | 'want-account'>('account-check');
    const [showAccountRequiredModal, setShowAccountRequiredModal] = useState(false);
    const [showServicesUnavailableModal, setShowServicesUnavailableModal] = useState(false);

    // Check if SSB employer (should skip this step)
    const isSSB = data.employer === 'government-ssb';
    const isEntrepreneur = data.employer === 'entrepreneur';

    // Skip this step for SSB employees using useEffect to avoid render issues
    useEffect(() => {
        if (isSSB) {
            onNext({
                hasAccount: true,
                accountType: 'SSB',
                skipAccountCheck: true
            });
        }
    }, [isSSB]); // Remove onNext from dependencies to prevent infinite loop

    // Don't render anything for SSB employees while transitioning
    if (isSSB) {
        return null;
    }

    const handleAccountResponse = (hasAccount: boolean) => {
        if (hasAccount) {
            onNext({
                hasAccount: true,
                accountType: isEntrepreneur ? 'SME Transaction Account' : 'ZB Bank Account'
            });
        } else {
            setCurrentStep('want-account');
        }
    };

    const handleWantAccountResponse = (wantsAccount: boolean) => {
        if (wantsAccount) {
            onNext({
                hasAccount: false,
                wantsAccount: true,
                accountType: isEntrepreneur ? 'SME Transaction Account' : 'ZB Bank Account'
            });
        } else {
            // Show services unavailable modal for non-SSB, non-ZB account holders
            setShowServicesUnavailableModal(true);
        }
    };

    const accountType = isEntrepreneur ? 'SME Transaction Account' : 'ZB Bank account';

    return (
        <div className="space-y-6">
            {/* Services Unavailable Modal */}
            {showServicesUnavailableModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md mx-4 relative">
                        <button
                            onClick={() => setShowServicesUnavailableModal(false)}
                            className="absolute right-4 top-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="text-center">
                            <AlertCircle className="mx-auto h-16 w-16 text-red-500 mb-4" />
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Services Currently Unavailable
                            </h3>
                            <p className="text-gray-600 dark:text-gray-300 mb-6">
                                Services are currently unavailable for non-SSB and non-ZB Bank account holders.
                            </p>
                            <div className="space-y-3">
                                <Button
                                    onClick={() => {
                                        setShowServicesUnavailableModal(false);
                                        setCurrentStep('want-account');
                                    }}
                                    className="w-full bg-emerald-600 hover:bg-emerald-700"
                                >
                                    I'll open a ZB Bank account
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setShowServicesUnavailableModal(false);
                                        onBack();
                                    }}
                                    className="w-full"
                                >
                                    Go Back
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {currentStep === 'account-check' && (
                <>
                    <div className="text-center">
                        <AlertCircle className="mx-auto h-12 w-12 text-blue-600 mb-4" />
                        <h2 className="text-2xl font-semibold mb-2">Account Check</h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Do you have a {accountType}?
                        </p>
                    </div>
                    
                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl mx-auto">
                        <Card
                            className="cursor-pointer p-8 text-center transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:hover:bg-emerald-950/20"
                            onClick={() => handleAccountResponse(true)}
                        >
                            <CheckCircle className="mx-auto h-12 w-12 text-emerald-600 mb-4" />
                            <h3 className="text-lg font-semibold mb-2">Yes, I have an account</h3>
                            <p className="text-sm text-gray-500">
                                I have an existing {accountType}
                            </p>
                        </Card>
                        
                        <Card
                            className="cursor-pointer p-8 text-center transition-all hover:border-red-600 hover:bg-red-50 hover:shadow-lg dark:hover:bg-red-950/20"
                            onClick={() => handleAccountResponse(false)}
                        >
                            <XCircle className="mx-auto h-12 w-12 text-red-600 mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No, I don't have an account</h3>
                            <p className="text-sm text-gray-500">
                                I need to open a new account
                            </p>
                        </Card>
                    </div>
                </>
            )}

            {currentStep === 'want-account' && (
                <>
                    <div className="text-center">
                        <AlertCircle className="mx-auto h-12 w-12 text-orange-600 mb-4" />
                        <h2 className="text-2xl font-semibold mb-2">Open Account</h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Would you like to open a {accountType}?
                        </p>
                        <p className="text-sm text-gray-500 mt-2">
                            This is required to proceed with your loan application
                        </p>
                    </div>
                    
                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl mx-auto">
                        <Card
                            className="cursor-pointer p-8 text-center transition-all hover:border-emerald-600 hover:bg-emerald-50 hover:shadow-lg dark:hover:bg-emerald-950/20"
                            onClick={() => handleWantAccountResponse(true)}
                        >
                            <CheckCircle className="mx-auto h-12 w-12 text-emerald-600 mb-4" />
                            <h3 className="text-lg font-semibold mb-2">Yes, open an account</h3>
                            <p className="text-sm text-gray-500">
                                I want to open a {accountType}
                            </p>
                        </Card>
                        
                        <Card
                            className="cursor-pointer p-8 text-center transition-all hover:border-red-600 hover:bg-red-50 hover:shadow-lg dark:hover:bg-red-950/20"
                            onClick={() => handleWantAccountResponse(false)}
                        >
                            <XCircle className="mx-auto h-12 w-12 text-red-600 mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No, not now</h3>
                            <p className="text-sm text-gray-500">
                                I don't want to open an account
                            </p>
                        </Card>
                    </div>
                </>
            )}
            
            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={currentStep === 'want-account' ? () => setCurrentStep('account-check') : onBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>
        </div>
    );
};

export default AccountVerification;