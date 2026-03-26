import { Head, Link } from '@inertiajs/react';
import { Copy, ExternalLink, LogOut, Share2, Store, User, MapPin, Phone, CreditCard, Calendar, CheckCircle, BarChart, ShoppingBag, MessageSquare, ChevronRight, LayoutDashboard } from 'lucide-react';
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

    // Link Generator State
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
        
        // Log Activity
        fetch(route('agent.log.activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                type: 'link_copied',
                description: 'Copied general referral link',
            }),
        });
    };

    const copyProductLink = () => {
        if (!generatedLink) return;
        navigator.clipboard.writeText(generatedLink);
        setProductCopied(true);
        setTimeout(() => setProductCopied(false), 2000);

        // Log Activity
        fetch(route('agent.log.activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                type: 'link_copied',
                description: 'Copied product referral link for product ID: ' + selectedProductId,
            }),
        });
    };

    const handleGenerateLink = () => {
        if (!selectedProductId) return;
        const link = `${window.location.origin}/apply?ref=${agent.agent_code}&product_id=${selectedProductId}`;
        setGeneratedLink(link);
    };

    const shareWhatsApp = (link: string) => {
        const text = `🌟 Start your own business with Microbiz Zimbabwe! Get gadgets, furniture, solar systems on credit. Apply now: ${link}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const shareFacebook = (link: string) => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`, '_blank');
    };

    // Chart logic (Simple SVG Chart)
    const maxVal = Math.max(...monthlyPerformance.map(d => Math.max(d.visits, d.referrals * 10)), 100);

    return (
        <>
            <Head title="Agent Dashboard" />

            <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
                {/* Deactivation Alert */}
                {agent.is_deactivated && (
                    <div className="bg-red-600 text-white p-4 text-center font-bold sticky top-0 z-50 shadow-lg">
                        <div className="container mx-auto flex items-center justify-center gap-4 flex-wrap">
                            <div className="flex items-center gap-2">
                                <span className="text-xl">⚠️</span>
                                <span>Your account has been deactivated due to inactivity for more than 30 days.</span>
                            </div>
                            <Link 
                                href={route('agent.reactivate')} 
                                method="post" 
                                as="button"
                                className="bg-white text-red-600 px-6 py-2 rounded-full text-sm font-black hover:bg-slate-100 transition-all active:scale-95 shadow-md"
                            >
                                CLICK HERE TO REACTIVATE
                            </Link>
                        </div>
                    </div>
                )}

                {/* Header */}
                <header className="border-b bg-white dark:bg-slate-900 sticky top-0 z-10 shadow-sm">
                    <div className="container mx-auto px-4 py-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <img src="/adala.jpg" alt="Logo" className="h-10 w-auto rounded" />
                            <div className="hidden sm:block">
                                <h1 className="text-lg font-bold text-slate-900 dark:text-white leading-tight">Microbiz Zimbabwe</h1>
                                <p className="text-xs text-slate-500 font-medium">Agent Portal</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 sm:gap-4">
                            <div className="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-full text-sm font-bold border border-emerald-200 dark:border-emerald-800">
                                <Store className="h-4 w-4" />
                                <span className="hidden xs:inline">AGENT PORTAL</span>
                            </div>
                            <a href={route('agent.logout')} className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-all duration-200">
                                <LogOut className="h-5 w-5" />
                            </a>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="container mx-auto px-4 py-8 max-w-7xl">
                    {/* Welcome */}
                    <div className="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6 bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800">
                        <div className="space-y-4">
                            <div className="flex flex-wrap items-center gap-4">
                                <h2 className="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                                    Hi, {agent.first_name} <span className="text-emerald-600 dark:text-emerald-500 ml-2 text-xl font-mono">AGENT CODE: {agent.agent_code}</span>
                                </h2>
                                
                                <div className="flex items-center gap-4 bg-slate-50 dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <div className={`h-4 w-4 rounded-full border-2 flex items-center justify-center ${agent.tier === 'ordinary' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'}`}>
                                            {agent.tier === 'ordinary' && <div className="h-1.5 w-1.5 bg-white rounded-full"></div>}
                                        </div>
                                        <span className="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">Ordinary</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <div className={`h-4 w-4 rounded-full border-2 flex items-center justify-center ${agent.tier === 'higher_achiever' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'}`}>
                                            {agent.tier === 'higher_achiever' && <div className="h-1.5 w-1.5 bg-white rounded-full"></div>}
                                        </div>
                                        <span className="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">Higher Achiever</span>
                                    </label>
                                </div>
                            </div>
                            <p className="text-slate-500 font-medium flex items-center gap-2">
                                <LayoutDashboard className="h-4 w-4" />
                                Here's an overview of your sales activity
                            </p>
                        </div>
                        <div className="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <Calendar className="h-4 w-4" />
                            Updated {new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-emerald-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-black text-slate-500 uppercase tracking-widest">TOTAL REFERRALS</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">📊</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black text-slate-900 dark:text-white">{stats.total_referrals}</div>
                                <p className="text-xs text-slate-400 mt-1">From all channels</p>
                            </CardContent>
                        </Card>
                        
                        <div className="grid grid-cols-2 gap-4 lg:col-span-1">
                            <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                                <div className="h-1 bg-blue-500"></div>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-[10px] font-black text-slate-500 uppercase tracking-widest">CASH SALES</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-black text-slate-900 dark:text-white">{cashReferrals}</div>
                                </CardContent>
                            </Card>
                            <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                                <div className="h-1 bg-indigo-500"></div>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-[10px] font-black text-slate-500 uppercase tracking-widest">CREDIT SALES</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-black text-slate-900 dark:text-white">{creditReferrals}</div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-amber-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-black text-slate-500 uppercase tracking-widest">PENDING COMM.</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">⏳</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black text-slate-900 dark:text-white">${stats.pending_commission.toFixed(2)}</div>
                                <p className="text-xs text-slate-400 mt-1 uppercase font-bold tracking-tighter text-amber-600">For {new Date().toLocaleDateString('en-US', { month: 'long' })}</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-purple-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-black text-slate-500 uppercase tracking-widest">TOTAL EARNED</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">💰</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-4xl font-black text-slate-900 dark:text-white">${stats.total_earned.toFixed(2)}</div>
                                <p className="text-xs text-slate-400 mt-1">Paid to EcoCash</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Generate your referral Link Parent Container */}
                    <div className="mb-12 bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800">
                        <div className="flex items-center gap-3 mb-8">
                            <div className="h-10 w-2 bg-emerald-600 rounded-full"></div>
                            <h3 className="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Generate your referral Link</h3>
                        </div>

                        <div className="grid gap-8 lg:grid-cols-3">
                            {/* Referral Link & Posters Card */}
                            <Card className="lg:col-span-1 shadow-md border-none bg-slate-50 dark:bg-slate-800/50 overflow-hidden">
                                <CardHeader className="bg-slate-900 text-white">
                                    <CardTitle className="flex items-center gap-2 text-white">
                                        <Share2 className="h-5 w-5 text-emerald-400" />
                                        General Referral Link
                                    </CardTitle>
                                    <CardDescription className="text-slate-400">Choose a poster for your link</CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {/* Poster Preview */}
                                    <div className="aspect-[4/5] relative overflow-hidden group">
                                        <img 
                                            src={selectedPosterLink.poster} 
                                            alt={selectedPosterLink.name} 
                                            className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex flex-col justify-end p-6">
                                            <h4 className="text-white font-black text-xl mb-1">{selectedPosterLink.name}</h4>
                                            <p className="text-slate-300 text-xs line-clamp-2">{selectedPosterLink.description}</p>
                                        </div>
                                    </div>

                                    <div className="p-6 space-y-6">
                                        {/* Poster Selection */}
                                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                                            {generalLinks.map((link) => (
                                                <button
                                                    key={link.id}
                                                    onClick={() => setSelectedPosterLink(link)}
                                                    className={`h-16 w-12 rounded-lg border-2 shrink-0 overflow-hidden transition-all ${selectedPosterLink.id === link.id ? 'border-emerald-500 scale-105 shadow-md' : 'border-slate-200 dark:border-slate-800 grayscale opacity-60'}`}
                                                >
                                                    <img src={link.poster} alt={link.name} className="h-full w-full object-cover" />
                                                </button>
                                            ))}
                                        </div>

                                        <div className="space-y-4">
                                            <div className="flex gap-2">
                                                <div className="flex-1 bg-slate-50 dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                                    <p className="text-[10px] text-slate-500 truncate font-mono">{agent.referral_link}</p>
                                                </div>
                                                <Button onClick={() => copyLink(agent.referral_link)} size="icon" className="bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/20 shrink-0 h-12 w-12">
                                                    {copied ? <CheckCircle className="h-5 w-5" /> : <Copy className="h-5 w-5" />}
                                                </Button>
                                            </div>

                                            <div className="grid grid-cols-2 gap-3">
                                                <Button onClick={() => shareWhatsApp(agent.referral_link)} className="bg-[#25D366] hover:bg-[#128C7E] text-white font-black border-none uppercase text-xs h-11">
                                                    WhatsApp
                                                </Button>
                                                <Button onClick={() => shareFacebook(agent.referral_link)} className="bg-[#1877F2] hover:bg-[#0d6efd] text-white font-black border-none uppercase text-xs h-11">
                                                    Facebook
                                                </Button>
                                            </div>

                                            <div className="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-900/20 text-center">
                                                <p className="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-widest font-black mb-1">Commission for Agents</p>
                                                <p className="text-2xl font-black text-emerald-700 dark:text-emerald-300">{agent.tier === 'higher_achiever' ? '1.5%' : '1.0%'} FOR EACH SUCCESSFUL</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Product Link Generator */}
                            <Card className="lg:col-span-2 shadow-md border-none bg-white dark:bg-slate-900 overflow-hidden">
                                <CardHeader className="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                    <CardTitle className="flex items-center gap-2">
                                        <ShoppingBag className="h-5 w-5 text-emerald-600" />
                                        Product Link Generator
                                    </CardTitle>
                                    <CardDescription>Create links for specific items to increase conversions</CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="grid gap-8 md:grid-cols-2">
                                        <div className="space-y-6">
                                            <div className="space-y-2">
                                                <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest">1. SELECT CATEGORY</label>
                                                <Select value={selectedCategoryId} onValueChange={(val) => {
                                                    setSelectedCategoryId(val);
                                                    setSelectedSubCategoryId("");
                                                    setSelectedProductId("");
                                                    setGeneratedLink("");
                                                }}>
                                                    <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 h-12 rounded-xl">
                                                        <SelectValue placeholder="Choose a category" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {productCategories.map(cat => (
                                                            <SelectItem key={cat.id} value={cat.id.toString()}>{cat.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest">2. SELECT SUB-CATEGORY</label>
                                                <Select 
                                                    disabled={!selectedCategoryId} 
                                                    value={selectedSubCategoryId} 
                                                    onValueChange={(val) => {
                                                        setSelectedSubCategoryId(val);
                                                        setSelectedProductId("");
                                                        setGeneratedLink("");
                                                    }}
                                                >
                                                    <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 h-12 rounded-xl">
                                                        <SelectValue placeholder="Choose a sub-category" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {selectedCategory?.sub_categories.map(sub => (
                                                            <SelectItem key={sub.id} value={sub.id.toString()}>{sub.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest">3. SELECT PRODUCT</label>
                                                <Select 
                                                    disabled={!selectedSubCategoryId} 
                                                    value={selectedProductId} 
                                                    onValueChange={(val) => {
                                                        setSelectedProductId(val);
                                                        setGeneratedLink("");
                                                    }}
                                                >
                                                    <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 h-12 rounded-xl">
                                                        <SelectValue placeholder="Choose a product" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {selectedSubCategory?.products.map(prod => (
                                                            <SelectItem key={prod.id} value={prod.id.toString()}>{prod.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <Button 
                                                disabled={!selectedProductId} 
                                                onClick={handleGenerateLink}
                                                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black h-12 rounded-xl uppercase tracking-widest text-xs"
                                            >
                                                Generate Product Link
                                            </Button>
                                        </div>

                                        <div className="flex flex-col items-center justify-center p-8 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                                            {generatedLink ? (
                                                <div className="w-full space-y-6">
                                                    <div className="text-center">
                                                        <div className="h-32 w-32 bg-white dark:bg-slate-900 rounded-2xl shadow-sm mx-auto mb-4 flex items-center justify-center p-3 overflow-hidden border border-slate-100 dark:border-slate-800">
                                                            {selectedProduct?.image_url ? (
                                                                <img src={selectedProduct.image_url} alt={selectedProduct.name} className="h-full w-full object-contain" />
                                                            ) : (
                                                                <ShoppingBag className="h-12 w-12 text-slate-200" />
                                                            )}
                                                        </div>
                                                        <h4 className="font-black text-slate-900 dark:text-white line-clamp-1 text-lg">{selectedProduct?.name}</h4>
                                                        <p className="text-[10px] text-emerald-600 font-black uppercase tracking-widest mt-1">Ready to Share</p>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <div className="flex-1 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                            <p className="text-[10px] text-slate-500 truncate font-mono">{generatedLink}</p>
                                                        </div>
                                                        <Button onClick={copyProductLink} size="icon" className="shrink-0 h-12 w-12 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 shadow-none border">
                                                            {productCopied ? <CheckCircle className="h-5 w-5" /> : <Copy className="h-5 w-5" />}
                                                        </Button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="text-center space-y-4 opacity-50">
                                                    <div className="h-20 w-20 bg-slate-200 dark:bg-slate-700 rounded-full mx-auto flex items-center justify-center">
                                                        <ExternalLink className="h-10 w-10 text-slate-400" />
                                                    </div>
                                                    <p className="text-sm font-black text-slate-500 uppercase tracking-widest">Preview will appear here</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* Milestones & Activity & History Section */}
                    <div className="grid gap-8 lg:grid-cols-3 mb-12">
                        {/* Interactive Dashboards with milestones */}
                        <div className="lg:col-span-2 space-y-8">
                            <Card className="shadow-md border-none bg-white dark:bg-slate-900 overflow-hidden">
                                <CardHeader className="bg-emerald-600 text-white">
                                    <CardTitle className="flex items-center gap-2">
                                        <BarChart className="h-5 w-5" />
                                        Performance Milestones
                                    </CardTitle>
                                    <CardDescription className="text-emerald-100">Reach these targets to unlock higher commissions</CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="grid gap-6 md:grid-cols-2">
                                        <div className="space-y-4">
                                            <div className="flex justify-between items-end">
                                                <div>
                                                    <p className="text-xs font-black text-slate-400 uppercase tracking-widest">Weekly Commission Target</p>
                                                    <p className="text-2xl font-black text-slate-900 dark:text-white">${milestones.weekly_commission.toFixed(2)} / ${milestones.weekly_target}</p>
                                                </div>
                                                <p className="text-xs font-black text-emerald-600">{Math.round((milestones.weekly_commission / milestones.weekly_target) * 100)}%</p>
                                            </div>
                                            <div className="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div 
                                                    className="h-full bg-emerald-500 rounded-full transition-all duration-1000"
                                                    style={{ width: `${Math.min((milestones.weekly_commission / milestones.weekly_target) * 100, 100)}%` }}
                                                ></div>
                                            </div>
                                            <p className="text-[10px] text-slate-500 font-medium italic">Reward: 5GB (NetOne) | 4GB (Econet) Data</p>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="flex justify-between items-end">
                                                <div>
                                                    <p className="text-xs font-black text-slate-400 uppercase tracking-widest">Daily Link Referrals Target</p>
                                                    <p className="text-2xl font-black text-slate-900 dark:text-white">{milestones.daily_referrals} / {milestones.daily_target}</p>
                                                </div>
                                                <p className="text-xs font-black text-blue-600">{Math.round((milestones.daily_referrals / milestones.daily_target) * 100)}%</p>
                                            </div>
                                            <div className="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div 
                                                    className="h-full bg-blue-500 rounded-full transition-all duration-1000"
                                                    style={{ width: `${Math.min((milestones.daily_referrals / milestones.daily_target) * 100, 100)}%` }}
                                                ></div>
                                            </div>
                                            <p className="text-[10px] text-slate-500 font-medium italic">Minimum 20 referrals daily to maintain active status</p>
                                        </div>
                                    </div>

                                    <div className="mt-8 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                                        <div className="flex items-center gap-3">
                                            <div className="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                                <CheckCircle className="h-6 w-6" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Become a Higher Achiever</p>
                                                <p className="text-xs text-slate-500">Hit a minimum of $150 commissions per week to earn 1.5% commission rate!</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Application History */}
                            <Card className="shadow-md border-none bg-white dark:bg-slate-900 overflow-hidden">
                                <CardHeader>
                                    <CardTitle className="text-lg font-black uppercase tracking-tight">Application Status & Commissions</CardTitle>
                                    <CardDescription>All products applied for through your links</CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left border-collapse">
                                            <thead className="bg-slate-50 dark:bg-slate-800/50 border-y border-slate-100 dark:border-slate-800">
                                                <tr>
                                                    <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reference</th>
                                                    <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Product</th>
                                                    <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                                    <th className="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Commission</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                                {applicationHistory.length > 0 ? applicationHistory.map((app) => (
                                                    <tr key={app.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                        <td className="px-6 py-4 text-xs font-mono text-slate-500">{app.reference}</td>
                                                        <td className="px-6 py-4">
                                                            <p className="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[200px]">{app.product}</p>
                                                            <p className="text-[10px] text-slate-400">{app.date}</p>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className={`px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest ${
                                                                app.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                                                app.status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                            }`}>
                                                                {app.status}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 text-right">
                                                            <span className={`text-sm font-black ${app.commission > 0 ? 'text-emerald-600' : 'text-slate-400'}`}>
                                                                ${app.commission.toFixed(2)}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                )) : (
                                                    <tr>
                                                        <td colSpan={4} className="px-6 py-12 text-center text-slate-400 text-sm italic">No application history found.</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Activity Log & Profile */}
                        <div className="space-y-8">
                            {/* Activity Log */}
                            <Card className="shadow-md border-none bg-white dark:bg-slate-900 overflow-hidden">
                                <CardHeader className="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                    <CardTitle className="text-base font-black uppercase tracking-widest flex items-center gap-2">
                                        <div className="h-2 w-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        Activity Log
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-0 max-h-[400px] overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200">
                                    <div className="p-4 space-y-6">
                                        {activityLogs.length > 0 ? activityLogs.map((log) => (
                                            <div key={log.id} className="relative pl-6 border-l-2 border-slate-100 dark:border-slate-800 pb-2 last:pb-0">
                                                <div className="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-white dark:bg-slate-900 border-2 border-emerald-500"></div>
                                                <p className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{log.timestamp}</p>
                                                <p className="text-sm font-bold text-slate-900 dark:text-white leading-tight">{log.description}</p>
                                            </div>
                                        )) : (
                                            <p className="text-center text-slate-400 text-xs py-8 italic">No recent activity.</p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="shadow-md border-none bg-white dark:bg-slate-900">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-base font-black uppercase tracking-widest">
                                        <Calendar className="h-5 w-5 text-emerald-600" />
                                        Account Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-800">
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Last Commission</span>
                                        <span className="font-black text-emerald-600">${agent.last_commission_amount.toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-800">
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Last Payout Date</span>
                                        <span className="font-black text-slate-900 dark:text-white">
                                            {lastCommissionDate ? new Date(lastCommissionDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'None yet'}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-800">
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Account Status</span>
                                        <span className={`px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest ${agent.is_deactivated ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                            {agent.is_deactivated ? 'Deactivated' : 'Active'}
                                        </span>
                                    </div>
                                    
                                    {/* Supervisor Feedback */}
                                    <div className="pt-2">
                                        <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Supervisor Feedback</label>
                                        <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 p-4 rounded-xl">
                                            <div className="flex gap-3">
                                                <MessageSquare className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                                                <p className="text-xs text-amber-800 dark:text-amber-200 italic font-medium leading-relaxed">
                                                    {supervisorComment || "Your performance is being monitored. Keep up the good work and share more links!"}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="shadow-md border-none bg-white dark:bg-slate-900">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-base font-black uppercase tracking-widest">
                                        <User className="h-5 w-5 text-emerald-600" />
                                        Agent Profile
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                        <div className="h-12 w-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-emerald-500/20">
                                            {agent.first_name[0]}{agent.surname[0]}
                                        </div>
                                        <div>
                                            <p className="text-base font-black text-slate-900 dark:text-white leading-tight">{agent.first_name} {agent.surname}</p>
                                            <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">{agent.province}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-2 px-1">
                                        <div className="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                            <div className="h-7 w-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                                <Phone className="h-3.5 w-3.5" />
                                            </div>
                                            {agent.whatsapp_contact}
                                        </div>
                                        <div className="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                            <div className="h-7 w-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                                <CreditCard className="h-3.5 w-3.5" />
                                            </div>
                                            {agent.ecocash_number}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
