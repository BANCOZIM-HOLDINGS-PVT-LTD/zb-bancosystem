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
    monthlyPerformance: {
        month: string;
        visits: number;
        referrals: number;
    }[];
}

export default function AgentDashboard({ 
    agent, 
    stats, 
    lastCommissionDate, 
    cashReferrals, 
    creditReferrals, 
    supervisorComment,
    productCategories,
    monthlyPerformance
}: AgentDashboardProps) {
    const [copied, setCopied] = useState(false);
    const [productCopied, setProductCopied] = useState(false);

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

    const copyLink = () => {
        navigator.clipboard.writeText(agent.referral_link);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const copyProductLink = () => {
        if (!generatedLink) return;
        navigator.clipboard.writeText(generatedLink);
        setProductCopied(true);
        setTimeout(() => setProductCopied(false), 2000);
    };

    const handleGenerateLink = () => {
        if (!selectedProductId) return;
        const link = `${window.location.origin}/apply?ref=${agent.agent_code}&product_id=${selectedProductId}`;
        setGeneratedLink(link);
    };

    const shareWhatsApp = () => {
        const text = `🌟 Start your own business with Microbiz Zimbabwe! Get gadgets, furniture, solar systems on credit. Apply now: ${agent.referral_link}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const shareFacebook = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(agent.referral_link)}`, '_blank');
    };

    // Chart logic (Simple SVG Chart)
    const maxVal = Math.max(...monthlyPerformance.map(d => Math.max(d.visits, d.referrals * 10)), 100);
    const chartHeight = 200;
    const chartWidth = 600;

    return (
        <>
            <Head title="Agent Dashboard" />

            <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
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
                    <div className="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800">
                        <div>
                            <h2 className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                                Hi, {agent.first_name} <span className="text-emerald-600 dark:text-emerald-500 ml-2 text-xl font-mono">AGENT CODE: {agent.agent_code}</span>
                            </h2>
                            <p className="text-slate-500 mt-1 font-medium flex items-center gap-2">
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
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Referrals</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">📊</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-black text-slate-900 dark:text-white">{stats.total_referrals}</div>
                                <p className="text-xs text-slate-400 mt-1">From all channels</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-blue-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Converted to Sales</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">✅</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-black text-slate-900 dark:text-white">{stats.successful_referrals}</div>
                                <p className="text-xs text-slate-400 mt-1">Successful applications</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-amber-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Pending Comm.</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">⏳</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-black text-slate-900 dark:text-white">${stats.pending_commission.toFixed(2)}</div>
                                <p className="text-xs text-slate-400 mt-1">Ready for next payout</p>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden group hover:shadow-lg transition-shadow duration-300">
                            <div className="h-1 bg-purple-500"></div>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Earned</CardTitle>
                                <div className="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">💰</div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-black text-slate-900 dark:text-white">${stats.total_earned.toFixed(2)}</div>
                                <p className="text-xs text-slate-400 mt-1">Paid to EcoCash</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Secondary Stats (Cash vs Credit) */}
                    <div className="grid gap-6 grid-cols-1 md:grid-cols-2 mb-8">
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden border-l-4 border-l-emerald-500">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Cash Referrals</CardTitle>
                            </CardHeader>
                            <CardContent className="flex items-center justify-between">
                                <div className="text-4xl font-black text-slate-900 dark:text-white">{cashReferrals}</div>
                                <div className="text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 p-2 rounded-lg font-bold text-sm">
                                    {stats.total_referrals > 0 ? Math.round((cashReferrals / stats.total_referrals) * 100) : 0}% of total
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="border-none shadow-md bg-white dark:bg-slate-900 overflow-hidden border-l-4 border-l-blue-500">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-bold text-slate-500 uppercase tracking-wider">Credit Referrals</CardTitle>
                            </CardHeader>
                            <CardContent className="flex items-center justify-between">
                                <div className="text-4xl font-black text-slate-900 dark:text-white">{creditReferrals}</div>
                                <div className="text-blue-500 bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg font-bold text-sm">
                                    {stats.total_referrals > 0 ? Math.round((creditReferrals / stats.total_referrals) * 100) : 0}% of total
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Link Generator & Referral Section */}
                    <div className="grid gap-8 lg:grid-cols-3 mb-8">
                        {/* Referral Link Card */}
                        <Card className="lg:col-span-1 shadow-md border-none bg-gradient-to-br from-slate-900 to-slate-800 text-white">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-white">
                                    <Share2 className="h-5 w-5 text-emerald-400" />
                                    General Referral Link
                                </CardTitle>
                                <CardDescription className="text-slate-400">Main link for all products</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <div className="flex gap-2">
                                        <div className="flex-1 bg-white/10 p-3 rounded-lg border border-white/20">
                                            <input
                                                type="text"
                                                value={agent.referral_link}
                                                readOnly
                                                className="w-full bg-transparent text-sm outline-none font-medium text-emerald-50"
                                            />
                                        </div>
                                        <Button onClick={copyLink} size="icon" className="bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/20 shrink-0">
                                            {copied ? <CheckCircle className="h-5 w-5" /> : <Copy className="h-5 w-5" />}
                                        </Button>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <Button onClick={shareWhatsApp} className="bg-[#25D366] hover:bg-[#128C7E] text-white font-bold border-none">
                                        WhatsApp
                                    </Button>
                                    <Button onClick={shareFacebook} className="bg-[#1877F2] hover:bg-[#0d6efd] text-white font-bold border-none">
                                        Facebook
                                    </Button>
                                </div>

                                <div className="bg-white/5 p-4 rounded-xl border border-white/10 text-center">
                                    <p className="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">Earnings Multiplier</p>
                                    <p className="text-xl font-black text-emerald-400">1.5% - 5.0%</p>
                                    <p className="text-[10px] text-slate-500 mt-1 italic">Varies by product category</p>
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
                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <label className="text-xs font-black text-slate-500 uppercase tracking-wider">1. Select Category</label>
                                            <Select value={selectedCategoryId} onValueChange={(val) => {
                                                setSelectedCategoryId(val);
                                                setSelectedSubCategoryId("");
                                                setSelectedProductId("");
                                                setGeneratedLink("");
                                            }}>
                                                <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
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
                                            <label className="text-xs font-black text-slate-500 uppercase tracking-wider">2. Select Sub-Category</label>
                                            <Select 
                                                disabled={!selectedCategoryId} 
                                                value={selectedSubCategoryId} 
                                                onValueChange={(val) => {
                                                    setSelectedSubCategoryId(val);
                                                    setSelectedProductId("");
                                                    setGeneratedLink("");
                                                }}
                                            >
                                                <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
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
                                            <label className="text-xs font-black text-slate-500 uppercase tracking-wider">3. Select Product</label>
                                            <Select 
                                                disabled={!selectedSubCategoryId} 
                                                value={selectedProductId} 
                                                onValueChange={(val) => {
                                                    setSelectedProductId(val);
                                                    setGeneratedLink("");
                                                }}
                                            >
                                                <SelectTrigger className="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
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
                                            className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold h-11"
                                        >
                                            Generate Product Link
                                        </Button>
                                    </div>

                                    <div className="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                                        {generatedLink ? (
                                            <div className="w-full space-y-4">
                                                <div className="text-center">
                                                    <div className="h-24 w-24 bg-white dark:bg-slate-900 rounded-xl shadow-sm mx-auto mb-3 flex items-center justify-center p-2 overflow-hidden border border-slate-100 dark:border-slate-800">
                                                        {selectedProduct?.image_url ? (
                                                            <img src={selectedProduct.image_url} alt={selectedProduct.name} className="h-full w-full object-contain" />
                                                        ) : (
                                                            <ShoppingBag className="h-10 w-10 text-slate-200" />
                                                        )}
                                                    </div>
                                                    <h4 className="font-bold text-slate-900 dark:text-white line-clamp-1">{selectedProduct?.name}</h4>
                                                    <p className="text-[10px] text-emerald-600 font-bold uppercase tracking-widest mt-1">Ready to Share</p>
                                                </div>
                                                <div className="flex gap-2">
                                                    <div className="flex-1 bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700 overflow-hidden">
                                                        <p className="text-[10px] text-slate-500 truncate">{generatedLink}</p>
                                                    </div>
                                                    <Button onClick={copyProductLink} size="sm" variant="outline" className="shrink-0 h-10 w-10 p-0 border-emerald-200 text-emerald-600 hover:bg-emerald-50">
                                                        {productCopied ? <CheckCircle className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="text-center space-y-2 opacity-50">
                                                <div className="h-16 w-16 bg-slate-200 dark:bg-slate-700 rounded-full mx-auto flex items-center justify-center">
                                                    <ExternalLink className="h-8 w-8 text-slate-400" />
                                                </div>
                                                <p className="text-sm font-medium text-slate-500">Preview will appear here</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Chart & Profile Section */}
                    <div className="grid gap-8 lg:grid-cols-3 mb-8">
                        {/* Activity Graph */}
                        <Card className="lg:col-span-2 shadow-md border-none bg-white dark:bg-slate-900 overflow-hidden">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <BarChart className="h-5 w-5 text-emerald-600" />
                                    Performance Activity
                                </CardTitle>
                                <CardDescription>Monthly link visits vs successful referrals</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[250px] w-full flex items-end justify-between gap-2 px-2 pb-8 pt-4">
                                    {monthlyPerformance.map((item, idx) => (
                                        <div key={idx} className="flex-1 flex flex-col items-center gap-2 group relative">
                                            {/* Tooltip */}
                                            <div className="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-[10px] p-2 rounded shadow-lg z-10 pointer-events-none whitespace-nowrap">
                                                Visits: {item.visits} | Sales: {item.referrals}
                                            </div>
                                            
                                            <div className="w-full flex justify-center items-end gap-1 h-[200px]">
                                                {/* Visits Bar */}
                                                <div 
                                                    className="w-1/3 bg-slate-100 dark:bg-slate-800 rounded-t-sm transition-all duration-500"
                                                    style={{ height: `${(item.visits / maxVal) * 100}%` }}
                                                ></div>
                                                {/* Referrals Bar */}
                                                <div 
                                                    className="w-1/3 bg-emerald-500 rounded-t-sm transition-all duration-500 group-hover:bg-emerald-400"
                                                    style={{ height: `${(item.referrals * 10 / maxVal) * 100}%` }}
                                                ></div>
                                            </div>
                                            <span className="text-[10px] font-bold text-slate-400 uppercase transform -rotate-45 sm:rotate-0 mt-2">{item.month.split(' ')[0]}</span>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex justify-center gap-6 mt-4">
                                    <div className="flex items-center gap-2">
                                        <div className="h-3 w-3 bg-slate-200 dark:bg-slate-700 rounded-sm"></div>
                                        <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Link Visits</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-3 w-3 bg-emerald-500 rounded-sm"></div>
                                        <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sales Converted</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Account & Profile */}
                        <div className="space-y-6">
                            <Card className="shadow-md border-none bg-white dark:bg-slate-900">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Calendar className="h-5 w-5 text-emerald-600" />
                                        Account Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-800">
                                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Last Commission</span>
                                        <span className="font-black text-slate-900 dark:text-white">
                                            {lastCommissionDate ? new Date(lastCommissionDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'None yet'}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-slate-50 dark:border-slate-800">
                                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Account Status</span>
                                        <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-[10px] font-black uppercase tracking-widest">Active</span>
                                    </div>
                                    
                                    {/* Supervisor Comment */}
                                    <div className="pt-2">
                                        <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Supervisor Feedback</label>
                                        <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 p-3 rounded-xl">
                                            <div className="flex gap-2">
                                                <MessageSquare className="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
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
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <User className="h-5 w-5 text-emerald-600" />
                                        Agent Profile
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                        <div className="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 font-black text-lg">
                                            {agent.first_name[0]}{agent.surname[0]}
                                        </div>
                                        <div>
                                            <p className="text-sm font-black text-slate-900 dark:text-white leading-tight">{agent.first_name} {agent.surname}</p>
                                            <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{agent.province}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2 text-xs text-slate-500">
                                            <Phone className="h-3 w-3" /> {agent.whatsapp_contact}
                                        </div>
                                        <div className="flex items-center gap-2 text-xs text-slate-500">
                                            <CreditCard className="h-3 w-3" /> {agent.ecocash_number}
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
