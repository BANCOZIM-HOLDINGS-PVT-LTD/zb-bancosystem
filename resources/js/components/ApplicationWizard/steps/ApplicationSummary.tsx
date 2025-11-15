import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, CheckCircle, User, Building, DollarSign, CreditCard, MapPin, Package } from 'lucide-react';

interface ApplicationData {
    language?: string;
    intent?: string;
    employer: string;
    hasAccount: boolean;
    wantsAccount: boolean;
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
}

interface ApplicationSummaryProps {
    data: ApplicationData;
    onNext: (data: { formId: string; proceedToForm: boolean }) => void;
    onBack: () => void;
    loading?: boolean;
}

const getFormIdByEmployer = (employerId: string, hasAccount: boolean, wantsAccount: boolean) => {
    switch (employerId) {
        case 'government-ssb':
            return 'ssb_account_opening_form.json';
        case 'government-non-ssb':
            return 'ssb_account_opening_form.json'; // Use SSB form for non-SSB government as well
        case 'entrepreneur':
            return 'smes_business_account_opening.json';
        default:
            if (hasAccount) {
                return 'account_holder_loan_application.json';
            } else if (wantsAccount) {
                return 'individual_account_opening.json';
            }
            return 'individual_account_opening.json';
    }
};

const ApplicationSummary: React.FC<ApplicationSummaryProps> = ({ data, onNext, onBack, loading }) => {
    const formId = getFormIdByEmployer(data.employer, data.hasAccount, data.wantsAccount);

    const handleSubmit = () => {
        onNext({
            formId,
            proceedToForm: true
        });
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

    return (
        <div className="space-y-6">
            <div className="text-center">
                <CheckCircle className="mx-auto h-12 w-12 text-emerald-600 mb-4" />
                <h2 className="text-2xl font-semibold mb-2">Application Summary</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Review your application details before submitting
                </p>
            </div>

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
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {data.totalLoanAmount ? 'Total Loan Amount Due' : 'Loan Amount'}
                                </p>
                                <p className="font-medium text-xl text-emerald-600">
                                    ${(data.totalLoanAmount || data.amount)?.toLocaleString()}
                                </p>
                            </div>
                            {data.creditTerm && data.monthlyPayment && (
                            <div className="grid grid-cols-2 gap-4">
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
                )}
            </div>

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
        </div>
    );
};

export default ApplicationSummary;
