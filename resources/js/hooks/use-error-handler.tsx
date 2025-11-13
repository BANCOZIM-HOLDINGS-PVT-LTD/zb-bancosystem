import { useCallback } from 'react';

interface ErrorDetails {
    message: string;
    stack?: string;
    component?: string;
    action?: string;
    metadata?: Record<string, any>;
}

export function useErrorHandler() {
    const logError = useCallback(async (error: Error | ErrorDetails, context?: string) => {
        const errorData = {
            error: typeof error === 'object' && 'message' in error ? {
                message: error.message,
                stack: error.stack,
                name: (error as Error).name,
            } : error,
            context,
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString(),
        };

        // Log to console in development
        if (process.env.NODE_ENV === 'development') {
            console.error('Error logged:', errorData);
        }

        try {
            await fetch('/api/client-errors', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(errorData),
            });
        } catch (logError) {
            console.error('Failed to log error to server:', logError);
        }
    }, []);

    const handleError = useCallback((error: Error, context?: string) => {
        logError(error, context);
        
        // You could also show a toast notification here
        // or trigger other error handling logic
    }, [logError]);

    const handleAsyncError = useCallback(async (asyncFn: () => Promise<any>, context?: string) => {
        try {
            return await asyncFn();
        } catch (error) {
            handleError(error as Error, context);
            throw error; // Re-throw so calling code can handle it
        }
    }, [handleError]);

    return {
        logError,
        handleError,
        handleAsyncError,
    };
}
