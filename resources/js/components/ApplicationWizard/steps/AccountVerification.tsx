
import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CheckCircle, XCircle, AlertCircle, X, Building2 } from 'lucide-react';
import { COUNCILS } from '../data/councils';


interface AccountVerificationProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const AccountVerification: React.FC<AccountVerificationProps> = ({ data, onNext, onBack, loading }) => {
    const [currentStep, setCurrentStep] = useState<'account-check' | 'want-account' | 'council-check'>('account-check');
    const [showAccountRequiredModal, setShowAccountRequiredModal] = useState(false);
    const [showServicesUnavailableModal, setShowServicesUnavailableModal] = useState(false);
    const [selectedCouncil, setSelectedCouncil] = useState('');

    // Check if SSB employer (should skip this step)
    const isSSB = data.employer === 'government-ssb';
    const isEntrepreneur = data.employer === 'entrepreneur';
    const isRdcOrMunicipality = data.employer === 'municipality' || data.employer === 'rural-district-council';

    // Skip this step for SSB employees using useEffect to avoid render issues
    useEffect(() => {
        if (isSSB) {
            // Prevent infinite loop if data is already set
            if (data.hasAccount === true && data.accountType === 'SSB' && data.skipAccountCheck === true) {
                return;
            }

            onNext({
                hasAccount: true,
                accountType: 'SSB',
                skipAccountCheck: true
            });
        }
    }, [isSSB, onNext, data.hasAccount, data.accountType, data.skipAccountCheck]); // Added onNext to dependencies for correctness, though it's stable

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
            if (isRdcOrMunicipality) {
                setCurrentStep('council-check');
            } else {
                setCurrentStep('want-account');
            }
        }
    };

    const handleCouncilSelect = (council: string) => {
        onNext({
            hasAccount: false,
            isRdcCouncilEmployee: true,
            accountType: 'RDC Loan Application',
            specificEmployer: council,
            employerName: council // Use the selected council as the employer name
        });
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
                                We are unable to assist you for the time being
                            </h3>
                            <p className="text-gray-600 dark:text-gray-300 mb-6">
                                This is because services are limited to ssb and zb account holders only. Options available:
                            </p>
                            <div className="space-y-3">
                                <Button
                                    onClick={() => {
                                        setShowServicesUnavailableModal(false);
                                        setCurrentStep('want-account');
                                    }}
                                    className="w-full bg-emerald-600 hover:bg-emerald-700"
                                >
                                    I have reconsidered opening a ZB account
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setShowServicesUnavailableModal(false);
                                        window.location.href = '/';
                                    }}
                                    className="w-full"
                                >
                                    I have reconsidered paying with cash
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setShowServicesUnavailableModal(false);
                                        alert('Thank you for your interest. We hope to serve you in the future. Goodbye!');
                                        window.location.href = '/';
                                    }}
                                    className="w-full"
                                >
                                    None of the above
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

            {currentStep === 'council-check' && (
                <div className="space-y-6">
                    <div className="text-center">
                        <Building2 className="mx-auto h-12 w-12 text-blue-600 mb-4" />
                        <h2 className="text-2xl font-semibold mb-2">Which council listed below do you work for?</h2>
                        {/* Subtext removed as per request */}
                    </div>

                    <Card className="p-6 max-h-[60vh] overflow-y-auto">
                        <div className="grid gap-2">
                            {COUNCILS.map((council) => (
                                <button
                                    key={council}
                                    onClick={() => handleCouncilSelect(council)}
                                    className="w-full text-left p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-emerald-50 hover:border-emerald-200 dark:hover:bg-emerald-900/20 transition-all text-gray-700 dark:text-gray-200 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium"
                                >
                                    {council}
                                </button>
                            ))}
                        </div>
                    </Card>

                    <div className="text-center">
                        <Button
                            variant="outline"
                            onClick={() => setCurrentStep('want-account')}
                            className="w-full sm:w-auto min-w-[200px]"
                        >
                            None of the above
                        </Button>
                        <p className="mt-2 text-sm text-gray-500">
                            Select "None of the above" if you don't work for any of the listed councils
                        </p>
                    </div>
                </div>
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