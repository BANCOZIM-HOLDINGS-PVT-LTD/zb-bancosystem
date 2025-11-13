import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import ApplicationWizard from '@/components/ApplicationWizard/ApplicationWizard';
import { ApplicationProvider } from '@/contexts/ApplicationContext';
import { ApplicationState } from '@/types/application';

// Mock the error handler hook
vi.mock('@/hooks/use-error-handler', () => ({
    useErrorHandler: () => ({
        handleAsyncError: vi.fn(),
    }),
}));

// Mock fetch
global.fetch = vi.fn();

const mockApplicationState: ApplicationState = {
    session_id: 'test-session-123',
    channel: 'web',
    user_identifier: 'test@example.com',
    current_step: 'language',
    form_data: {},
};

const renderWithProvider = (component: React.ReactElement, initialState?: ApplicationState) => {
    return render(
        <ApplicationProvider initialApplicationState={initialState}>
            {component}
        </ApplicationProvider>
    );
};

describe('ApplicationWizard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        (fetch as any).mockClear();
    });

    it('renders language selection step by default', () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/select your language/i)).toBeInTheDocument();
        expect(screen.getByText(/english/i)).toBeInTheDocument();
        expect(screen.getByText(/shona/i)).toBeInTheDocument();
        expect(screen.getByText(/ndebele/i)).toBeInTheDocument();
    });

    it('allows language selection and proceeds to next step', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />
        );

        // Select English
        const englishButton = screen.getByText(/english/i);
        fireEvent.click(englishButton);

        // Click continue
        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/what would you like to do/i)).toBeInTheDocument();
        });
    });

    it('shows intent selection step after language', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="intent"
                initialData={{ language: 'en' }}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/what would you like to do/i)).toBeInTheDocument();
        expect(screen.getByText(/apply for a loan/i)).toBeInTheDocument();
        expect(screen.getByText(/open an account/i)).toBeInTheDocument();
    });

    it('handles loan application flow', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="intent"
                initialData={{ language: 'en' }}
                sessionId="test-session"
            />
        );

        // Select loan application
        const loanButton = screen.getByText(/apply for a loan/i);
        fireEvent.click(loanButton);

        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/select your employer/i)).toBeInTheDocument();
        });
    });

    it('handles account opening flow', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="intent"
                initialData={{ language: 'en' }}
                sessionId="test-session"
            />
        );

        // Select account opening
        const accountButton = screen.getByText(/open an account/i);
        fireEvent.click(accountButton);

        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/select your employer/i)).toBeInTheDocument();
        });
    });

    it('shows employer selection step', () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="employer"
                initialData={{ 
                    language: 'en',
                    intent: 'loan'
                }}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/select your employer/i)).toBeInTheDocument();
        expect(screen.getByText(/government of zimbabwe/i)).toBeInTheDocument();
        expect(screen.getByText(/large corporate/i)).toBeInTheDocument();
        expect(screen.getByText(/entrepreneur/i)).toBeInTheDocument();
    });

    it('shows product selection for entrepreneurs', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="employer"
                initialData={{ 
                    language: 'en',
                    intent: 'loan'
                }}
                sessionId="test-session"
            />
        );

        // Select entrepreneur
        const entrepreneurButton = screen.getByText(/entrepreneur/i);
        fireEvent.click(entrepreneurButton);

        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/select your business/i)).toBeInTheDocument();
        });
    });

    it('shows account check step for non-entrepreneurs', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="employer"
                initialData={{ 
                    language: 'en',
                    intent: 'loan'
                }}
                sessionId="test-session"
            />
        );

        // Select government
        const govButton = screen.getByText(/government of zimbabwe/i);
        fireEvent.click(govButton);

        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/do you have an existing account/i)).toBeInTheDocument();
        });
    });

    it('handles form step with validation', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="form"
                initialData={{ 
                    language: 'en',
                    intent: 'loan',
                    employer: 'goz-ssb',
                    hasAccount: true
                }}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/application form/i)).toBeInTheDocument();
        
        // Try to continue without filling required fields
        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        await waitFor(() => {
            expect(screen.getByText(/please fill in all required fields/i)).toBeInTheDocument();
        });
    });

    it('shows progress indicator', () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="employer"
                initialData={{ 
                    language: 'en',
                    intent: 'loan'
                }}
                sessionId="test-session"
            />
        );

        // Check for progress steps
        expect(screen.getByText(/language/i)).toBeInTheDocument();
        expect(screen.getByText(/intent/i)).toBeInTheDocument();
        expect(screen.getByText(/employer/i)).toBeInTheDocument();
    });

    it('allows navigation back to previous steps', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="employer"
                initialData={{ 
                    language: 'en',
                    intent: 'loan'
                }}
                sessionId="test-session"
            />
        );

        // Click back button
        const backButton = screen.getByText(/back/i);
        fireEvent.click(backButton);

        await waitFor(() => {
            expect(screen.getByText(/what would you like to do/i)).toBeInTheDocument();
        });
    });

    it('saves state automatically', async () => {
        (fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ success: true }),
        });

        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />,
            mockApplicationState
        );

        // Select a language
        const englishButton = screen.getByText(/english/i);
        fireEvent.click(englishButton);

        // Wait for auto-save
        await waitFor(() => {
            expect(fetch).toHaveBeenCalledWith('/api/states/save', expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                }),
                body: expect.stringContaining('test-session-123'),
            }));
        }, { timeout: 35000 }); // Auto-save interval is 30 seconds
    });

    it('handles network errors gracefully', async () => {
        (fetch as any).mockRejectedValueOnce(new Error('Network error'));

        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />,
            mockApplicationState
        );

        // Select a language to trigger save
        const englishButton = screen.getByText(/english/i);
        fireEvent.click(englishButton);

        // Should not crash the application
        expect(screen.getByText(/english/i)).toBeInTheDocument();
    });

    it('shows loading state during transitions', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />
        );

        const englishButton = screen.getByText(/english/i);
        fireEvent.click(englishButton);

        const continueButton = screen.getByText(/continue/i);
        fireEvent.click(continueButton);

        // Should show loading indicator briefly
        expect(screen.getByText(/loading/i)).toBeInTheDocument();
    });

    it('resumes from saved state', () => {
        const savedState: ApplicationState = {
            session_id: 'test-session-123',
            channel: 'web',
            user_identifier: 'test@example.com',
            current_step: 'employer',
            form_data: {
                language: 'en',
                intent: 'loan',
            },
        };

        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />,
            savedState
        );

        // Should start from the saved step
        expect(screen.getByText(/select your employer/i)).toBeInTheDocument();
    });

    it('handles document upload step', async () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="documents"
                initialData={{ 
                    language: 'en',
                    intent: 'loan',
                    employer: 'goz-ssb',
                    hasAccount: true
                }}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/upload documents/i)).toBeInTheDocument();
        expect(screen.getByText(/national id/i)).toBeInTheDocument();
        expect(screen.getByText(/proof of residence/i)).toBeInTheDocument();
    });

    it('shows completion step with summary', () => {
        renderWithProvider(
            <ApplicationWizard 
                initialStep="completed"
                initialData={{ 
                    language: 'en',
                    intent: 'loan',
                    employer: 'goz-ssb',
                    hasAccount: true,
                    formResponses: {
                        firstName: 'John',
                        lastName: 'Doe',
                        emailAddress: 'john.doe@example.com',
                    }
                }}
                sessionId="test-session"
            />
        );

        expect(screen.getByText(/application completed/i)).toBeInTheDocument();
        expect(screen.getByText(/john doe/i)).toBeInTheDocument();
        expect(screen.getByText(/john.doe@example.com/i)).toBeInTheDocument();
    });

    it('handles responsive design', () => {
        // Mock window.innerWidth
        Object.defineProperty(window, 'innerWidth', {
            writable: true,
            configurable: true,
            value: 768,
        });

        renderWithProvider(
            <ApplicationWizard 
                initialStep="language"
                initialData={{}}
                sessionId="test-session"
            />
        );

        // Should render mobile-friendly layout
        const container = screen.getByTestId('application-wizard');
        expect(container).toHaveClass('mobile-layout');
    });
});
