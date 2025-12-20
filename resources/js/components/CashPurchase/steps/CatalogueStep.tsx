import { useState, useEffect } from 'react';
import { Search, ChevronRight, Check, Loader2, Tag, TrendingDown, ShoppingCart, Plus, Minus, X, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { productService } from '@/services/productService';

interface Product {
    id: number;
    name: string;
    cashPrice: number;
    loanPrice: number;
    category: string;
    description?: string;
    image?: string;
}

interface CartItem extends Product {
    quantity: number;
}

interface CatalogueStepProps {
    purchaseType: 'personal' | 'microbiz';
    cart: CartItem[];
    onUpdateCart: (cart: CartItem[]) => void;
    onNext: () => void;
    onBack: () => void;
    currency: string;
}

const ZIG_RATE = 35;

const allowedZiGKeywords = [
    // Personal
    'Techno', 'Redmi', 'Samsung',
    'Building materials', 'holiday', 'school fees',
    'back to school', 'baby',
    'Zimparks',
    'vacation',
    // Microbiz
    'Agriculture', // Allow full Agriculture category
    'Broiler', 'Grocery', 'Tuckshop', 'Tuck shop', 'Groceries',
    // Agricultural Mechanization specific items
    'water storage', 'pumping system', 'maize sheller', 'irrigation', 'land security'
];

export default function CatalogueStep({
    purchaseType,
    cart = [], // Default to empty array to prevent "undefined" errors
    onUpdateCart,
    onNext,
    onBack,
    currency
}: CatalogueStepProps) {
    const [products, setProducts] = useState<Product[]>([]);
    const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<string[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(true);
    const [isCartOpen, setIsCartOpen] = useState(false);

    useEffect(() => {
        loadProducts();
    }, [purchaseType, currency]);

    useEffect(() => {
        filterProducts();
    }, [selectedCategory, searchQuery, products]);

    const loadProducts = async () => {
        setLoading(true);
        try {
            // Get products based on purchase type
            const intent = purchaseType === 'personal' ? 'hirePurchase' : 'microBiz';
            const categoriesData = await productService.getProductCategories(intent);

            // Flatten products from all categories and add cash pricing
            const allProducts: Product[] = [];
            const categoryNames: string[] = [];

            categoriesData.forEach((category: any) => {
                categoryNames.push(category.name);

                // Handle different data structures (Keeping logic from original file)
                if (category.businesses) { /* ... same logic for direct businesses ... */ }

                // Helper to add product
                const add = (p: Product) => {
                    // Avoid duplicates if IDs are not unique across categories
                    if (!allProducts.some(existing => existing.id === p.id)) {
                        allProducts.push(p);
                    }
                };

                // Logic adapted from original file to extraction function
                // Structure 1: businessTypes > scales (current microBiz format)
                if (category.businessTypes) {
                    category.businessTypes.forEach((business: any) => {
                        if (business.scales) {
                            business.scales.forEach((scale: any) => {
                                const loanPrice = scale.basePrice || 0;
                                const cashPrice = Math.round(loanPrice * 0.85);
                                add({
                                    id: scale.id || Math.floor(Math.random() * 1000000),
                                    name: `${business.name} - ${scale.name}`,
                                    cashPrice,
                                    loanPrice,
                                    category: category.name,
                                    description: scale.description || business.description,
                                    image: category.image || business.image,
                                });
                            });
                        }
                    });
                }

                // Structure 2: subcategories > businesses > scales
                if (category.subcategories) {
                    category.subcategories.forEach((subcategory: any) => {
                        if (subcategory.businesses) {
                            subcategory.businesses.forEach((business: any) => {
                                if (business.scales) {
                                    business.scales.forEach((scale: any) => {
                                        const loanPrice = (business.basePrice || 0) * (scale.multiplier || 1);
                                        const cashPrice = Math.round(loanPrice * 0.85);
                                        if (loanPrice > 0) {
                                            add({
                                                id: scale.id || Math.floor(Math.random() * 1000000),
                                                name: `${business.name} - ${scale.name}`,
                                                cashPrice,
                                                loanPrice,
                                                category: category.name,
                                                description: business.description || subcategory.description,
                                                image: business.image || subcategory.image || category.image,
                                            });
                                        }
                                    });
                                }
                            });
                        }
                        if (subcategory.series) {
                            subcategory.series.forEach((series: any) => {
                                if (series.products) {
                                    series.products.forEach((product: any) => {
                                        if (product.scales) {
                                            product.scales.forEach((scale: any) => {
                                                const loanPrice = scale.custom_price || ((product.basePrice || 0) * (scale.multiplier || 1));
                                                const cashPrice = Math.round(loanPrice * 0.85);
                                                if (loanPrice > 0) {
                                                    add({
                                                        id: scale.id || Math.floor(Math.random() * 1000000),
                                                        name: `${product.name} - ${scale.name}`,
                                                        cashPrice,
                                                        loanPrice,
                                                        category: category.name,
                                                        description: series.description || subcategory.description,
                                                        image: product.image_url || series.image_url || category.image,
                                                    });
                                                }
                                            });
                                        } else {
                                            const loanPrice = product.basePrice || product.price || 0;
                                            const cashPrice = Math.round(loanPrice * 0.85);
                                            if (loanPrice > 0) {
                                                add({
                                                    id: product.id || Math.floor(Math.random() * 1000000),
                                                    name: product.name,
                                                    cashPrice,
                                                    loanPrice,
                                                    category: category.name,
                                                    description: series.description || subcategory.description,
                                                    image: product.image_url || series.image_url || category.image,
                                                });
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    });
                }

                // Structure 3: direct products
                if (category.products && Array.isArray(category.products)) {
                    category.products.forEach((product: any) => {
                        const loanPrice = product.price || product.basePrice || 0;
                        const cashPrice = Math.round(loanPrice * 0.85);
                        add({
                            id: product.id || Math.floor(Math.random() * 1000000),
                            name: product.name || 'Product',
                            cashPrice,
                            loanPrice,
                            category: category.name,
                            description: product.description,
                            image: product.image || category.image,
                        });
                    });
                }
            });

            // Apply filtering to match the approved list (same as ZiG loans) for ALL cash purchases
            const finalProducts = allProducts.filter(p =>
                allowedZiGKeywords.some(k =>
                    p.name.toLowerCase().includes(k.toLowerCase()) ||
                    p.category.toLowerCase().includes(k.toLowerCase())
                )
            );

            setProducts(finalProducts);
            setCategories(['all', ...categoryNames]);
        } catch (error) {
            console.error('Failed to load products:', error);
        } finally {
            setLoading(false);
        }
    };

    const filterProducts = () => {
        let filtered = products;
        if (selectedCategory !== 'all') {
            filtered = filtered.filter((p) => p.category === selectedCategory);
        }
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(
                (p) =>
                    p.name.toLowerCase().includes(query) ||
                    p.category.toLowerCase().includes(query) ||
                    (p.description && p.description.toLowerCase().includes(query))
            );
        }
        setFilteredProducts(filtered);
    };

    const formatCurrency = (amount: number) => {
        if (currency === 'ZiG') {
            const zigAmount = amount * ZIG_RATE;
            return `ZiG${zigAmount.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
        }
        return `$${amount.toLocaleString()}`;
    };

    const calculateDiscount = (loanPrice: number, cashPrice: number) => {
        const discount = ((loanPrice - cashPrice) / loanPrice) * 100;
        return Math.round(discount);
    };

    // Shopping Basket Logic
    const addToCart = (product: Product) => {
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            updateQuantity(product.id, existing.quantity + 1);
        } else {
            onUpdateCart([...cart, { ...product, quantity: 1 }]);
        }
        setIsCartOpen(true);
    };

    const updateQuantity = (productId: number, newQuantity: number) => {
        if (newQuantity < 1) {
            removeFromCart(productId);
            return;
        }
        const newCart = cart.map(item =>
            item.id === productId ? { ...item, quantity: newQuantity } : item
        );
        onUpdateCart(newCart);
    };

    const removeFromCart = (productId: number) => {
        onUpdateCart(cart.filter(item => item.id !== productId));
    };

    const cartTotal = cart.reduce((total, item) => total + (item.cashPrice * item.quantity), 0);

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <Loader2 className="h-12 w-12 animate-spin text-emerald-600" />
                <span className="ml-3 text-lg text-[#706f6c]">Loading products...</span>
            </div>
        );
    }

    return (
        <div className="space-y-6 relative">
            <div className="flex justify-between items-start">
                <div>
                    <h2 className="text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                        Shop Products
                    </h2>
                    <p className="text-[#706f6c] dark:text-[#A1A09A]">
                        Add items to your cart. Cash prices are discounted!
                    </p>
                </div>
                {/* Floating Shopping Basket Trigger (Desktop) */}
                <Button
                    onClick={() => setIsCartOpen(true)}
                    className="hidden md:flex bg-emerald-600 hover:bg-emerald-700 relative"
                >
                    <ShoppingCart className="w-5 h-5 mr-2" />
                    Shopping Basket ({cart.reduce((a, c) => a + c.quantity, 0)})
                    {cart.length > 0 && <span className="ml-2 font-mono">{formatCurrency(cartTotal)}</span>}
                </Button>
            </div>

            {/* Search and Filters */}
            <div className="space-y-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search products..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-gray-800 dark:text-white"
                    />
                </div>
                <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                    {categories.map((category) => (
                        <button
                            key={category}
                            onClick={() => setSelectedCategory(category)}
                            className={`
                                px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all
                                ${selectedCategory === category
                                    ? 'bg-emerald-600 text-white shadow-lg'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                }
                            `}
                        >
                            {category === 'all' ? 'All' : category}
                        </button>
                    ))}
                </div>
            </div>

            {/* Products Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
                {filteredProducts.map((product) => {
                    const discount = calculateDiscount(product.loanPrice, product.cashPrice);
                    const inCart = cart.find(i => i.id === product.id);

                    return (
                        <div key={product.id} className="group bg-white dark:bg-[#161615] rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all">
                            {/* Image & Badge */}
                            <div className="relative h-48 bg-gray-50 dark:bg-gray-800 p-4 flex items-center justify-center">
                                <div className="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center">
                                    <TrendingDown className="w-3 h-3 mr-1" />{discount}% OFF
                                </div>
                                {product.image ? (
                                    <img src={product.image} alt={product.name} className="max-h-full object-contain" />
                                ) : (
                                    <Tag className="w-12 h-12 text-gray-300" />
                                )}
                            </div>

                            {/* Content */}
                            <div className="p-4 space-y-3">
                                <div>
                                    <div className="text-xs text-emerald-600 font-semibold uppercase">{product.category}</div>
                                    <h3 className="font-bold text-gray-900 dark:text-white leading-tight mt-1">{product.name}</h3>
                                </div>

                                <div className="flex items-end justify-between">
                                    <div>
                                        <p className="text-xs text-gray-500 line-through">{formatCurrency(product.loanPrice)}</p>
                                        <p className="text-xl font-bold text-emerald-600">{formatCurrency(product.cashPrice)}</p>
                                    </div>

                                    {inCart ? (
                                        <div className="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                                            <button
                                                onClick={(e) => { e.stopPropagation(); updateQuantity(product.id, inCart.quantity - 1) }}
                                                className="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-colors"
                                            >
                                                <Minus className="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                            </button>
                                            <span className="w-8 text-center text-sm font-bold">{inCart.quantity}</span>
                                            <button
                                                onClick={(e) => { e.stopPropagation(); updateQuantity(product.id, inCart.quantity + 1) }}
                                                className="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-colors"
                                            >
                                                <Plus className="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                            </button>
                                        </div>
                                    ) : (
                                        <Button size="sm" onClick={() => addToCart(product)} className="bg-emerald-600 hover:bg-emerald-700">
                                            <Plus className="w-4 h-4 mr-1" /> Add to Basket
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Shopping Basket Drawer / Slide-over */}
            {isCartOpen && (
                <div className="fixed inset-0 z-50 flex justify-end">
                    <div className="absolute inset-0 bg-black/30 backdrop-blur-sm" onClick={() => setIsCartOpen(false)} />
                    <div className="relative w-full max-w-md bg-white dark:bg-[#161615] h-full shadow-2xl flex flex-col animate-in slide-in-from-right-10 duration-300">
                        {/* Header */}
                        <div className="p-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50">
                            <h3 className="font-bold text-lg flex items-center">
                                <ShoppingCart className="w-5 h-5 mr-2 text-emerald-600" />
                                Your Shopping Basket ({cart.reduce((a, c) => a + c.quantity, 0)})
                            </h3>
                            <button onClick={() => setIsCartOpen(false)} className="p-2 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-full">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Items */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-4">
                            {cart.length === 0 ? (
                                <div className="flex flex-col items-center justify-center h-full text-center text-gray-400 space-y-4">
                                    <ShoppingCart className="w-16 h-16 opacity-20" />
                                    <p>Your cart is empty</p>
                                    <Button variant="outline" onClick={() => setIsCartOpen(false)}>Start Shopping</Button>
                                </div>
                            ) : (
                                cart.map(item => (
                                    <div key={item.id} className="flex gap-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm">
                                        <div className="w-16 h-16 bg-gray-50 rounded-md flex items-center justify-center shrink-0">
                                            {item.image ? <img src={item.image} className="w-full h-full object-cover" /> : <Tag className="w-6 h-6 text-gray-300" />}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex justify-between items-start">
                                                <h4 className="font-medium text-sm line-clamp-2">{item.name}</h4>
                                                <button onClick={() => removeFromCart(item.id)} className="text-gray-400 hover:text-red-500">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div className="mt-2 flex justify-between items-end">
                                                <div className="text-sm font-bold text-emerald-600">{formatCurrency(item.cashPrice * item.quantity)}</div>
                                                <div className="flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg p-0.5">
                                                    <button onClick={() => updateQuantity(item.id, item.quantity - 1)} className="p-1 hover:bg-white rounded"><Minus className="w-3 h-3" /></button>
                                                    <span className="w-6 text-center text-xs font-mono">{item.quantity}</span>
                                                    <button onClick={() => updateQuantity(item.id, item.quantity + 1)} className="p-1 hover:bg-white rounded"><Plus className="w-3 h-3" /></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Footer */}
                        <div className="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                            <div className="flex justify-between items-center mb-4">
                                <span className="text-gray-500">Total</span>
                                <span className="text-2xl font-bold text-emerald-600">{formatCurrency(cartTotal)}</span>
                            </div>
                            <Button
                                onClick={onNext}
                                disabled={cart.length === 0}
                                className="w-full bg-emerald-600 hover:bg-emerald-700 h-12 text-lg"
                            >
                                Output Checkout <ChevronRight className="w-5 h-5 ml-1" />
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Mobile Fab */}
            {!isCartOpen && cart.length > 0 && (
                <div className="md:hidden fixed bottom-6 right-6 z-40">
                    <Button onClick={() => setIsCartOpen(true)} className="rounded-full w-14 h-14 bg-emerald-600 shadow-2xl flex items-center justify-center relative">
                        <ShoppingCart className="w-6 h-6" />
                        <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-900">
                            {cart.reduce((a, c) => a + c.quantity, 0)}
                        </span>
                    </Button>
                </div>
            )}
        </div>
    );
}