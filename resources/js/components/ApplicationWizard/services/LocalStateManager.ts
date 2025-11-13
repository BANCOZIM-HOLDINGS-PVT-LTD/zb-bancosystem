/**
 * Local State Manager for form persistence
 * Saves form state to localStorage to prevent data loss on page refresh or navigation
 */
export class LocalStateManager {
    private readonly STORAGE_KEY = 'bancozim_application_state';
    private readonly SESSION_KEY = 'bancozim_session_id';
    private readonly STEP_KEY = 'bancozim_current_step';
    private readonly EXPIRY_KEY = 'bancozim_state_expiry';
    private readonly EXPIRY_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    
    /**
     * Save the current state to localStorage
     */
    saveLocalState(sessionId: string, currentStep: string, formData: any): void {
        try {
            const state = {
                sessionId,
                currentStep,
                formData,
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(state));
            localStorage.setItem(this.SESSION_KEY, sessionId);
            localStorage.setItem(this.STEP_KEY, currentStep);
            localStorage.setItem(this.EXPIRY_KEY, (Date.now() + this.EXPIRY_DURATION).toString());
            
            // Also save to sessionStorage for tab-specific persistence
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(state));
        } catch (error) {
            console.error('Error saving local state:', error);
        }
    }
    
    /**
     * Retrieve the saved state from localStorage
     */
    getLocalState(): { sessionId: string; currentStep: string; formData: any } | null {
        try {
            // Check expiry
            const expiry = localStorage.getItem(this.EXPIRY_KEY);
            if (expiry && Date.now() > parseInt(expiry)) {
                this.clearLocalState();
                return null;
            }
            
            // Try sessionStorage first (for current tab)
            const sessionState = sessionStorage.getItem(this.STORAGE_KEY);
            if (sessionState) {
                return JSON.parse(sessionState);
            }
            
            // Fall back to localStorage
            const state = localStorage.getItem(this.STORAGE_KEY);
            if (state) {
                return JSON.parse(state);
            }
        } catch (error) {
            console.error('Error retrieving local state:', error);
        }
        
        return null;
    }
    
    /**
     * Get the current session ID
     */
    getSessionId(): string | null {
        return localStorage.getItem(this.SESSION_KEY);
    }
    
    /**
     * Get the current step
     */
    getCurrentStep(): string | null {
        return localStorage.getItem(this.STEP_KEY);
    }
    
    /**
     * Clear the saved state
     */
    clearLocalState(): void {
        try {
            localStorage.removeItem(this.STORAGE_KEY);
            localStorage.removeItem(this.SESSION_KEY);
            localStorage.removeItem(this.STEP_KEY);
            localStorage.removeItem(this.EXPIRY_KEY);
            sessionStorage.removeItem(this.STORAGE_KEY);
        } catch (error) {
            console.error('Error clearing local state:', error);
        }
    }
    
    /**
     * Check if there's a valid saved state
     */
    hasValidState(): boolean {
        const expiry = localStorage.getItem(this.EXPIRY_KEY);
        if (!expiry || Date.now() > parseInt(expiry)) {
            return false;
        }
        
        return !!localStorage.getItem(this.STORAGE_KEY);
    }
    
    /**
     * Save a partial update to the form data
     */
    updateFormData(updates: any): void {
        const currentState = this.getLocalState();
        if (currentState) {
            const updatedState = {
                ...currentState,
                formData: {
                    ...currentState.formData,
                    ...updates
                },
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(updatedState));
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(updatedState));
        }
    }
    
    /**
     * Debounced save function for real-time form updates
     */
    private saveTimeout: NodeJS.Timeout | null = null;
    
    debouncedSave(sessionId: string, currentStep: string, formData: any, delay = 1000): void {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveTimeout = setTimeout(() => {
            this.saveLocalState(sessionId, currentStep, formData);
        }, delay);
    }
}