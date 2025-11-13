import { useState, useEffect } from 'react';
import { Search, ChevronRight, Check, Loader2, Tag, TrendingDown } from 'lucide-react';
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

interface CatalogueStepProps {
    purchaseType: 'personal' | 'microbiz';
    selectedProduct?: Product;
    onNext: (product: Product) => void;
    onBack: () => void;
}

export default function CatalogueStep({ purchaseType, selectedProduct, onNext, onBack }: CatalogueStepProps) {
    const [products, setProducts] = useState<Product[]>([]);
    const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<string[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(true);
    const [selected, setSelected] = useState<Product | undefined>(selectedProduct);

    useEffect(() => {
        loadProducts();
    }, [purchaseType]);

    useEffect(() => {
        filterProducts();
    }, [selectedCategory, searchQuery, products]);

    const loadProducts = async () => {
        setLoading(true);
        try {
            // Get products based on purchase type
            const intent = purchaseType === 'personal' ? 'hirePurchase' : 'microBiz';
            const categoriesData = await productService.getProductCategories(intent);

            // Define allowed categories for each purchase type
            const personalCategories = ['Electronics', 'Homeware', 'Mobile Phones', 'PCs', 'Beds', 'Furniture'];
            const microbizCategories = ['Agriculture', 'Animal Husbandry', 'Catering', 'Construction',
                                        'Entertainment', 'Events Hire', 'Farming Machinery', 'Live Chickens',
                                        'Groceries', 'Tuckshop'];

            const allowedCategories = purchaseType === 'personal' ? personalCategories : microbizCategories;

            // Flatten products from all categories and add cash pricing
            const allProducts: Product[] = [];
            const categoryNames: string[] = [];

            categoriesData.forEach((category: any) => {
                // Filter categories based on purchase type
                const categoryNameLower = category.name?.toLowerCase() || '';
                const isAllowed = allowedCategories.some(allowed =>
                    categoryNameLower.includes(allowed.toLowerCase()) ||
                    allowed.toLowerCase().includes(categoryNameLower)
                );

                if (!isAllowed) {
                    console.log(`Skipping category "${category.name}" - not in allowed list for ${purchaseType}`);
                    return; // Skip this category
                }

                console.log(`Processing category "${category.name}":`, {
                    hasBusinessTypes: !!category.businessTypes,
                    hasSubcategories: !!category.subcategories,
                    hasProducts: !!category.products,
                    keys: Object.keys(category)
                });

                categoryNames.push(category.name);

                // Handle different data structures
                // Structure 1: businessTypes > scales (current microBiz format)
                if (category.businessTypes) {
                    category.businessTypes.forEach((business: any) => {
                        if (business.scales) {
                            business.scales.forEach((scale: any) => {
                                // Calculate cash price (15% discount from loan price)
                                const loanPrice = scale.basePrice || 0;
                                const cashPrice = Math.round(loanPrice * 0.85);

                                allProducts.push({
                                    id: scale.id || Math.random(),
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
                        if (subcategory.businesses && subcategory.businesses.length > 0) {
                            subcategory.businesses.forEach((business: any) => {
                                if (business.scales && business.scales.length > 0) {
                                    business.scales.forEach((scale: any) => {
                                        // Calculate loan price (basePrice * multiplier)
                                        const loanPrice = (business.basePrice || 0) * (scale.multiplier || 1);
                                        // Calculate cash price (15% discount from loan price)
                                        const cashPrice = Math.round(loanPrice * 0.85);

                                        if (loanPrice > 0) {
                                            allProducts.push({
                                                id: scale.id || Math.random(),
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
                    });
                }

                // Structure 3: direct products array
                if (category.products && Array.isArray(category.products)) {
                    category.products.forEach((product: any) => {
                        const loanPrice = product.price || product.basePrice || 0;
                        const cashPrice = Math.round(loanPrice * 0.85);

                        allProducts.push({
                            id: product.id || Math.random(),
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

            console.log('Loaded products:', allProducts.length, 'from', categoryNames.length, 'categories');
            setProducts(allProducts);
            setCategories(['all', ...categoryNames]);
        } catch (error) {
            console.error('Failed to load products:', error);
        } finally {
            setLoading(false);
        }
    };

    const filterProducts = () => {
        let filtered = products;

        // Filter by category
        if (selectedCategory !== 'all') {
            filtered = filtered.filter((p) => p.category === selectedCategory);
        }

        // Filter by search query
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

    const handleSelectProduct = (product: Product) => {
        setSelected(product);
    };

    const handleContinue = () => {
        if (selected) {
            onNext(selected);
        }
    };

    const formatCurrency = (amount: number) => {
        return `$${amount.toLocaleString()}`;
    };

    const calculateDiscount = (loanPrice: number, cashPrice: number) => {
        const discount = ((loanPrice - cashPrice) / loanPrice) * 100;
        return Math.round(discount);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <Loader2 className="h-12 w-12 animate-spin text-emerald-600" />
                <span className="ml-3 text-lg text-[#706f6c]">Loading products...</span>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-bold mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    Select Your Product
                </h2>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    Choose from our range of {purchaseType === 'personal' ? 'personal products' : 'business starter packs'}. Cash prices are 10-15% lower than loan prices!
                </p>
            </div>

            {/* Search and Filters */}
            <div className="space-y-4">
                {/* Search Bar */}
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

                {/* Category Filter */}
                <div className="flex gap-2 overflow-x-auto pb-2">
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
                            {category === 'all' ? 'All Categories' : category}
                        </button>
                    ))}
                </div>
            </div>

            {/* Products Grid */}
            {filteredProducts.length === 0 ? (
                <div className="text-center py-12">
                    <p className="text-[#706f6c] dark:text-[#A1A09A]">
                        No products found. Try adjusting your search or filters.
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredProducts.map((product) => {
                        const discount = calculateDiscount(product.loanPrice, product.cashPrice);
                        const isSelected = selected?.id === product.id;

                        return (
                            <div
                                key={product.id}
                                onClick={() => handleSelectProduct(product)}
                                className={`
                                    relative group cursor-pointer rounded-lg border-2 transition-all overflow-hidden
                                    ${isSelected
                                        ? 'border-emerald-600 shadow-lg scale-105'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-400 hover:shadow-md'
                                    }
                                `}
                            >
                                {/* Discount Badge */}
                                <div className="absolute top-2 right-2 z-10">
                                    <div className="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                        <TrendingDown className="h-3 w-3" />
                                        {discount}% OFF
                                    </div>
                                </div>

                                {/* Selected Indicator */}
                                {isSelected && (
                                    <div className="absolute top-2 left-2 z-10">
                                        <div className="bg-emerald-600 text-white p-2 rounded-full">
                                            <Check className="h-4 w-4" />
                                        </div>
                                    </div>
                                )}

                                {/* Product Image */}
                                <div className="bg-gray-100 dark:bg-gray-800 h-40 flex items-center justify-center">
                                    {product.image ? (
                                        <img
                                            src={product.image}
                                            alt={product.name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <Tag className="h-16 w-16 text-gray-400" />
                                    )}
                                </div>

                                {/* Product Details */}
                                <div className="p-4">
                                    <div className="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-1">
                                        {product.category}
                                    </div>
                                    <h3 className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2 line-clamp-2">
                                        {product.name}
                                    </h3>
                                    {product.description && (
                                        <p className="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-3 line-clamp-2">
                                            {product.description}
                                        </p>
                                    )}

                                    {/* Pricing */}
                                    <div className="space-y-1">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-500 line-through">
                                                Loan: {formatCurrency(product.loanPrice)}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs text-gray-600 dark:text-gray-400">Cash Price:</span>
                                            <span className="text-2xl font-bold text-emerald-600">
                                                {formatCurrency(product.cashPrice)}
                                            </span>
                                        </div>
                                        <div className="text-xs text-green-600 dark:text-green-400 font-medium">
                                            Save {formatCurrency(product.loanPrice - product.cashPrice)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Actions */}
            <div className="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button onClick={onBack} variant="outline" size="lg">
                    Cancel
                </Button>
                <Button
                    onClick={handleContinue}
                    disabled={!selected}
                    size="lg"
                    className="bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="ml-2 h-5 w-5" />
                </Button>
            </div>
        </div>
    );
}