import { Head } from '@inertiajs/react';
import ApplicationWizard from '@/components/ApplicationWizard/ApplicationWizard';
import ErrorBoundary from '@/components/ErrorBoundary';
import { ApplicationProvider } from '@/contexts/ApplicationContext';
import { ApplicationState } from '@/types/application';

interface ApplicationWizardPageProps {
    initialStep?: string;
    initialData?: any;
    sessionId?: string;
    applicationState?: ApplicationState;
}

export default function ApplicationWizardPage({
    initialStep = 'language',
    initialData = {},
    sessionId,
    applicationState
}: ApplicationWizardPageProps) {
    return (
        <>
            <Head title="BancoSystem - Application" />
            <ErrorBoundary>
                <ApplicationProvider
                    initialApplicationState={applicationState}
                    autoSaveInterval={30000}
                >
                    <ApplicationWizard
                        initialStep={initialStep}
                        initialData={initialData}
                        sessionId={sessionId}
                    />
                </ApplicationProvider>
            </ErrorBoundary>
        </>
    );
}