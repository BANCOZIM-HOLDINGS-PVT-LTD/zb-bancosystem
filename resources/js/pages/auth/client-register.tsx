import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { countryCodes } from '@/data/countryCodes';
import CountryCodeSelect from '@/components/ui/country-code-select';

type ClientRegisterForm = {
    phone: string;
};

export default function ClientRegister() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<ClientRegisterForm>>({
        phone: '+263', // Default country code
    });

    // Helper to extract dial code from phone number
    const getDialCode = (phone: string) => {
        // Sort codes by length descending to match longest first
        // We memoize this or just run it since countryCodes is small enough (~250 items)
        const sortedCodes = [...countryCodes].sort((a, b) => b.dial_code.length - a.dial_code.length);
        const match = sortedCodes.find(c => phone.startsWith(c.dial_code));
        return match ? match.dial_code : '+263';
    };

    const currentDialCode = getDialCode(data.phone);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('client.register'), {
            onFinish: () => reset('phone'),
        });
    };

    return (
        <AuthLayout
            title="Register"
            description="Enter your phone number to register"
        >
            <Head title="Register" />
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
                                disabled={processing}
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
                                disabled={processing}
                                placeholder="771234567"
                                className="flex-1"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">Enter your phone number.
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