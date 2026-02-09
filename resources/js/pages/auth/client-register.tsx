import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { countryCodes } from '@/data/countryCodes';
import CountryCodeSelect from '@/components/ui/country-code-select';
import { SessionRecoveryModal } from '@/components/ApplicationWizard/components/SessionRecoveryModal';

type ClientRegisterForm = {
    phone: string;
};

export default function ClientRegister() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<ClientRegisterForm>>({
        phone: '+263', // Default country code
    });

    const [showRecoveryModal, setShowRecoveryModal] = useState(false);
    const [existingSession, setExistingSession] = useState<any>(null);
    const [checkingSession, setCheckingSession] = useState(false);

    // Helper to extract dial code from phone number
    const getDialCode = (phone: string) => {
        // Sort codes by length descending to match longest first
        // We memoize this or just run it since countryCodes is small enough (~250 items)
        const sortedCodes = [...countryCodes].sort((a, b) => b.dial_code.length - a.dial_code.length);
        const match = sortedCodes.find(c => phone.startsWith(c.dial_code));
        return match ? match.dial_code : '+263';
    };

    const currentDialCode = getDialCode(data.phone);

    const submit: FormEventHandler = async (e) => {
        e.preventDefault();

        if (checkingSession) return;
        setCheckingSession(true);

        // Check for existing session first
        try {
            const response = await fetch('/api/states/check-existing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ phone: data.phone }),
            });

            const result = await response.json();

            if (result.has_existing_session) {
                setExistingSession(result);
                setShowRecoveryModal(true);
                setCheckingSession(false);
                return;
            }
        } catch (error) {
            console.error('Failed to check existing session', error);
            // Fallback to normal submission if check fails
        }

        setCheckingSession(false);

        post(route('client.register'), {
            onFinish: () => reset('phone'),
        });
    };

    const handleContinue = () => {
        if (existingSession && existingSession.session_id) {
            window.location.href = `/application?session=${existingSession.session_id}&resume=true`;
        }
    };

    const handleDiscard = async () => {
        if (!existingSession || !existingSession.session_id) return;

        try {
            await fetch('/api/states/discard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ session_id: existingSession.session_id }),
            });

            // Proceed with registration after discard
            post(route('client.register'), {
                onFinish: () => reset('phone'),
            });
        } catch (error) {
            console.error('Failed to discard session', error);
            // Even if discard fails API-wise, we might want to try proceeding or show error
            // For now, let's try to proceed
            post(route('client.register'), {
                onFinish: () => reset('phone'),
            });
        }
    };

    return (
        <AuthLayout
            title="Register"
            description="Enter your phone number to register"
        >
            <Head title="Register" />

            {existingSession && (
                <SessionRecoveryModal
                    open={showRecoveryModal}
                    onOpenChange={setShowRecoveryModal}
                    sessionId={existingSession.session_id}
                    lastActivity={existingSession.last_activity}
                    currentStep={existingSession.current_step}
                    onDiscard={handleDiscard}
                    onContinue={handleContinue}
                />
            )}

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="phone">Phone Number</Label>
                        <div className="flex gap-2">
                            <CountryCodeSelect
                                value={currentDialCode}
                                onChange={(newCode) => {
                                    // Strip the old code and prepend new code
                                    const numberPart = data.phone.substring(currentDialCode.length);
                                    setData('phone', newCode + numberPart);
                                }}
                                countries={countryCodes}
                                disabled={processing || checkingSession}
                            />
                            <Input
                                id="phone"
                                type="tel"
                                required
                                tabIndex={2}
                                value={data.phone.substring(currentDialCode.length)} // Display only the number part
                                onChange={(e) => {
                                    const number = e.target.value.replace(/\D/g, ''); // Numeric only for the body
                                    setData('phone', currentDialCode + number);
                                }}
                                disabled={processing || checkingSession}
                                placeholder="771234567"
                                className="flex-1"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">Enter your phone number.
                        </p>
                        <InputError message={errors.phone} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={3} disabled={processing || checkingSession}>
                        {(processing || checkingSession) && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Continue
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <TextLink href={route('client.login')} tabIndex={4}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}