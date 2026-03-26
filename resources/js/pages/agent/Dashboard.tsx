import { Head, Link } from '@inertiajs/react';
import { Copy, ExternalLink, LogOut, Share2, Store, User, MapPin, Phone, CreditCard, Calendar, CheckCircle, BarChart, ShoppingBag, MessageSquare, ChevronRight, LayoutDashboard, TrendingUp, DollarSign, Users, Award, Zap, Target } from 'lucide-react';
import { useState, useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Product {
    id: number;
    name: string;
    image_url: string | null;
}

interface SubCategory {
    id: number;
    name: string;
    products: Product[];
}

interface Category {
    id: number;
    name: string;
    sub_categories: SubCategory[];
}

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
        tier: 'ordinary' | 'higher_achiever';
        last_commission_amount: number;
        is_deactivated: boolean;
        deactivated_at: string | null;
    };
    stats: {
        total_referrals: number;
        successful_referrals: number;
        pending_commission: number;
        total_earned: number;
    };
    lastCommissionDate: string | null;
    cashReferrals: number;
    creditReferrals: number;
    supervisorComment: string | null;
    productCategories: Category[];
    generalLinks: {
        id: number;
        name: string;
        poster: string;
        description: string;
    }[];
    monthlyPerformance: {
        month: string;
        visits: number;
        referrals: number;
    }[];
    applicationHistory: {
        id: number;
        date: string;
        product: string;
        status: string;
        commission: number;
        reference: string;
    }[];
    activityLogs: {
        id: number;
        type: string;
        description: string;
        timestamp: string;
    }[];
    milestones: {
        weekly_commission: number;
        weekly_target: number;
        daily_referrals: number;
        daily_target: number;
    };
}

const PerformanceChart = ({ data }: { data: { month: string, visits: number, referrals: number }[] }) => {
    const maxVal = Math.max(...data.map(d => Math.max(d.visits, d.referrals)), 10);
    
    return (
        <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 rounded-2xl overflow-hidden mt-6">
            <div className="px-6 py-4 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-transparent flex items-center justify-between">
                <div>
                    <h3 className="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Performance Overview</h3>
                    <p className="text-[9px] font-bold text-slate-400 uppercase mt-1">Last 6 Months Activity</p>
                </div>
                <div className="flex gap-4">
                    <div className="flex items-center gap-1.5">
                        <div className="h-2 w-2 rounded-full bg-emerald-500"></div>
                        <span className="text-[9px] font-black uppercase text-slate-400">Referrals</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="h-2 w-2 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                        <span className="text-[9px] font-black uppercase text-slate-400">Visits</span>
                    </div>
                </div>
            </div>
            <div className="p-6">
                <div className="h-48 w-full flex items-end justify-between gap-2">
                    {data.map((d, i) => (
                        <div key={i} className="flex-1 flex flex-col items-center gap-2 group relative">
                            <div className="w-full flex items-end justify-center gap-1 h-32">
                                {/* Visits Bar */}
                                <div 
                                    className="w-1.5 bg-slate-100 dark:bg-slate-800 rounded-t-full transition-all duration-500 group-hover:bg-slate-200" 
                                    style={{ height: `${(d.visits / maxVal) * 100}%` }}
                                >
                                    <div className="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[8px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                                        {d.visits} Visits
                                    </div>
                                </div>
                                {/* Referrals Bar */}
                                <div 
                                    className="w-3 bg-emerald-500 rounded-t-sm transition-all duration-500 group-hover:bg-emerald-400 shadow-[0_-4px_12px_rgba(16,185,129,0.1)]" 
                                    style={{ height: `${(d.referrals / maxVal) * 100}%` }}
                                >
                                    <div className="absolute -top-8 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-[8px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                        {d.referrals} Referrals
                                    </div>
                                </div>
                            </div>
                            <span className="text-[9px] font-black text-slate-400 uppercase tracking-tighter">{d.month.split(' ')[0]}</span>
                        </div>
                    ))}
                </div>
            </div>
        </Card>
    );
};

export default function AgentDashboard({ 
    agent, 
    stats, 
    lastCommissionDate, 
    cashReferrals, 
    creditReferrals, 
    supervisorComment,
    productCategories,
    generalLinks,
    monthlyPerformance,
    applicationHistory,
    activityLogs,
    milestones
}: AgentDashboardProps) {
    const [copied, setCopied] = useState(false);
    const [productCopied, setProductCopied] = useState(false);
    const [selectedPosterLink, setSelectedPosterLink] = useState(generalLinks[0]);

    const [selectedCategoryId, setSelectedCategoryId] = useState<string>("");
    const [selectedSubCategoryId, setSelectedSubCategoryId] = useState<string>("");
    const [selectedProductId, setSelectedProductId] = useState<string>("");
    const [generatedLink, setGeneratedLink] = useState<string>("");

    const selectedCategory = useMemo(() => 
        productCategories.find(c => c.id.toString() === selectedCategoryId),
    [selectedCategoryId, productCategories]);

    const selectedSubCategory = useMemo(() => 
        selectedCategory?.sub_categories.find(s => s.id.toString() === selectedSubCategoryId),
    [selectedSubCategoryId, selectedCategory]);

    const selectedProduct = useMemo(() => 
        selectedSubCategory?.products.find(p => p.id.toString() === selectedProductId),
    [selectedProductId, selectedSubCategory]);

    const copyLink = (link: string) => {
        navigator.clipboard.writeText(link);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
        fetch(route('agent.log.activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ type: 'link_copied', description: 'Copied general referral link' }),
        });
    };

    const copyProductLink = () => {
        if (!generatedLink) return;
        navigator.clipboard.writeText(generatedLink);
        setProductCopied(true);
        setTimeout(() => setProductCopied(false), 2000);
        fetch(route('agent.log.activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ type: 'link_copied', description: 'Copied product referral link' }),
        });
    };

    const handleGenerateLink = () => {
        if (!selectedProductId) return;
        setGeneratedLink(`${window.location.origin}/apply?ref=${agent.agent_code}&product_id=${selectedProductId}`);
    };

    const shareWhatsApp = (link: string) => {
        const text = `🌟 Start your own business with Microbiz Zimbabwe! Apply now: ${link}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const shareFacebook = (link: string) => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`, '_blank');
    };

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 selection:bg-emerald-100">
            <Head title="Agent Dashboard" />

            {/* Header */}
            <header className="sticky top-0 z-50 w-full border-b border-slate-200/60 bg-white/95 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/95">
                <div className="container mx-auto flex h-14 items-center justify-between px-4">
                    <div className="flex items-center gap-3">
                        <img src="/adala.jpg" alt="Logo" className="h-7 w-7 rounded-lg object-cover shadow-sm" />
                        <h1 className="text-sm font-black tracking-tight text-slate-900 dark:text-white uppercase tracking-widest">Microbiz</h1>
                    </div>
                    <a href={route('agent.logout')} className="p-2 text-slate-400 hover:text-red-600 transition-colors">
                        <LogOut className="h-4 w-4" />
                    </a>
                </div>
            </header>

            <main className="container mx-auto px-4 py-6 max-w-7xl">
                {/* Deactivation Notification */}
                {agent.is_deactivated && (
                    <div className="mb-6 bg-red-50 border border-red-200 p-4 rounded-xl flex items-center justify-between">
                        <div className="flex items-center gap-3 text-red-700">
                            <Zap className="h-5 w-5 fill-red-500" />
                            <p className="text-sm font-bold">Your account has been deactivated due to inactivity. Reactivation takes 24 hours.</p>
                        </div>
                        <Button variant="outline" size="sm" onClick={() => {
                            fetch(route('agent.reactivate'), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                                }
                            }).then(() => window.location.reload());
                        }} className="border-red-200 text-red-600 hover:bg-red-100">Click here to reactivate</Button>
                    </div>
                )}

                {/* Name Container / Welcome Header */}
                <div className="mb-6 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-800/60 shadow-sm">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <h2 className="text-xl font-black text-slate-900 dark:text-white">
                                Hi, {agent.first_name} <span className="text-emerald-600 ml-2 font-mono text-base uppercase tracking-wider">AGENT CODE: {agent.agent_code}</span>
                            </h2>
                            <div className="flex items-center gap-4 mt-2">
                                <div className="flex items-center gap-2">
                                    <div className={`h-4 w-4 rounded border-2 flex items-center justify-center ${agent.tier === 'ordinary' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'}`}>
                                        {agent.tier === 'ordinary' && <CheckCircle className="h-3 w-3 text-white" />}
                                    </div>
                                    <span className={`text-[10px] font-black uppercase tracking-widest ${agent.tier === 'ordinary' ? 'text-slate-900' : 'text-slate-400'}`}>Ordinary</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className={`h-4 w-4 rounded border-2 flex items-center justify-center ${agent.tier === 'higher_achiever' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'}`}>
                                        {agent.tier === 'higher_achiever' && <CheckCircle className="h-3 w-3 text-white" />}
                                    </div>
                                    <span className={`text-[10px] font-black uppercase tracking-widest ${agent.tier === 'higher_achiever' ? 'text-slate-900' : 'text-slate-400'}`}>Higher Achiever</span>
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                            <Calendar className="h-3 w-3" /> Updated {new Date().toLocaleDateString('en-GB')}
                        </div>
                    </div>
                </div>

                {/* Stats Grid - Smaller Containers */}
                <div className="mb-8 grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400">Total Referrals</p>
                            <div className="text-xl font-black text-slate-900 dark:text-white">{stats.total_referrals}</div>
                        </div>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 text-emerald-600">Cash Referrals</p>
                            <div className="text-xl font-black text-slate-900 dark:text-white">{cashReferrals}</div>
                        </div>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 text-blue-600">Credit Referrals</p>
                            <div className="text-xl font-black text-slate-900 dark:text-white">{creditReferrals}</div>
                        </div>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1 text-center border-l-4 border-emerald-500">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400">Cash Sales</p>
                            <div className="text-xl font-black text-emerald-600">{cashReferrals}</div>
                        </div>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1 text-center border-l-4 border-blue-500">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400">Credit Sales</p>
                            <div className="text-xl font-black text-blue-600">{creditReferrals}</div>
                        </div>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <div className="p-4 space-y-1">
                            <p className="text-[9px] font-black uppercase tracking-widest text-slate-400 text-amber-600">Pending Comm.</p>
                            <div className="text-xl font-black text-slate-900 dark:text-white">${stats.pending_commission.toFixed(2)}</div>
                        </div>
                    </Card>
                </div>

                {/* Milestone Targets */}
                <div className="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 p-5">
                        <div className="flex justify-between items-center mb-4">
                            <div className="flex items-center gap-2">
                                <Target className="h-4 w-4 text-emerald-600" />
                                <h4 className="text-xs font-black uppercase tracking-widest">Weekly Commission Target</h4>
                            </div>
                            <span className="text-[10px] font-bold text-slate-400">${milestones.weekly_commission.toFixed(2)} / ${milestones.weekly_target}</span>
                        </div>
                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${Math.min((milestones.weekly_commission / milestones.weekly_target) * 100, 100)}%` }}></div>
                        </div>
                        <p className="mt-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Target: $150.00 for 1.5% Commission & Data Reward</p>
                    </Card>
                    <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 p-5">
                        <div className="flex justify-between items-center mb-4">
                            <div className="flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-blue-600" />
                                <h4 className="text-xs font-black uppercase tracking-widest">Daily Link Referrals</h4>
                            </div>
                            <span className="text-[10px] font-bold text-slate-400">{milestones.daily_referrals} / {milestones.daily_target}</span>
                        </div>
                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div className="h-full bg-blue-500 rounded-full" style={{ width: `${Math.min((milestones.daily_referrals / milestones.daily_target) * 100, 100)}%` }}></div>
                        </div>
                        <p className="mt-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Target: 20 daily referrals for consistency</p>
                    </Card>
                </div>

                {/* Generate Referral Link Parent Container */}
                <div className="mb-10 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/60 dark:border-slate-800/60 shadow-sm">
                    <div className="flex items-center gap-3 mb-6">
                        <div className="h-6 w-1 bg-emerald-600 rounded-full"></div>
                        <h3 className="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Generate your referral Link</h3>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {/* Posters & General Link */}
                        <div className="lg:col-span-5 space-y-6">
                            <div className="relative aspect-[4/5] rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm group">
                                <img src={selectedPosterLink.poster} className="w-full h-full object-cover" />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent p-5 flex flex-col justify-end">
                                    <p className="text-sm font-black text-white">{selectedPosterLink.name}</p>
                                    <p className="text-[10px] font-medium text-slate-300 line-clamp-1">{selectedPosterLink.description}</p>
                                </div>
                            </div>
                            <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                                {generalLinks.map((link) => (
                                    <button key={link.id} onClick={() => setSelectedPosterLink(link)} className={`h-12 w-10 shrink-0 rounded-lg border-2 transition-all ${selectedPosterLink.id === link.id ? 'border-emerald-500 scale-105' : 'border-transparent opacity-40'}`}>
                                        <img src={link.poster} className="h-full w-full object-cover rounded-md" />
                                    </button>
                                ))}
                            </div>
                            <div className="space-y-3">
                                <div className="flex gap-2 p-1 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                                    <div className="flex-1 px-3 text-[10px] font-mono font-bold text-slate-400 truncate self-center">{agent.referral_link}</div>
                                    <Button size="icon" variant="ghost" onClick={() => copyLink(agent.referral_link)} className="h-9 w-9 text-slate-600">
                                        {copied ? <CheckCircle className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                    </Button>
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <Button onClick={() => shareWhatsApp(agent.referral_link)} className="bg-[#25D366] hover:bg-[#1DA851] h-10 text-[10px] font-black uppercase tracking-widest">WhatsApp</Button>
                                    <Button onClick={() => shareFacebook(agent.referral_link)} className="bg-[#1877F2] hover:bg-[#166FE5] h-10 text-[10px] font-black uppercase tracking-widest">Facebook</Button>
                                </div>
                            </div>
                        </div>

                        {/* Product Link Generator Container */}
                        <div className="lg:col-span-7 bg-slate-50/50 dark:bg-slate-800/30 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <div className="flex items-center gap-2 mb-6">
                                <ShoppingBag className="h-4 w-4 text-emerald-600" />
                                <h4 className="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Product Link Generator</h4>
                            </div>
                            <div className="grid md:grid-cols-2 gap-8">
                                <div className="space-y-4">
                                    <div className="space-y-1">
                                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest">Category</label>
                                        <Select value={selectedCategoryId} onValueChange={(val) => { setSelectedCategoryId(val); setSelectedSubCategoryId(""); setSelectedProductId(""); setGeneratedLink(""); }}>
                                            <SelectTrigger className="h-10 rounded-xl border-slate-200 bg-white dark:bg-slate-900"><SelectValue placeholder="Industry" /></SelectTrigger>
                                            <SelectContent>{productCategories.map(cat => <SelectItem key={cat.id} value={cat.id.toString()}>{cat.name}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1">
                                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest">Sub-Category</label>
                                        <Select disabled={!selectedCategoryId} value={selectedSubCategoryId} onValueChange={(val) => { setSelectedSubCategoryId(val); setSelectedProductId(""); setGeneratedLink(""); }}>
                                            <SelectTrigger className="h-10 rounded-xl border-slate-200 bg-white dark:bg-slate-900"><SelectValue placeholder="Class" /></SelectTrigger>
                                            <SelectContent>{selectedCategory?.sub_categories.map(sub => <SelectItem key={sub.id} value={sub.id.toString()}>{sub.name}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1">
                                        <label className="text-[9px] font-black text-slate-400 uppercase tracking-widest">Product</label>
                                        <Select disabled={!selectedSubCategoryId} value={selectedProductId} onValueChange={(val) => { setSelectedProductId(val); setGeneratedLink(""); }}>
                                            <SelectTrigger className="h-10 rounded-xl border-slate-200 bg-white dark:bg-slate-900"><SelectValue placeholder="Select Product" /></SelectTrigger>
                                            <SelectContent>{selectedSubCategory?.products.map(prod => <SelectItem key={prod.id} value={prod.id.toString()}>{prod.name}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                    <Button disabled={!selectedProductId} onClick={handleGenerateLink} className="w-full bg-slate-900 h-11 text-[10px] font-black uppercase tracking-widest shadow-xl shadow-slate-900/10">Generate Link</Button>
                                </div>
                                <div className="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-4 bg-white/50 dark:bg-slate-900/50">
                                    {generatedLink ? (
                                        <div className="w-full text-center space-y-4 animate-in zoom-in duration-300">
                                            <div className="h-24 w-24 bg-white dark:bg-slate-900 rounded-2xl mx-auto p-2 shadow-sm flex items-center justify-center border border-slate-100 dark:border-slate-800">
                                                {selectedProduct?.image_url ? <img src={selectedProduct.image_url} className="max-h-full max-w-full object-contain" /> : <ShoppingBag className="h-8 w-8 text-slate-200" />}
                                            </div>
                                            <p className="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Link Ready</p>
                                            <div className="flex gap-2 p-1 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200">
                                                <div className="flex-1 px-2 text-[9px] font-mono font-bold text-slate-400 truncate self-center">{generatedLink}</div>
                                                <button onClick={copyProductLink} className="p-2 text-emerald-600">{productCopied ? <CheckCircle className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}</button>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Select Product</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* History and Activity Row */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Referrals & Commissions Feed */}
                    <div className="lg:col-span-8 space-y-6">
                        <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 rounded-2xl overflow-hidden">
                            <div className="px-6 py-4 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-transparent">
                                <h3 className="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Product Referrals & Commissions</h3>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left">
                                    <thead className="bg-slate-50 dark:bg-slate-800/50">
                                        <tr>
                                            <th className="px-6 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400">Reference</th>
                                            <th className="px-6 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400">Product</th>
                                            <th className="px-6 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400">Status</th>
                                            <th className="px-6 py-3 text-right text-[9px] font-black uppercase tracking-widest text-slate-400">Commission</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-50 dark:divide-slate-800">
                                        {applicationHistory.length > 0 ? applicationHistory.map((app) => (
                                            <tr key={app.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td className="px-6 py-4 text-[10px] font-mono font-bold text-slate-400 uppercase tracking-tighter">{app.reference}</td>
                                                <td className="px-6 py-4">
                                                    <p className="text-xs font-black text-slate-900 dark:text-white truncate max-w-[180px]">{app.product}</p>
                                                    <p className="text-[9px] font-bold text-slate-400 uppercase">{app.date}</p>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest ${app.status === 'approved' ? 'bg-emerald-50 text-emerald-600' : app.status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-400'}`}>{app.status}</span>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <span className="text-xs font-black text-emerald-600">${app.commission.toFixed(2)}</span>
                                                </td>
                                            </tr>
                                        )) : <tr><td colSpan={4} className="px-6 py-10 text-center text-[10px] font-black uppercase text-slate-300">No data available</td></tr>}
                                    </tbody>
                                </table>
                            </div>
                        </Card>

                        {/* Performance Chart */}
                        <PerformanceChart data={monthlyPerformance} />
                    </div>

                    {/* Activity Log Feed */}
                    <div className="lg:col-span-4 space-y-6">
                        <Card className="border-slate-200/60 shadow-sm bg-white dark:bg-slate-900 rounded-2xl overflow-hidden">
                            <div className="px-6 py-4 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-transparent flex items-center justify-between">
                                <h3 className="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Activity Log</h3>
                                <div className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            </div>
                            <div className="p-6 space-y-6 max-h-[400px] overflow-y-auto">
                                {activityLogs.length > 0 ? activityLogs.map((log) => (
                                    <div key={log.id} className="relative pl-5 border-l border-slate-100 dark:border-slate-800 pb-1">
                                        <div className="absolute -left-[3.5px] top-0 h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                                        <p className="text-[8px] font-black text-slate-400 uppercase mb-1">{log.timestamp}</p>
                                        <p className="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">{log.description}</p>
                                    </div>
                                )) : <p className="text-center text-[10px] font-black uppercase text-slate-300 py-10">No recent activity</p>}
                            </div>
                        </Card>

                        {/* Profile Info */}
                        <Card className="border-none bg-slate-900 text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10"><User className="h-16 w-16" /></div>
                            <div className="space-y-4 relative z-10">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center font-black">{agent.first_name[0]}{agent.surname[0]}</div>
                                    <div>
                                        <p className="text-sm font-black">{agent.first_name} {agent.surname}</p>
                                        <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{agent.province}</p>
                                    </div>
                                </div>
                                <div className="space-y-2 pt-2 border-t border-slate-800 mt-2">
                                    <div className="flex items-center justify-between text-[10px] font-bold">
                                        <span className="text-slate-500 uppercase">Last Commission</span>
                                        <span className="text-emerald-400">${agent.last_commission_amount.toFixed(2)}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-[10px] font-bold text-slate-300"><Phone className="h-3 w-3 text-emerald-500" /> {agent.whatsapp_contact}</div>
                                    <div className="flex items-center gap-2 text-[10px] font-bold text-slate-300"><CreditCard className="h-3 w-3 text-emerald-500" /> {agent.ecocash_number}</div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </main>

            <footer className="py-10 text-center opacity-30">
                <p className="text-[9px] font-black uppercase tracking-[0.4em] text-slate-900 dark:text-white">Microbiz Agent Ecosystem &copy; 2026</p>
            </footer>
        </div>
    );
}
