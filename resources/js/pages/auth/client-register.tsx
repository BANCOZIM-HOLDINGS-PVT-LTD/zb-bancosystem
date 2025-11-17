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
        phone: '+263',
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

        // Format as XX-XXXXXXX-Y-XX (Zimbabwe ID format)
        let formatted = '';

        if (cleaned.length <= 2) {
            formatted = cleaned;
        } else if (cleaned.length <= 9) {
            formatted = `${cleaned.slice(0, 2)}-${cleaned.slice(2)}`;
        } else if (cleaned.length <= 10) {
            formatted = `${cleaned.slice(0, 2)}-${cleaned.slice(2, 9)}-${cleaned.slice(9)}`;
        } else if (cleaned.length <= 12) {
            formatted = `${cleaned.slice(0, 2)}-${cleaned.slice(2, 9)}-${cleaned.slice(9, 10)}-${cleaned.slice(10)}`;
        } else {
            formatted = `${cleaned.slice(0, 2)}-${cleaned.slice(2, 9)}-${cleaned.slice(9, 10)}-${cleaned.slice(10, 12)}`;
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
                        <Input
                            id="phone"
                            type="tel"
                            required
                            tabIndex={2}
                            value={data.phone}
                            onChange={handlePhoneChange}
                            disabled={processing}
                            placeholder="+263771234567"
                        />
                        <p className="text-xs text-muted-foreground">
                            Enter your Zimbabwe phone number (we'll send you a verification code)
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