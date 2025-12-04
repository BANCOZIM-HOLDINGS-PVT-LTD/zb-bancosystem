import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CheckCircle, User, Building, DollarSign, CreditCard, MapPin, Package, Monitor, GraduationCap } from 'lucide-react';

interface ApplicationData {
    language?: string;
    intent?: string;
    employer: string;
    hasAccount: boolean;
    wantsAccount: boolean;
    accountType?: string;
    specificEmployer?: string;
    business?: string;
    category?: string;
    scale?: string;
    amount?: number;
    creditTerm?: number;
    monthlyPayment?: number;
    employerName?: string;
    creditType?: string;
    idNumber?: string;
    deliveryDepot?: string;
    deliveryAddress?: string;
    product?: string;
    totalLoanAmount?: number;
    includesMESystem?: boolean;
    meSystemFee?: number;
    includesTraining?: boolean;
    trainingFee?: number;
    // New loan amount fields
    netLoan?: number;
    grossLoan?: number;
    bankAdminFee?: number;
    sellingPrice?: number;
    loanAmount?: number;
    firstPaymentDate?: string;
    lastPaymentDate?: string;
    // Zimparks booking details
    bookingDetails?: {
        startDate?: string;
        endDate?: string;
        destination?: string;
    };
    destinationName?: string;
}

interface ApplicationSummaryProps {
    data: ApplicationData;
    onNext: (data: { formId: string; proceedToForm: boolean; hasAccount: boolean; wantsAccount: boolean; accountType?: string }) => void;
    onBack: () => void;
    loading?: boolean;
}

const getFormIdByEmployer = (employerId: string, hasAccount: boolean, wantsAccount: boolean) => {
    switch (employerId) {
        case 'government-ssb':
            // SSB employers always use SSB form
            return 'ssb_account_opening_form.json';
        case 'entrepreneur':
            // Entrepreneurs use SME form
            return 'smes_business_account_opening.json';
        default:
            // For all other employers (non-SSB)
            if (hasAccount) {
                // User has ZB Bank account -> use Account Holders form
                return 'account_holder_loan_application.json';
            } else if (wantsAccount) {
                // User wants to open ZB Bank account -> use ZB Account Opening form
                return 'individual_account_opening.json';
            }
            // Fallback (shouldn't reach here due to validation in AccountVerification)
            return 'individual_account_opening.json';
    }
};

const ApplicationSummary: React.FC<ApplicationSummaryProps> = ({ data, onNext, onBack, loading }) => {
    const formId = getFormIdByEmployer(data.employer, data.hasAccount, data.wantsAccount);

    // Debug logging to help trace any issues
    console.log('[ApplicationSummary] Form routing data:', {
        employer: data.employer,
        hasAccount: data.hasAccount,
        wantsAccount: data.wantsAccount,
        accountType: data.accountType,
        selectedFormId: formId
    });

    const handleSubmit = () => {
        const submissionData = {
            formId,
            proceedToForm: true,
            hasAccount: data.hasAccount,
            wantsAccount: data.wantsAccount,
            accountType: data.accountType
        };

        console.log('[ApplicationSummary] Submitting with data:', submissionData);

        // Explicitly preserve hasAccount and wantsAccount flags along with formId
        onNext(submissionData);
    };

    const getEmployerName = (employerId: string) => {
        const employerMap: { [key: string]: string } = {
            'government-ssb': 'Government of Zimbabwe - SSB',
            'government-non-ssb': 'Government of Zimbabwe - Non SSB',
            'municipality-rdc': 'Municipality and Rural District Council',
            'parastatal': 'Parastatal',
            'state-university': 'State University',
            'mission-school': 'Mission School',
            'private-school': 'Private School',
            'small-company': 'Small Company (less than 100 employees)',
            'large-company': 'Large Company (more than 100 employees)',
            'ngo-nonprofit': "N.G.O's and Non Profit Organisation",
            'entrepreneur': 'I am an Entrepreneur',
            'other': 'Other'
        };
        return employerMap[employerId] || data.employerName || employerId;
    };

    const getFormTypeName = (formId: string) => {
        const formTypeMap: { [key: string]: string } = {
            'account_holder_loan_application.json': 'Account Holder Loan Application',
            'ssb_account_opening_form.json': 'SSB Loan Application',
            'individual_account_opening.json': 'New ZB Account Opening',
            'smes_business_account_opening.json': 'SME Business Account Opening',
            'pensioners_loan_account.json': 'Pensioners Loan Account'
        };
        return formTypeMap[formId] || 'Application Form';
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <CheckCircle className="mx-auto h-12 w-12 text-emerald-600 mb-4" />
                <h2 className="text-2xl font-semibold mb-2">Application Summary</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Review your application details before submitting
                </p>
            </div>

            {/* Form Type Information */}
            <Card className="p-4 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800">
                <div className="flex items-start gap-3">
                    <div className="p-2 bg-emerald-100 dark:bg-emerald-900/40 rounded-full">
                        <CheckCircle className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div className="flex-1">
                        <h3 className="font-semibold text-emerald-900 dark:text-emerald-100 mb-1">
                            Form Type: {getFormTypeName(formId)}
                        </h3>
                        <p className="text-sm text-emerald-700 dark:text-emerald-300">
                            {data.hasAccount && !data.wantsAccount && 'You will complete the Account Holder Loan Application form.'}
                            {data.wantsAccount && 'You will complete the New Account Opening form.'}
                            {data.employer === 'government-ssb' && 'You will complete the SSB Loan Application form.'}
                            {data.employer === 'entrepreneur' && 'You will complete the SME Business Account Opening form.'}
                        </p>
                    </div>
                </div>
            </Card>

            <div className="grid gap-6 sm:grid-cols-2">
                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <User className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Personal Information</h3>
                    </div>
                    <div className="space-y-3">
                        {data.idNumber && (
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">ID Number</p>
                                <p className="font-medium">{data.idNumber}</p>
                            </div>
                        )}
                        <div>
                            <p className="text-sm text-gray-500 dark:text-gray-400">Application Type</p>
                            <p className="font-medium">
                                {data.intent === 'hirePurchase' ? 'Hire Purchase Credit' :
                                    data.intent === 'microBiz' ? 'Micro Biz Loan' : data.intent}
                            </p>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center mb-4">
                        <Building className="h-6 w-6 text-emerald-600 mr-3" />
                        <h3 className="text-lg font-semibold">Employment</h3>
                    </div>
                    <div className="space-y-3">
                        <div>
                            <p className="text-sm text-gray-500 dark:text-gray-400">Employer</p>
                            <p className="font-medium">{getEmployerName(data.employer)}</p>
                        </div>
                        {data.specificEmployer && (
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Organization</p>
                                <p className="font-medium">{data.specificEmployer}</p>
                            </div>
                        )}
                    </div>
                </Card>

                {data.product && (
                    <Card className="p-6">
                        <div className="flex items-center mb-4">
                            <Package className="h-6 w-6 text-emerald-600 mr-3" />
                            <h3 className="text-lg font-semibold">Product Details</h3>
                        </div>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Selected Product</p>
                                <p className="font-medium">{data.product}</p>
                            </div>
                            {data.category && (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Category</p>
                                    <p className="font-medium">{data.category}</p>
                                </div>
                            )}
                        </div>
                    </Card>
                )}

                {data.business && (
                    <Card className="p-6">
                        <div className="flex items-center mb-4">
                            <DollarSign className="h-6 w-6 text-emerald-600 mr-3" />
                            <h3 className="text-lg font-semibold">
                                {data.intent === 'microBiz' ? 'Business Category' : 'Product Category'}
                            </h3>
                        </div>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Category</p>
                                <p className="font-medium">{data.category}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Business Type</p>
                                <p className="font-medium">{data.business}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Scale</p>
                                <p className="font-medium">{data.scale}</p>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Booking Information for Zimparks */}
                {data.bookingDetails && (
                    <Card className="p-6">
                        <div className="flex items-center mb-4">
                            <MapPin className="h-6 w-6 text-emerald-600 mr-3" />
                            <h3 className="text-lg font-semibold">Booking Information</h3>
                        </div>
                        <div className="space-y-3">
                            {data.bookingDetails.destination && (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Destination</p>
                                    <p className="font-medium">{data.bookingDetails.destination}</p>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                {data.bookingDetails.startDate && (
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Check-in</p>
                                        <p className="font-medium">{new Date(data.bookingDetails.startDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                )}
                                {data.bookingDetails.endDate && (
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Check-out</p>
                                        <p className="font-medium">{new Date(data.bookingDetails.endDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </Card>
                )}

                {(data.deliveryDepot || data.deliveryAddress) && (
                    <Card className="p-6">
                        <div className="flex items-center mb-4">
                            <MapPin className="h-6 w-6 text-emerald-600 mr-3" />
                            <h3 className="text-lg font-semibold">Delivery Information</h3>
                        </div>
                        <div className="space-y-3">
                            {data.deliveryDepot && (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Delivery Depot</p>
                                    <p className="font-medium">{data.deliveryDepot}</p>
                                </div>
                            )}
                            {data.deliveryAddress && (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Delivery Address</p>
                                    <p className="font-medium">{data.deliveryAddress}</p>
                                </div>
                            )}
                        </div>
                    </Card>
                )}

                {(data.amount || data.totalLoanAmount) && (
                    <Card className="p-6">
                        <div className="flex items-center mb-4">
                            <CreditCard className="h-6 w-6 text-emerald-600 mr-3" />
                            <h3 className="text-lg font-semibold">Credit Terms</h3>
                        </div>
                        <div className="space-y-3">
                            {data.creditType && (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Credit Type</p>
                                    <p className="font-medium">
                                        {data.creditType === 'ZDC' ? 'Zero Deposit Credit' :
                                            data.creditType === 'PDC' ? 'Paid Deposit Credit' :
                                                data.creditType}
                                    </p>
                                </div>
                            )}

                            {/* Loan Amount Breakdown */}
                            <div className="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                <p className="text-sm font-medium text-gray-700 dark:text-gray-300">Loan Amount Breakdown:</p>

                                {/* Base Product Amount */}
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Product/Business</span>
                                    <span className="font-medium">
                                        ${(() => {
                                            const netLoan = data.netLoan || data.amount || 0;
                                            const meSystem = data.meSystemFee || 0;
                                            const training = data.trainingFee || 0;
                                            const base = netLoan - meSystem - training;
                                            return base.toLocaleString();
                                        })()}
                                    </span>
                                </div>

                                {/* M&E System Fee */}
                                {data.includesMESystem && data.meSystemFee && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                            <Monitor className="h-3 w-3" />
                                            M&E System (10%)
                                        </span>
                                        <span className="font-medium text-emerald-600">
                                            +${data.meSystemFee.toLocaleString()}
                                        </span>
                                    </div>
                                )}

                                {/* Training Fee */}
                                {data.includesTraining && data.trainingFee && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                            <GraduationCap className="h-3 w-3" />
                                            Training (5.5%)
                                        </span>
                                        <span className="font-medium text-purple-600">
                                            +${data.trainingFee.toFixed(2)}
                                        </span>
                                    </div>
                                )}

                                {/* Net Loan Line */}
                                <div className="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Net Loan (selling price)
                                    </span>
                                    <span className="font-bold text-lg text-gray-900 dark:text-gray-100">
                                        ${(data.netLoan || data.amount)?.toLocaleString()}
                                    </span>
                                </div>

                                {/* Bank Admin Fee */}
                                {data.bankAdminFee && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600 dark:text-gray-400">Bank Admin Fee (6%)</span>
                                        <span className="font-medium text-blue-600">
                                            +${data.bankAdminFee.toLocaleString()}
                                        </span>
                                    </div>
                                )}

                                {/* Gross Loan Line */}
                                <div className="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Gross Loan (incl. 6% admin fee)
                                    </span>
                                    <span className="font-bold text-xl text-emerald-600">
                                        ${(data.grossLoan || data.loanAmount || data.amount)?.toLocaleString()}
                                    </span>
                                </div>
                            </div>

                            {data.creditTerm && data.monthlyPayment && (
                                <div className="grid grid-cols-2 gap-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Term</p>
                                        <p className="font-medium">{data.creditTerm} months</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Monthly Payment</p>
                                        <p className="font-medium">${data.monthlyPayment}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>
                )
                }
            </div >

            <div className="flex flex-col items-end gap-3 pt-4">
                <div className="flex justify-between w-full">
                    <Button
                        variant="outline"
                        onClick={onBack}
                        disabled={loading}
                        className="flex items-center gap-2"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back
                    </Button>

                    <Button
                        onClick={handleSubmit}
                        disabled={loading}
                        className="bg-emerald-600 hover:bg-emerald-700 px-8"
                    >
                        {loading ? 'Loading...' : 'Proceed to Form'}
                    </Button>
                </div>

                <p className="text-sm text-gray-600 dark:text-gray-400">
                    By clicking the "Proceed to Form" button, you agree to our{' '}
                    <a
                        href="/terms-and-conditions"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline text-emerald-600 hover:text-emerald-700 dark:text-emerald-500 dark:hover:text-emerald-400"
                    >
                        terms and conditions
                    </a>
                </p>
            </div>
        </div >
    );
};

export default ApplicationSummary;
