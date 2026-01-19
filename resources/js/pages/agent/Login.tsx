import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, KeyRound, Store } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type AgentLoginForm = {
    agent_code: string;
};

interface AgentLoginProps {
    status?: string;
    error?: string;
}

export default function AgentLogin({ status, error }: AgentLoginProps) {
    const { data, setData, post, processing, errors } = useForm<Required<AgentLoginForm>>({
        agent_code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('agent.login.submit'));
    };

    return (
        <AuthLayout title="Agent Portal" description="Enter your agent code to access your dashboard">
            <Head title="Agent Login" />

            {/* Agent Badge */}
            <div className="flex justify-center mb-4">
                <div className="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-4 py-2 rounded-full text-sm font-medium">
                    <Store className="h-4 w-4" />
                    Agent Access
                </div>
            </div>

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="agent_code">Agent Code</Label>
                        <div className="relative">
                            <KeyRound className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="agent_code"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="off"
                                value={data.agent_code}
                                onChange={(e) => setData('agent_code', e.target.value.toUpperCase())}
                                placeholder="e.g AG123456"
                                className="pl-10 uppercase tracking-widest font-mono"
                            />
                        </div>
                        <InputError message={errors.agent_code} />
                    </div>

                    <Button type="submit" className="mt-2 w-full bg-emerald-600 hover:bg-emerald-700" tabIndex={2} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                        Click to Proceed
                    </Button>
                </div>
            </form>

            {status && <div className="mt-4 text-center text-sm font-medium text-green-600">{status}</div>}
            {error && <div className="mt-4 text-center text-sm font-medium text-red-600">{error}</div>}

            {/* Help Text */}
            <p className="mt-6 text-center text-sm text-muted-foreground">
                Don't have an agent code?{' '}
                <a
                    href="https://wa.me/254773988988"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-emerald-600 hover:text-emerald-700 font-medium"
                >
                    Apply to become an agent
                </a>
            </p>

            {/* Features */}
            <div className="mt-8 pt-6 border-t border-border">
                <p className="text-xs font-medium text-muted-foreground text-center mb-4">Agent Portal Features</p>
                <div className="grid gap-3 text-sm text-muted-foreground">
                    <div className="flex items-center gap-3">
                        <span>📊</span>
                        <span>View your referral statistics</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <span>🔗</span>
                        <span>Get your unique referral link</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <span>💰</span>
                        <span>Track your commissions</span>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
