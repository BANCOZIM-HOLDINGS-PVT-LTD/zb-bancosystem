import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState, useRef, useEffect } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import axios from 'axios';

interface VerifyOtpProps {
    phone: string;
    maskedPhone: string;
}

export default function VerifyOtp({ phone, maskedPhone }: VerifyOtpProps) {
    const { data, setData, post, processing, errors } = useForm({
        otp: '',
    });

    const [resendCooldown, setResendCooldown] = useState(60); // Start with 60 second cooldown
    const [resending, setResending] = useState(false);
    const [resendMessage, setResendMessage] = useState('');

    const inputRefs = [
        useRef<HTMLInputElement>(null),
        useRef<HTMLInputElement>(null),
        useRef<HTMLInputElement>(null),
        useRef<HTMLInputElement>(null),
        useRef<HTMLInputElement>(null),
        useRef<HTMLInputElement>(null),
    ];

    // Countdown timer for resend
    useEffect(() => {
        if (resendCooldown > 0) {
            const timer = setTimeout(() => setResendCooldown(resendCooldown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [resendCooldown]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('client.otp.verify'));
    };

    const handleOtpChange = (index: number, value: string) => {
        // Only allow digits
        const digit = value.replace(/[^0-9]/g, '');

        if (digit.length > 1) {
            // If pasting multiple digits, distribute them
            const digits = digit.slice(0, 6).split('');
            let newOtp = data.otp.split('');

            digits.forEach((d, i) => {
                if (index + i < 6) {
                    newOtp[index + i] = d;
                    if (index + i < 5) {
                        inputRefs[index + i + 1].current?.focus();
                    }
                }
            });

            setData('otp', newOtp.join(''));
            return;
        }

        // Update the current digit
        const otpArray = data.otp.split('');
        otpArray[index] = digit;
        setData('otp', otpArray.join(''));

        // Auto-focus next input
        if (digit && index < 5) {
            inputRefs[index + 1].current?.focus();
        }
    };

    const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Backspace' && !data.otp[index] && index > 0) {
            inputRefs[index - 1].current?.focus();
        }
    };

    const handleResend = async () => {
        if (resending || resendCooldown > 0) return;

        setResending(true);
        setResendMessage('');

        try {
            const response = await axios.post(route('client.otp.resend'));
            setResendMessage(response.data.message);
            setResendCooldown(60); // 60 seconds cooldown
        } catch (error: any) {
            setResendMessage(error.response?.data?.message || 'Failed to resend OTP');
        } finally {
            setResending(false);
        }
    };

    return (
        <AuthLayout
            title="Verify your phone number"
            description={`Enter the 6-digit code sent to ${maskedPhone}`}
        >
            <Head title="Verify OTP" />
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-4">
                        <Label className="text-center">Enter Verification Code</Label>

                        <div className="flex justify-center gap-2">
                            {inputRefs.map((ref, index) => (
                                <Input
                                    key={index}
                                    ref={ref}
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={1}
                                    value={data.otp[index] || ''}
                                    onChange={(e) => handleOtpChange(index, e.target.value)}
                                    onKeyDown={(e) => handleKeyDown(index, e)}
                                    className="w-12 h-12 text-center text-lg font-semibold"
                                    disabled={processing}
                                    autoFocus={index === 0}
                                />
                            ))}
                        </div>

                        <InputError message={errors.otp} className="text-center" />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing || data.otp.length !== 6}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Verify & Continue
                    </Button>

                    <div className="text-center">
                        <p className="text-sm text-muted-foreground mb-2">
                            Didn't receive the code?
                        </p>
                        <Button
                            type="button"
                            variant="link"
                            onClick={handleResend}
                            disabled={resending || resendCooldown > 0}
                            className="text-sm"
                        >
                            {resending ? (
                                <>
                                    <LoaderCircle className="h-3 w-3 animate-spin mr-2" />
                                    Sending...
                                </>
                            ) : resendCooldown > 0 ? (
                                `Resend code in ${resendCooldown}s`
                            ) : (
                                'Resend code'
                            )}
                        </Button>
                        {resendMessage && (
                            <p className="text-sm text-green-600 mt-2">{resendMessage}</p>
                        )}
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}