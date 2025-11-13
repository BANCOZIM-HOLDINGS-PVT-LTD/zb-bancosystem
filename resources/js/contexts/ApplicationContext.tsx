import React, { createContext, useContext, useReducer, useCallback, useEffect } from 'react';
import { ApplicationState, FormData, ApplicationStep, ApiResponse } from '@/types/application';
import { useErrorHandler } from '@/hooks/use-error-handler';

// Action Types
type ApplicationAction =
    | { type: 'SET_APPLICATION_STATE'; payload: ApplicationState }
    | { type: 'UPDATE_FORM_DATA'; payload: Partial<FormData> }
    | { type: 'SET_CURRENT_STEP'; payload: ApplicationStep }
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_ERROR'; payload: string | null }
    | { type: 'SET_SAVING'; payload: boolean }
    | { type: 'SET_LAST_SAVED'; payload: string }
    | { type: 'RESET_APPLICATION' };

// Context State
interface ApplicationContextState {
    applicationState: ApplicationState | null;
    isLoading: boolean;
    isSaving: boolean;
    error: string | null;
    lastSaved: string | null;
}

// Context Actions
interface ApplicationContextActions {
    updateFormData: (data: Partial<FormData>) => void;
    setCurrentStep: (step: ApplicationStep) => void;
    saveApplicationState: () => Promise<void>;
    loadApplicationState: (sessionId: string) => Promise<void>;
    resetApplication: () => void;
    setError: (error: string | null) => void;
    clearError: () => void;
}

type ApplicationContextType = ApplicationContextState & ApplicationContextActions;

// Initial State
const initialState: ApplicationContextState = {
    applicationState: null,
    isLoading: false,
    isSaving: false,
    error: null,
    lastSaved: null,
};

// Reducer
function applicationReducer(state: ApplicationContextState, action: ApplicationAction): ApplicationContextState {
    switch (action.type) {
        case 'SET_APPLICATION_STATE':
            return {
                ...state,
                applicationState: action.payload,
                isLoading: false,
                error: null,
            };

        case 'UPDATE_FORM_DATA':
            if (!state.applicationState) return state;
            
            return {
                ...state,
                applicationState: {
                    ...state.applicationState,
                    form_data: {
                        ...state.applicationState.form_data,
                        ...action.payload,
                    },
                },
            };

        case 'SET_CURRENT_STEP':
            if (!state.applicationState) return state;
            
            return {
                ...state,
                applicationState: {
                    ...state.applicationState,
                    current_step: action.payload,
                },
            };

        case 'SET_LOADING':
            return {
                ...state,
                isLoading: action.payload,
            };

        case 'SET_ERROR':
            return {
                ...state,
                error: action.payload,
                isLoading: false,
                isSaving: false,
            };

        case 'SET_SAVING':
            return {
                ...state,
                isSaving: action.payload,
            };

        case 'SET_LAST_SAVED':
            return {
                ...state,
                lastSaved: action.payload,
                isSaving: false,
            };

        case 'RESET_APPLICATION':
            return initialState;

        default:
            return state;
    }
}

// Context
const ApplicationContext = createContext<ApplicationContextType | undefined>(undefined);

// Provider Props
interface ApplicationProviderProps {
    children: React.ReactNode;
    initialApplicationState?: ApplicationState;
    autoSaveInterval?: number;
}

// Provider Component
export function ApplicationProvider({ 
    children, 
    initialApplicationState,
    autoSaveInterval = 30000 // 30 seconds
}: ApplicationProviderProps) {
    const [state, dispatch] = useReducer(applicationReducer, {
        ...initialState,
        applicationState: initialApplicationState || null,
    });

    const { handleAsyncError } = useErrorHandler();

    // Auto-save effect
    useEffect(() => {
        if (!state.applicationState || state.isSaving) return;

        const autoSaveTimer = setInterval(() => {
            if (state.applicationState && !state.isSaving) {
                saveApplicationState();
            }
        }, autoSaveInterval);

        return () => clearInterval(autoSaveTimer);
    }, [state.applicationState, state.isSaving, autoSaveInterval]);

    // Actions
    const updateFormData = useCallback((data: Partial<FormData>) => {
        dispatch({ type: 'UPDATE_FORM_DATA', payload: data });
    }, []);

    const setCurrentStep = useCallback((step: ApplicationStep) => {
        dispatch({ type: 'SET_CURRENT_STEP', payload: step });
    }, []);

    const saveApplicationState = useCallback(async () => {
        if (!state.applicationState || state.isSaving) return;

        dispatch({ type: 'SET_SAVING', payload: true });

        try {
            const response = await fetch('/api/states/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: state.applicationState.session_id,
                    channel: state.applicationState.channel,
                    user_identifier: state.applicationState.user_identifier,
                    current_step: state.applicationState.current_step,
                    form_data: state.applicationState.form_data,
                    metadata: {
                        ...state.applicationState.metadata,
                        last_activity: new Date().toISOString(),
                    },
                }),
            });

            const result: ApiResponse = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to save application state');
            }

            dispatch({ type: 'SET_LAST_SAVED', payload: new Date().toISOString() });

        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Failed to save application state';
            dispatch({ type: 'SET_ERROR', payload: errorMessage });
            handleAsyncError(async () => { throw error; }, 'ApplicationContext.saveApplicationState');
        }
    }, [state.applicationState, state.isSaving, handleAsyncError]);

    const loadApplicationState = useCallback(async (sessionId: string) => {
        dispatch({ type: 'SET_LOADING', payload: true });

        try {
            const response = await fetch('/api/states/retrieve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ session_id: sessionId }),
            });

            const result: ApiResponse<ApplicationState> = await response.json();

            if (!result.success || !result.data) {
                throw new Error(result.message || 'Failed to load application state');
            }

            dispatch({ type: 'SET_APPLICATION_STATE', payload: result.data });

        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Failed to load application state';
            dispatch({ type: 'SET_ERROR', payload: errorMessage });
            handleAsyncError(async () => { throw error; }, 'ApplicationContext.loadApplicationState');
        }
    }, [handleAsyncError]);

    const resetApplication = useCallback(() => {
        dispatch({ type: 'RESET_APPLICATION' });
    }, []);

    const setError = useCallback((error: string | null) => {
        dispatch({ type: 'SET_ERROR', payload: error });
    }, []);

    const clearError = useCallback(() => {
        dispatch({ type: 'SET_ERROR', payload: null });
    }, []);

    const contextValue: ApplicationContextType = {
        ...state,
        updateFormData,
        setCurrentStep,
        saveApplicationState,
        loadApplicationState,
        resetApplication,
        setError,
        clearError,
    };

    return (
        <ApplicationContext.Provider value={contextValue}>
            {children}
        </ApplicationContext.Provider>
    );
}

// Hook to use the context
export function useApplication(): ApplicationContextType {
    const context = useContext(ApplicationContext);
    
    if (context === undefined) {
        throw new Error('useApplication must be used within an ApplicationProvider');
    }
    
    return context;
}

// Hook for form data management
export function useFormData() {
    const { applicationState, updateFormData } = useApplication();
    
    const formData = applicationState?.form_data || {};
    
    const updateField = useCallback((field: string, value: any) => {
        updateFormData({ [field]: value });
    }, [updateFormData]);
    
    const updateFormResponses = useCallback((responses: Record<string, any>) => {
        updateFormData({
            formResponses: {
                ...formData.formResponses,
                ...responses,
            },
        });
    }, [updateFormData, formData.formResponses]);
    
    return {
        formData,
        updateFormData,
        updateField,
        updateFormResponses,
    };
}

// Hook for step management
export function useStepNavigation() {
    const { applicationState, setCurrentStep } = useApplication();
    
    const currentStep = applicationState?.current_step || 'language';
    
    const goToStep = useCallback((step: ApplicationStep) => {
        setCurrentStep(step);
    }, [setCurrentStep]);
    
    const goToNextStep = useCallback(() => {
        const steps: ApplicationStep[] = [
            'language', 'intent', 'employer', 'product', 'account', 
            'summary', 'form', 'documents', 'completed'
        ];
        
        const currentIndex = steps.indexOf(currentStep);
        if (currentIndex < steps.length - 1) {
            setCurrentStep(steps[currentIndex + 1]);
        }
    }, [currentStep, setCurrentStep]);
    
    const goToPreviousStep = useCallback(() => {
        const steps: ApplicationStep[] = [
            'language', 'intent', 'employer', 'product', 'account', 
            'summary', 'form', 'documents', 'completed'
        ];
        
        const currentIndex = steps.indexOf(currentStep);
        if (currentIndex > 0) {
            setCurrentStep(steps[currentIndex - 1]);
        }
    }, [currentStep, setCurrentStep]);
    
    return {
        currentStep,
        goToStep,
        goToNextStep,
        goToPreviousStep,
    };
}
