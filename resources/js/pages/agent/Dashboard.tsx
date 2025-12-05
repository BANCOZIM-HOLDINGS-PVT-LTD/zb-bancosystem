import { Head, Link } from '@inertiajs/react';
import { Copy, ExternalLink, LogOut, Share2, Store, User, MapPin, Phone, CreditCard, Calendar, CheckCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface AgentDashboardProps {
    agent: {
        id: number;
        first_name: string;
        surname: string;
        province: string;
        whatsapp_contact: string;
        ecocash_number: string;
        agent_code: string;
        referral_link: string;
        created_at: string;
        updated_at: string;
    };
    stats: {
        total_referrals: number;
        successful_referrals: number;
        pending_commission: number;
        total_earned: number;
    };
}

export default function AgentDashboard({ agent, stats }: AgentDashboardProps) {
    const [copied, setCopied] = useState(false);

    const copyLink = () => {
        navigator.clipboard.writeText(agent.referral_link);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const shareWhatsApp = () => {
        const text = `🌟 Start your own business with Microbiz Zimbabwe! Get gadgets, furniture, solar systems on credit. Apply now: ${agent.referral_link}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const shareFacebook = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(agent.referral_link)}`, '_blank');
    };

    return (
        <>
            <Head title="Agent Dashboard" />

            <div className="min-h-screen bg-background">
                {/* Header */}
                <header className="border-b bg-card">
                    <div className="container mx-auto px-4 py-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img src="/adala.jpg" alt="Logo" className="h-10 w-auto" />
                            <div>
                                <h1 className="text-lg font-semibold">Microbiz Zimbabwe</h1>
                                <p className="text-xs text-muted-foreground">Agent Portal</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-full text-sm font-medium">
                                <Store className="h-4 w-4" />
                                Agent
                            </div>
                            <a href={route('agent.logout')} className="text-muted-foreground hover:text-foreground transition-colors">
                                <LogOut className="h-5 w-5" />
                            </a>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="container mx-auto px-4 py-8">
                    {/* Welcome */}
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold">Welcome back, {agent.first_name}! 👋</h2>
                        <p className="text-muted-foreground">Here's an overview of your agent activity</p>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
                                <div className="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-lg">📊</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{stats.total_referrals}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Successful</CardTitle>
                                <div className="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-lg">✅</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{stats.successful_referrals}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Pending Commission</CardTitle>
                                <div className="h-10 w-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-lg">⏳</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">${stats.pending_commission.toFixed(2)}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Earned</CardTitle>
                                <div className="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-lg">💰</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">${stats.total_earned.toFixed(2)}</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Referral Section */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Share2 className="h-5 w-5" />
                                Your Referral Link
                            </CardTitle>
                            <CardDescription>Share this link to earn commissions on successful applications</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Agent Code Display */}
                            <div className="bg-slate-900 dark:bg-slate-800 text-white p-4 rounded-lg">
                                <p className="text-xs text-slate-400 mb-1">YOUR AGENT CODE</p>
                                <p className="text-2xl font-bold tracking-[0.3em] font-mono">{agent.agent_code}</p>
                            </div>

                            {/* Referral Link */}
                            <div className="flex gap-2">
                                <div className="flex-1 bg-muted p-3 rounded-lg border-2 border-dashed">
                                    <input
                                        type="text"
                                        value={agent.referral_link}
                                        readOnly
                                        className="w-full bg-transparent text-sm outline-none"
                                    />
                                </div>
                                <Button onClick={copyLink} variant="default" className="bg-emerald-600 hover:bg-emerald-700">
                                    {copied ? <CheckCircle className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                    <span className="ml-2">{copied ? 'Copied!' : 'Copy'}</span>
                                </Button>
                            </div>

                            {/* Share Buttons */}
                            <div className="flex gap-3">
                                <Button onClick={shareWhatsApp} className="bg-[#25D366] hover:bg-[#128C7E] text-white">
                                    💬 Share on WhatsApp
                                </Button>
                                <Button onClick={shareFacebook} className="bg-[#1877F2] hover:bg-[#0d6efd] text-white">
                                    📘 Share on Facebook
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Profile Section */}
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Your Profile
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between py-2 border-b">
                                    <span className="text-muted-foreground">Full Name</span>
                                    <span className="font-medium">{agent.first_name} {agent.surname}</span>
                                </div>
                                <div className="flex justify-between py-2 border-b">
                                    <span className="text-muted-foreground flex items-center gap-2">
                                        <MapPin className="h-4 w-4" /> Province
                                    </span>
                                    <span className="font-medium">{agent.province}</span>
                                </div>
                                <div className="flex justify-between py-2 border-b">
                                    <span className="text-muted-foreground flex items-center gap-2">
                                        <Phone className="h-4 w-4" /> WhatsApp
                                    </span>
                                    <span className="font-medium">{agent.whatsapp_contact}</span>
                                </div>
                                <div className="flex justify-between py-2">
                                    <span className="text-muted-foreground flex items-center gap-2">
                                        <CreditCard className="h-4 w-4" /> EcoCash
                                    </span>
                                    <span className="font-medium">{agent.ecocash_number}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Calendar className="h-5 w-5" />
                                    Account Info
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between py-2 border-b">
                                    <span className="text-muted-foreground">Status</span>
                                    <span className="font-medium text-emerald-600 flex items-center gap-1">
                                        <CheckCircle className="h-4 w-4" /> Active
                                    </span>
                                </div>
                                <div className="flex justify-between py-2 border-b">
                                    <span className="text-muted-foreground">Member Since</span>
                                    <span className="font-medium">{new Date(agent.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                                </div>
                                <div className="flex justify-between py-2">
                                    <span className="text-muted-foreground">Approved On</span>
                                    <span className="font-medium">{new Date(agent.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </main>
            </div>
        </>
    );
}
