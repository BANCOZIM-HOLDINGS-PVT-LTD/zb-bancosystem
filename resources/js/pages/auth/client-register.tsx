import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { validateZimbabweanID } from '@/utils/zimbabwean-id-validator';

type ClientRegisterForm = {
    national_id: string;
    phone: string;
};

export default function ClientRegister() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<ClientRegisterForm>>({
        national_id: '',
        phone: '+263', // Default country code
    });

    const [idValidationError, setIdValidationError] = useState<string>('');

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Validate ID before submitting
        const validation = validateZimbabweanID(data.national_id);
        if (!validation.valid) {
            setIdValidationError(validation.message || 'Invalid National ID');
            return;
        }

        post(route('client.register'), {
            onFinish: () => reset('phone'),
        });
    };

    const formatNationalId = (value: string) => {
        // Remove any non-alphanumeric characters
        const cleaned = value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();

        // Format logic to handle both 6 and 7 digit middle sections
        // We'll rely on the validator for final correctness, here we just try to insert dashes intelligently

        let formatted = '';
        if (cleaned.length <= 2) {
            formatted = cleaned;
        } else {
            // First 2 digits
            formatted = cleaned.slice(0, 2) + '-';

            // The rest
            const rest = cleaned.slice(2);

            if (rest.length > 0) {
                // Try to find the letter which marks the end of the middle section
                const letterMatch = rest.match(/[A-Z]/);

                if (letterMatch && letterMatch.index !== undefined) {
                    // Digits before letter
                    const middleDigits = rest.slice(0, letterMatch.index);
                    // The letter
                    const letter = rest[letterMatch.index];
                    // Digits after letter
                    const endDigits = rest.slice(letterMatch.index + 1);

                    formatted += middleDigits + '-' + letter;

                    if (endDigits.length > 0) {
                        formatted += '-' + endDigits.slice(0, 2);
                    }
                } else {
                    // No letter yet, just dump the rest
                    formatted += rest;
                }
            }
        }

        return formatted;
    };

    const handleNationalIdChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const formatted = formatNationalId(e.target.value);
        setData('national_id', formatted);

        // Real-time validation
        if (formatted.length >= 11) {
            const validation = validateZimbabweanID(formatted);
            if (!validation.valid) {
                setIdValidationError(validation.message || 'Invalid National ID');
            } else {
                setIdValidationError('');
            }
        } else {
            setIdValidationError('');
        }
    };

    const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let value = e.target.value;

        // Ensure it starts with +263
        if (!value.startsWith('+263')) {
            value = '+263';
        }

        // Remove any non-digit characters except the leading +
        value = '+263' + value.slice(4).replace(/\D/g, '');

        // Limit to 13 characters (+263 + 9 digits)
        if (value.length > 13) {
            value = value.slice(0, 13);
        }

        setData('phone', value);
    };

    return (
        <AuthLayout
            title="Register"
            description="Enter your National ID and phone number to register"
        >
            <Head title="Register" />
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="national_id">National ID</Label>
                        <Input
                            id="national_id"
                            type="text"
                            required
                            autoFocus
                            tabIndex={1}
                            value={data.national_id}
                            onChange={handleNationalIdChange}
                            disabled={processing}
                            placeholder="08-2047823-Q-29"
                            maxLength={15}
                        />
                        <p className="text-xs text-muted-foreground">
                            Format: XX-XXXXXXX-Y-XX (e.g., 08-2047823-Q-29)
                        </p>
                        {idValidationError && (
                            <p className="text-xs text-destructive">{idValidationError}</p>
                        )}
                        <InputError message={errors.national_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="phone">Phone Number</Label>
                        <div className="flex gap-2">
                            <div className="w-[110px] shrink-0">
                                <select
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={data.phone.startsWith('+') ? data.phone.slice(0, 4) : '+263'}
                                    onChange={(e) => {
                                        const code = e.target.value;
                                        // Update phone start with new code while keeping the rest
                                        const currentNumber = data.phone.replace(/^\+\d{3}/, ''); // Strip existing code if any
                                        setData('phone', code + currentNumber);
                                    }}
                                    disabled={processing}
                                >
                                    <option value="+263">🇿🇼 +263</option>
                                    <option value="+27">🇿🇦 +27</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+267">🇧🇼 +267</option>
                                    <option value="+260">🇿🇲 +260</option>
                                    {/* Add generic option for others if needed, strictly text input next */}
                                </select>
                            </div>
                            <Input
                                id="phone"
                                type="tel"
                                required
                                tabIndex={2}
                                value={data.phone.replace(/^\+\d{3}/, '')} // Display only the number part
                                onChange={(e) => {
                                    const number = e.target.value.replace(/\D/g, ''); // Numeric only for the body
                                    const currentCode = data.phone.startsWith('+') ? data.phone.slice(0, 4) : '+263';
                                    setData('phone', currentCode + number);
                                }}
                                disabled={processing}
                                placeholder="771234567"
                                className="flex-1"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Enter your phone number starting with the country code on the left.
                        </p>
                        <InputError message={errors.phone} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={3} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
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