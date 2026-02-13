import React from 'react';
import AccountHoldersLoanForm from '../forms/AccountHoldersLoanForm';
import SSBLoanForm from '../forms/SSBLoanForm';
import RDCLoanForm from '../forms/RDCLoanForm';
import ZBAccountOpeningForm from '../forms/ZBAccountOpeningForm';
import SMEBusinessForm from '../forms/SMEBusinessForm';
// Import with exact path to fix resolution issues
import PensionerLoanForm from '../forms/PensionerLoanForm';

interface FormStepProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

const FormStep: React.FC<FormStepProps> = ({ data, onNext, onBack, loading }) => {
    // Debug logging to verify correct formId
    console.log('[FormStep] Rendering form with data:', {
        formId: data.formId,
        hasAccount: data.hasAccount,
        wantsAccount: data.wantsAccount,
        employer: data.employer,
        data: data // Full data debug
    });

    // Determine which form to show based on the form ID from ApplicationSummary
    const getFormComponent = () => {
        switch (data.formId) {
            case 'account_holder_loan_application.json':
                return (
                    <AccountHoldersLoanForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'ssb_account_opening_form.json':
                return (
                    <SSBLoanForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'rdc_loan_application.json':
                return (
                    <RDCLoanForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'individual_account_opening.json':
                return (
                    <ZBAccountOpeningForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'smes_business_account_opening.json':
                return (
                    <SMEBusinessForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'pensioner_loan_application.json':
                return (
                    <PensionerLoanForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            case 'pensioners_loan_account.json':
                return (
                    <AccountHoldersLoanForm
                        data={data}
                        onNext={onNext}
                        onBack={onBack}
                        loading={loading}
                    />
                );

            default:
                return (
                    <div className="text-center space-y-4">
                        <h2 className="text-2xl font-semibold">Form Not Found</h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            The requested form ({data.formId}) could not be found.
                        </p>
                        <button
                            onClick={onBack}
                            className="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
                        >
                            Go Back
                        </button>
                    </div>
                );
        }
    };

    return (
        <div className="w-full">
            {getFormComponent()}
        </div>
    );
};

export default FormStep;