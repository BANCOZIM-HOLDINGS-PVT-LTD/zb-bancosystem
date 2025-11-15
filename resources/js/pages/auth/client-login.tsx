import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type ClientLoginForm = {
    national_id: string;
};

export default function ClientLogin() {
    const { data, setData, post, processing, errors } = useForm<Required<ClientLoginForm>>({
        national_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('client.login'));
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
    };

    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your National ID to log in"
        >
            <Head title="Login" />
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
                        <InputError message={errors.national_id} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={2} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Log in
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Don't have an account?{' '}
                    <TextLink href={route('client.register')} tabIndex={3}>
                        Register
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}