import axios from 'axios';

export class StateManager {
    private apiUrl = '/api/states';

    generateSessionId(): string {
        return `web_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    async saveState(sessionId: string, currentStep: string, formData: any): Promise<void> {
        try {
            // Sanitize and validate the data before sending
            const sanitizedFormData = this.sanitizeFormData(formData);
            const sanitizedSessionId = this.sanitizeSessionId(sessionId);
            const sanitizedCurrentStep = this.sanitizeCurrentStep(currentStep);

            const payload = {
                session_id: sanitizedSessionId,
                channel: 'web',
                user_identifier: this.getUserIdentifier(),
                current_step: sanitizedCurrentStep,
                form_data: sanitizedFormData,
                metadata: {
                    ip_address: null, // Will be filled by server
                    user_agent: navigator.userAgent,
                    timestamp: new Date().toISOString()
                }
            };

            console.log('Sending state save request:', {
                session_id: payload.session_id,
                current_step: payload.current_step,
                data_keys: Object.keys(payload.form_data || {}),
                user_identifier: payload.user_identifier
            });

            await axios.post(`${this.apiUrl}/save`, payload);
        } catch (error: unknown) {
            console.error('Error saving state:', error);

            // Log validation errors if available
            if (axios.isAxiosError(error) && error.response?.data?.errors) {
                console.error('Validation errors:', error.response.data.errors);
            }

            throw error;
        }
    }

    private sanitizeSessionId(sessionId: string): string {
        if (!sessionId) return this.generateSessionId();

        // Only allow alphanumeric, underscore, and dash
        return sessionId.replace(/[^a-zA-Z0-9_-]/g, '').substring(0, 255);
    }

    private sanitizeCurrentStep(currentStep: string): string {
        const validSteps = [
            // Original web flow steps
            'language', 'intent', 'employer', 'product', 'account',
            'summary', 'form', 'documents', 'completed', 'in_review',
            'approved', 'rejected', 'pending_documents', 'processing',
            // Extended wizard steps
            'housePlanApproval', 'constructionDetails', 'companyRegistration',
            'licenseCourses', 'zimparksHoliday', 'creditTerm', 'creditType',
            'delivery', 'registration', 'depositPayment'
        ];

        return validSteps.includes(currentStep) ? currentStep : 'product';
    }

    private sanitizeFormData(formData: any): any {
        if (!formData || typeof formData !== 'object') {
            return {};
        }

        const sanitized = { ...formData };

        // Sanitize formResponses if present
        if (sanitized.formResponses && typeof sanitized.formResponses === 'object') {
            const responses = { ...sanitized.formResponses };

            // Sanitize string fields
            Object.keys(responses).forEach(key => {
                if (typeof responses[key] === 'string') {
                    // Remove control characters and trim
                    responses[key] = responses[key]
                        .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '')
                        .trim();
                }
            });

            // Validate specific fields
            if (responses.firstName) {
                responses.firstName = responses.firstName.replace(/[^a-zA-Z\s'-]/g, '').substring(0, 100);
            }
            if (responses.lastName) {
                responses.lastName = responses.lastName.replace(/[^a-zA-Z\s'-]/g, '').substring(0, 100);
            }
            if (responses.nationalIdNumber) {
                responses.nationalIdNumber = responses.nationalIdNumber.replace(/[^a-zA-Z0-9-]/g, '').substring(0, 50);
            }
            if (responses.mobile) {
                // Sanitize mobile number - allow digits, spaces, dashes, parentheses, and + sign
                responses.mobile = responses.mobile.replace(/[^0-9\s\-\(\)\+]/g, '').trim();

                // Ensure it matches Zimbabwe format
                if (responses.mobile && !responses.mobile.match(/^(\+263|0)?[0-9\s\-\(\)]{7,15}$/)) {
                    // If it doesn't match, try to clean it up
                    const digitsOnly = responses.mobile.replace(/[^0-9]/g, '');

                    // If it starts with 263, add +
                    if (digitsOnly.startsWith('263')) {
                        responses.mobile = '+' + digitsOnly;
                    } else if (digitsOnly.length >= 9) {
                        // Assume it's a local number without leading 0
                        responses.mobile = '0' + digitsOnly;
                    } else {
                        responses.mobile = digitsOnly;
                    }
                }
            }

            sanitized.formResponses = responses;
        }

        // Ensure numeric fields are valid
        if (sanitized.amount) {
            const amount = parseFloat(sanitized.amount);
            sanitized.amount = (!isNaN(amount) && amount >= 0 && amount <= 1000000) ? amount : null;
        }

        return sanitized;
    }

    async retrieveState(userIdentifier?: string): Promise<any> {
        try {
            const response = await axios.post(`${this.apiUrl}/retrieve`, {
                user: userIdentifier || this.getUserIdentifier(),
                channel: 'web'
            });
            return response.data;
        } catch (error) {
            console.error('Error retrieving state:', error);
            return null;
        }
    }

    private getUserIdentifier(): string {
        let identifier = localStorage.getItem('user_identifier');
        if (!identifier) {
            identifier = `web_user_${Date.now()}`;
            localStorage.setItem('user_identifier', identifier);
        }

        // Ensure it meets validation requirements: alphanumeric, @, ., _, +, -
        return identifier.replace(/[^a-zA-Z0-9@._+-]/g, '').substring(0, 255);
    }
}
