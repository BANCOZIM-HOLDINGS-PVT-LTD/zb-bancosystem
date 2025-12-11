import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ChevronLeft, ChevronRight, ArrowLeft, DollarSign, Calendar, Loader2, Monitor, GraduationCap, Info } from 'lucide-react';
import { productService, getCreditTermOptions, type BusinessType, type Subcategory, type Category, type Series } from '../../../services/productService';
import { zimparksDestinations, type ZimparksDestination } from '../data/zimparksDestinations';
import { getPackageDescription } from '../data/packageDescriptions';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Check } from 'lucide-react';

interface ProductSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

type ViewMode = 'categories' | 'subcategories' | 'series' | 'businesses' | 'product_detail' | 'zimparks_destinations' | 'scales' | 'terms';

const ProductSelection: React.FC<ProductSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const [currentView, setCurrentView] = useState<ViewMode>('categories');
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [selectedSubcategory, setSelectedSubcategory] = useState<Subcategory | null>(null);
    const [selectedSeries, setSelectedSeries] = useState<Series | null>(null);
    const [selectedBusiness, setSelectedBusiness] = useState<BusinessType | null>(null);
    const [selectedScale, setSelectedScale] = useState<{ name: string; multiplier: number; custom_price?: number } | null>(null);
    const [selectedColor, setSelectedColor] = useState<string | null>(null);
    const [finalAmount, setFinalAmount] = useState<number>(0);
    const [includesMESystem, setIncludesMESystem] = useState<boolean>(false);
    const [includesTraining, setIncludesTraining] = useState<boolean>(false);
    const [productCategories, setProductCategories] = useState<Category[]>([]);
    const [isLoadingProducts, setIsLoadingProducts] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedTermMonths, setSelectedTermMonths] = useState<number | null>(null);
    const [validationError, setValidationError] = useState<string>('');
    const [showZBBankingNotification, setShowZBBankingNotification] = useState<boolean>(false);
    const [selectedDestination, setSelectedDestination] = useState<ZimparksDestination | null>(null);
    const [showDestinationModal, setShowDestinationModal] = useState<boolean>(false);

    const ME_SYSTEM_PERCENTAGE = 0.10; // 10% of loan amount
    const TRAINING_PERCENTAGE = 0.055; // 5.5%

    // Determine if this is a personal products or MicroBiz application
    const isPersonalProducts = data.intent === 'hirePurchase';
    const isMicroBiz = data.intent === 'microBiz';

    // Load products from API on component mount
    useEffect(() => {
        const loadProducts = async () => {
            try {
                setIsLoadingProducts(true);
                setError(null);
                const categories = await productService.getProductCategories(data.intent);

                // No filtering needed - API already filters by intent
                setProductCategories(categories);
            } catch (err) {
                console.error('Failed to load products:', err);
                setError('Failed to load product catalog. Please try again.');
            } finally {
                setIsLoadingProducts(false);
            }
        };

        loadProducts();
    }, [data.intent]);

    const handleCategorySelect = (category: Category) => {
        setSelectedCategory(category);
        setCurrentView('subcategories');
    };

    const handleSubcategorySelect = (subcategory: Subcategory) => {
        setSelectedSubcategory(subcategory);

        // Special handling for Zimparks: Skip product selection and go straight to destinations
        if (subcategory.name === 'Zimparks Lodges/Cottages') {
            const zimparksProduct = subcategory.businesses.find(b => b.name === 'Zimparks Vacation Package');
            if (zimparksProduct) {
                setSelectedBusiness(zimparksProduct);
                setCurrentView('zimparks_destinations');
                return;
            }
        }

        // Check if subcategory has series
        if (subcategory.series && subcategory.series.length > 0) {
            setCurrentView('series');
        } else {
            setCurrentView('businesses');
        }
    };

    const handleSeriesSelect = (series: Series) => {
        setSelectedSeries(series);
        setCurrentView('businesses');
    };

    const handleBusinessSelect = (business: BusinessType) => {
        setSelectedBusiness(business);
        setSelectedColor(null); // Reset color
        setSelectedScale(null); // Reset scale/storage

        if (business.name === 'Zimparks Vacation Package') {
            setCurrentView('zimparks_destinations');
            return;
        }

        // Show notification for school fees products
        const schoolFeeProducts = ['Primary School Fees', 'Secondary School Fees', 'Polytech Fees', 'University Fees'];
        if (schoolFeeProducts.includes(business.name)) {
            setShowZBBankingNotification(true);
            setTimeout(() => {
                setShowZBBankingNotification(false);
            }, 8000);
        } else {
            setShowZBBankingNotification(false);
        }

        if (isPersonalProducts) {
            // For personal products, go to product detail view
            // Select default scale if available (e.g. for phones with only one storage option)
            if (business.scales.length === 1) {
                handleScaleSelect(business.scales[0]);
            }
            setCurrentView('product_detail');
        } else {
            setCurrentView('scales');
        }
    };

    const handleScaleSelect = (scale: { name: string; multiplier: number; custom_price?: number }) => {
        setSelectedScale(scale);

        let amount = 0;
        if (scale.custom_price) {
            amount = scale.custom_price;
        } else {
            amount = (selectedBusiness?.basePrice || 0) * scale.multiplier;
        }

        setFinalAmount(amount);
        setSelectedTermMonths(null); // Reset term selection
        setValidationError(''); // Clear validation error

        // For Zimparks and MicroBiz, stay on scales view to show description
        const isZimparks = selectedBusiness?.name === 'Zimparks Vacation Package';

        if (isPersonalProducts) {
            // Stay on product detail view
        } else if (isMicroBiz || isZimparks) {
            // Stay on current view
        } else {
            setCurrentView('terms');
        }
    };

    const handleProceedToTerms = () => {
        setCurrentView('terms');
    };

    const handleTermDropdownChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const months = e.target.value ? parseInt(e.target.value) : null;
        setSelectedTermMonths(months);
        setValidationError(''); // Clear validation error when user selects
    };

    const handleDestinationSelect = (destination: ZimparksDestination) => {
        setSelectedDestination(destination);
        setShowDestinationModal(true);
    };

    const handleDestinationConfirm = () => {
        setShowDestinationModal(false);
        setCurrentView('scales');
    };

    const handleContinue = () => {
        // Validate term selection
        if (!selectedTermMonths) {
            setValidationError('Please select a loan duration before continuing');
            return;
        }

        if (selectedTermMonths < 3 || selectedTermMonths > 18) {
            setValidationError('Loan duration must be between 3 and 18 months');
            return;
        }

        // Find the selected term details
        const selectedTerm = creditTerms.find(term => term.months === selectedTermMonths);
        if (!selectedTerm) {
            setValidationError('Invalid loan duration selected');
            return;
        }

        const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
        const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;

        // Net Loan = selling price + optional fees (what user sees)
        const netLoan = finalAmount + meSystemFee + trainingFee;

        // Gross Loan = Net Loan + 6% bank admin fee (used for backend calculation)
        const ADMIN_FEE_PERCENTAGE = 0.06;
        const bankAdminFee = netLoan * ADMIN_FEE_PERCENTAGE;
        const grossLoan = netLoan + bankAdminFee;

        // Calculate monthly payment using amortization formula (based on Gross Loan)
        const interestRate = 0.96; // 96% annual interest rate
        const monthlyInterestRate = interestRate / 12;
        const monthlyPayment = grossLoan > 0
            ? (grossLoan * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
            (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
            : 0;

        // Calculate first and last payment dates
        const today = new Date();
        const firstPaymentDate = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        const lastPaymentDate = new Date(today.getFullYear(), today.getMonth() + selectedTermMonths, 1);

        onNext({
            // Legacy fields for backward compatibility
            category: selectedCategory?.name,
            subcategory: selectedSubcategory?.name,
            business: selectedBusiness?.name,
            scale: selectedScale?.name,
            amount: grossLoan, // Total for backend calculation
            creditTerm: selectedTermMonths,
            monthlyPayment: parseFloat(monthlyPayment.toFixed(2)),
            // New fields with IDs for better tracking
            productId: selectedBusiness?.id,
            productName: selectedBusiness?.name === 'Zimparks Vacation Package' && selectedDestination
                ? `Zimparks Package - ${selectedDestination.name}`
                : selectedBusiness?.name,
            destinationId: selectedDestination?.id,
            destinationName: selectedDestination?.name,
            scaleId: (selectedScale as any)?.id,
            categoryId: selectedCategory?.id,
            // New loan amount fields
            loanAmount: grossLoan,
            netLoan: netLoan,
            grossLoan: grossLoan,
            bankAdminFee: parseFloat(bankAdminFee.toFixed(2)),
            sellingPrice: finalAmount,
            // Payment dates
            firstPaymentDate: firstPaymentDate.toISOString(),
            lastPaymentDate: lastPaymentDate.toISOString(),
            // ME System and Training
            includesMESystem: includesMESystem,
            meSystemFee: meSystemFee,
            includesTraining: includesTraining,
            trainingFee: trainingFee,
        });
    };

    const goBack = () => {
        switch (currentView) {
            case 'subcategories':
                setCurrentView('categories');
                setSelectedCategory(null);
                break;
            case 'series':
                setCurrentView('subcategories');
                setSelectedSubcategory(null);
                break;
            case 'businesses':
                if (selectedSeries) {
                    setCurrentView('series');
                    setSelectedSeries(null);
                } else {
                    setCurrentView('subcategories');
                    setSelectedSubcategory(null);
                }
                break;
            case 'product_detail':
                setCurrentView('businesses');
                setSelectedBusiness(null);
                setSelectedScale(null);
                setSelectedColor(null);
                break;
            case 'scales':
                if (selectedBusiness?.name === 'Zimparks Vacation Package') {
                    setCurrentView('zimparks_destinations');
                    setSelectedScale(null);
                } else {
                    setCurrentView('businesses');
                    setSelectedBusiness(null);
                }
                break;
            case 'zimparks_destinations':
                setCurrentView('subcategories');
                setSelectedSubcategory(null);
                setSelectedBusiness(null);
                setSelectedDestination(null);
                break;
            case 'terms':
                if (isPersonalProducts) {
                    setCurrentView('product_detail');
                } else {
                    setCurrentView('scales');
                    setSelectedScale(null);
                }
                break;
            default:
                onBack();
        }
    };

    const creditTerms = selectedBusiness ? getCreditTermOptions(finalAmount) : [];

    // Show loading state
    if (isLoadingProducts) {
        return (
            <div className="space-y-6">
                <div className="text-center">
                    <h2 className="text-2xl font-semibold mb-2">Loading Product Catalog</h2>
                    <p className="text-gray-600 dark:text-gray-400">Please wait while we load available products...</p>
                </div>
                <div className="flex justify-center items-center py-12">
                    <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                </div>
            </div>
        );
    }

    // Show error state
    if (error) {
        return (
            <div className="space-y-6">
                <div className="text-center">
                    <h2 className="text-2xl font-semibold mb-2 text-red-600">Error Loading Products</h2>
                    <p className="text-gray-600 dark:text-gray-400">{error}</p>
                </div>
                <div className="flex justify-center space-x-4">
                    <Button onClick={() => window.location.reload()} variant="outline">
                        Retry
                    </Button>
                    <Button onClick={onBack} variant="outline">
                        Go Back
                    </Button>
                </div>
            </div>
        );
    }

    const getColorHex = (colorName: string): string => {
        const map: Record<string, string> = {
            'phantom black': '#000000',
            'phantom silver': '#C0C0C0',
            'awesome black': '#111111',
            'awesome white': '#F0F0F0',
            'awesome blue': '#0070BB',
            'black titanium': '#1E1E1E',
            'white titanium': '#E3E3E3',
            'blue titanium': '#2F3C53',
            'midnight black': '#000000',
            'polar white': '#F8F9FA',
            'obsidian': '#1C1C1C',
            'porcelain': '#FDFDFD',
            'bay': '#87CEEB',
            'hazel': '#8E7618',
            'white gloss': '#FFFFFF',
            'oak': '#DEB887',
            'metallic': '#D4AF37',
            'elephant grey': '#4A4A4A',
            'savanna beige': '#F5F5DC',
            'buffalo brown': '#8B4513',
            'deep blue': '#00008B',
            'sunset orange': '#FD5E53',
            'sand': '#C2B280',
            'genuine leather brown': '#5D4037',
            'velvet green': '#006400',
            'teak': '#8B5A2B',
            'white wash': '#F0EAD6',
            'grey velvet': '#808080',
            'cream linen': '#FFFDD0',
            'black leather': '#000000',
            'natural black': '#1A1A1A',
            'dark brown': '#654321',
            'burgundy': '#800020',
            'neutral': '#D3D3D3',
            'navy': '#000080',
            'cream': '#FFFDD0',
            'gold': '#FFD700',
            'silver': '#C0C0C0',
            'grey': '#808080',
            'black': '#000000',
            'white': '#FFFFFF',
            'blue': '#0000FF',
            'red': '#FF0000',
            'green': '#008000',
            'pink': '#FFC0CB',
            'purple': '#800080',
            'brown': '#A52A2A',
            'beige': '#F5F5DC'
        };
        return map[colorName.toLowerCase()] || colorName.toLowerCase();
    };

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">
                    {currentView === 'categories' && (isPersonalProducts ? 'Select Product Category' : 'Select Business Category')}
                    {currentView === 'subcategories' && `${selectedCategory?.name} - Select Type`}
                    {currentView === 'businesses' && (isPersonalProducts ? `${selectedSubcategory?.name} - Select Product` : `${selectedSubcategory?.name} - Select Business`)}
                    {currentView === 'zimparks_destinations' && 'Select Your Destination Resort'}
                    {currentView === 'scales' && `${selectedBusiness?.name} - Select ${isPersonalProducts ? 'Quantity' : 'Scale'}`}
                    {currentView === 'terms' && 'Select Credit Terms'}
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                    {currentView === 'categories' && (isPersonalProducts ? 'Choose the type of product you want to purchase' : 'Choose the type of business you want to start')}
                    {currentView === 'subcategories' && 'Select a specific category'}
                    {currentView === 'businesses' && (isPersonalProducts ? 'Choose your product' : 'Choose your business type')}
                    {currentView === 'zimparks_destinations' && 'Choose from our exclusive list of 30 premier destinations'}
                    {currentView === 'scales' && (isPersonalProducts ? 'Select quantity or package size' : 'Select the size of your operation')}
                    {currentView === 'terms' && `Net Loan (selling price): $${finalAmount.toLocaleString()}`}
                </p>
                {currentView === 'categories' && (
                    <p className="text-sm text-blue-600 dark:text-blue-400 mt-2">
                        Showing {productCategories.length} categories with live data from our catalog
                    </p>
                )}
            </div>

            <div className="min-h-[400px]">
                {currentView === 'categories' && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {productCategories
                            .filter(category => category.subcategories.some(sub => sub.businesses.length > 0 || (sub.series && sub.series.length > 0)))
                            .map((category) => {
                                const totalProducts = category.subcategories.reduce((sum, sub) => {
                                    const businessCount = sub.businesses.length;
                                    const seriesProductCount = sub.series ? sub.series.reduce((sSum, series) => sSum + series.products.length, 0) : 0;
                                    return sum + businessCount + seriesProductCount;
                                }, 0);
                                return (
                                    <Card
                                        key={category.id}
                                        className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg"
                                        onClick={() => handleCategorySelect(category)}
                                    >
                                        <div className="text-center">
                                            <div className="text-4xl mb-3">{category.emoji}</div>
                                            <h3 className="text-lg font-medium mb-2">{category.name}</h3>
                                            <p className="text-sm text-gray-500">
                                                {totalProducts} products available
                                            </p>
                                            <ChevronRight className="mx-auto mt-4 h-5 w-5 text-gray-400" />
                                        </div>
                                    </Card>
                                );
                            })}
                    </div>
                )}

                {currentView === 'subcategories' && selectedCategory && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {selectedCategory.subcategories
                            .filter(subcategory => subcategory.businesses.length > 0 || (subcategory.series && subcategory.series.length > 0))
                            .map((subcategory, index) => (
                                <Card
                                    key={index}
                                    className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg"
                                    onClick={() => handleSubcategorySelect(subcategory)}
                                >
                                    <div className="text-center">
                                        <h3 className="text-lg font-medium mb-2">{subcategory.name}</h3>
                                        <p className="text-sm text-gray-500">
                                            {subcategory.series && subcategory.series.length > 0
                                                ? `${subcategory.series.length} series available`
                                                : `${subcategory.businesses.length} ${isPersonalProducts ? 'products' : 'business types'}`
                                            }
                                        </p>
                                        <ChevronRight className="mx-auto mt-4 h-5 w-5 text-gray-400" />
                                    </div>
                                </Card>
                            ))}
                    </div>
                )}

                {currentView === 'series' && selectedSubcategory && selectedSubcategory.series && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {selectedSubcategory.series.map((series) => (
                            <Card
                                key={series.id}
                                className="cursor-pointer p-0 overflow-hidden transition-all hover:border-emerald-600 hover:shadow-lg group"
                                onClick={() => handleSeriesSelect(series)}
                            >
                                <div className="aspect-video w-full bg-gray-100 relative overflow-hidden">
                                    {series.image_url ? (
                                        <img
                                            src={`/storage/${series.image_url}`}
                                            alt={series.name}
                                            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="absolute inset-0 flex items-center justify-center bg-gray-100 text-gray-400">
                                            <span className="text-4xl">📦</span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-4 text-center">
                                    <h3 className="text-lg font-medium mb-1">{series.name}</h3>
                                    <p className="text-sm text-gray-500">
                                        {series.products.length} models available
                                    </p>
                                    <ChevronRight className="mx-auto mt-3 h-5 w-5 text-gray-400" />
                                </div>
                            </Card>
                        ))}
                    </div>
                )}

                {currentView === 'businesses' && selectedSubcategory && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(selectedSeries ? selectedSeries.products : selectedSubcategory.businesses).map((business, index) => (
                            <Card
                                key={index}
                                className="cursor-pointer p-4 transition-all hover:border-emerald-600 hover:shadow-lg flex flex-row items-center gap-4"
                                onClick={() => handleBusinessSelect(business)}
                            >
                                {business.image_url && (
                                    <div className="w-24 h-24 flex-shrink-0 bg-gray-100 rounded-md overflow-hidden">
                                        <img
                                            src={`/storage/${business.image_url}`}
                                            alt={business.name}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                )}
                                <div className="flex-grow">
                                    <h3 className="text-lg font-medium mb-1">{business.name}</h3>
                                    <div className="flex items-center text-sm text-gray-500 mb-1">
                                        <DollarSign className="h-4 w-4 mr-1" />
                                        From ${business.basePrice.toLocaleString()}
                                    </div>
                                    {business.colors && Array.isArray(business.colors) && business.colors.length > 0 && (
                                        <div className="flex gap-1 mt-2">
                                            {business.colors.slice(0, 3).map((color, i) => (
                                                <div
                                                    key={i}
                                                    className="w-4 h-4 rounded-full border border-gray-200"
                                                    style={{ backgroundColor: getColorHex(color) }}
                                                    title={color}
                                                />
                                            ))}
                                            {business.colors.length > 3 && (
                                                <span className="text-xs text-gray-400">+{business.colors.length - 3}</span>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <ChevronRight className="h-5 w-5 text-gray-400" />
                            </Card>
                        ))}
                    </div>
                )}

                {currentView === 'zimparks_destinations' && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {zimparksDestinations.map((destination) => (
                            <Card
                                key={destination.id}
                                className="cursor-pointer p-4 transition-all hover:border-emerald-600 hover:shadow-lg flex flex-col h-full"
                                onClick={() => handleDestinationSelect(destination)}
                            >
                                <div className="aspect-video w-full bg-gray-200 rounded-md mb-3 overflow-hidden relative">
                                    <div className="absolute inset-0 flex items-center justify-center bg-emerald-100 text-emerald-800 font-bold text-xl">
                                        {destination.name.charAt(0)}
                                    </div>
                                </div>
                                <h3 className="text-lg font-medium mb-1">{destination.name}</h3>
                                <p className="text-sm text-gray-500 line-clamp-2 mb-3 flex-grow">
                                    {destination.description}
                                </p>
                                <Button variant="outline" className="w-full mt-auto text-emerald-600 border-emerald-200 hover:bg-emerald-50">
                                    View Details
                                </Button>
                            </Card>
                        ))}
                    </div>
                )}

                {currentView === 'product_detail' && selectedBusiness && (
                    <div className="grid md:grid-cols-2 gap-8">
                        {/* Product Image */}
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-center min-h-[300px]">
                            {selectedBusiness.image_url ? (
                                <img
                                    src={`/storage/${selectedBusiness.image_url}`}
                                    alt={selectedBusiness.name}
                                    className="max-w-full max-h-[400px] object-contain"
                                />
                            ) : (
                                <div className="text-center text-gray-400">
                                    <div className="text-6xl mb-4">📱</div>
                                    <p>No image available</p>
                                </div>
                            )}
                        </div>

                        {/* Product Details */}
                        <div className="space-y-6">
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900 mb-2">{selectedBusiness.name}</h2>
                                <div className="text-2xl font-bold text-emerald-600">
                                    ${finalAmount > 0 ? finalAmount.toLocaleString() : selectedBusiness.basePrice.toLocaleString()}
                                </div>
                            </div>

                            {/* Storage / Variant Selection */}
                            {selectedBusiness.scales.length > 0 && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {selectedBusiness.name.toLowerCase().includes('phone') || selectedBusiness.name.toLowerCase().includes('iphone') || selectedBusiness.name.toLowerCase().includes('samsung') ? 'Storage' : 'Options'}
                                    </label>
                                    <div className="grid grid-cols-3 gap-3">
                                        {selectedBusiness.scales.map((scale) => (
                                            <button
                                                key={scale.name}
                                                onClick={() => handleScaleSelect(scale)}
                                                className={`
                                                    px-4 py-3 rounded-lg border text-sm font-medium transition-all
                                                    ${selectedScale?.name === scale.name
                                                        ? 'border-emerald-600 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600'
                                                        : 'border-gray-200 text-gray-700 hover:border-emerald-300'
                                                    }
                                                `}
                                            >
                                                {scale.name}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Color Selection */}
                            {selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Color: <span className="text-gray-500 font-normal">{selectedColor || 'Select a color'}</span>
                                    </label>
                                    <div className="flex flex-wrap gap-3">
                                        {selectedBusiness.colors.map((color) => (
                                            <button
                                                key={color}
                                                onClick={() => setSelectedColor(color)}
                                                className={`
                                                    w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
                                                    ${selectedColor === color
                                                        ? 'border-emerald-600 ring-2 ring-emerald-100 scale-110'
                                                        : 'border-transparent hover:scale-105'
                                                    }
                                                `}
                                                style={{ backgroundColor: getColorHex(color), boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}
                                                title={color}
                                            >
                                                {selectedColor === color && (
                                                    <Check className={`h-5 w-5 ${['white', 'yellow', 'cream', 'polar white', 'white titanium', 'porcelain', 'white gloss', 'savanna beige', 'white wash', 'cream linen', 'beige'].includes(color.toLowerCase()) ? 'text-black' : 'text-white'}`} />
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Action Button */}
                            <div className="pt-6">
                                <Button
                                    onClick={handleProceedToTerms}
                                    disabled={!selectedScale || (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor)}
                                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-6 text-lg"
                                >
                                    Continue to Payment Terms
                                    <ChevronRight className="ml-2 h-5 w-5" />
                                </Button>
                                <p className="text-xs text-gray-500 text-center mt-3">
                                    {!selectedScale
                                        ? "Please select an option to continue"
                                        : (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor)
                                            ? "Please select a color to continue"
                                            : "Next: Choose your repayment plan"
                                    }
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {currentView === 'scales' && selectedBusiness && (
                    <>
                        {showZBBankingNotification && (
                            <Alert className="mb-4 border-blue-500 bg-blue-50 dark:bg-blue-950">
                                <Info className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                <AlertDescription className="text-blue-800 dark:text-blue-200">
                                    Please make sure that this school or institution banks with ZB before proceeding.
                                </AlertDescription>
                            </Alert>
                        )}
                        {selectedBusiness.name === 'Starlink Internet Kit' && (
                            <Alert className="mb-4 border-amber-500 bg-amber-50 dark:bg-amber-950">
                                <Info className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                <AlertDescription className="text-amber-800 dark:text-amber-200">
                                    <strong>Note:</strong> Available for areas outside Harare and Chitungwiza
                                </AlertDescription>
                            </Alert>
                        )}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {selectedBusiness.scales.map((scale, index) => {
                                // Use custom_price if available, otherwise calculate from multiplier
                                const amount = scale.custom_price || (selectedBusiness.basePrice * scale.multiplier);
                                const isSelected = selectedScale?.name === scale.name;
                                // Format scale name: add "Package" suffix for Lite, Standard, Full house
                                const formatScaleName = (name: string) => {
                                    const packageScales = ['Lite', 'Standard', 'Full house'];
                                    if (packageScales.includes(name)) {
                                        return `${name === 'Full house' ? 'Full House' : name} Package`;
                                    }
                                    return name;
                                };
                                return (
                                    <Card
                                        key={index}
                                        className={`cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg text-center ${isSelected ? 'border-2 border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : ''}`}
                                        onClick={() => handleScaleSelect(scale)}
                                    >
                                        <h3 className="text-lg font-medium mb-2">{formatScaleName(scale.name)}</h3>
                                        <div className="text-xl font-bold text-emerald-600 mb-2">
                                            Cost: ${amount.toLocaleString()}
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>

                        {/* Package Description Slide-in for MicroBiz and Zimparks */}
                        {selectedScale && (isMicroBiz || selectedBusiness.name === 'Zimparks Vacation Package') && (
                            <div className="mt-8 animate-in slide-in-from-bottom-4 fade-in duration-500">
                                <Card className="p-6 border-emerald-200 bg-emerald-50/50 dark:bg-emerald-900/10">
                                    <h3 className="text-lg font-semibold text-emerald-800 dark:text-emerald-400 mb-2">
                                        Package Details
                                    </h3>
                                    <p className="text-gray-700 dark:text-gray-300 mb-6 text-lg">
                                        {getPackageDescription(selectedBusiness.name, selectedScale.name)}
                                    </p>
                                    <div className="flex justify-end">
                                        <Button
                                            onClick={handleProceedToTerms}
                                            className="bg-emerald-600 hover:bg-emerald-700 text-white px-8"
                                        >
                                            Continue
                                            <ChevronRight className="ml-2 h-4 w-4" />
                                        </Button>
                                    </div>
                                </Card>
                            </div>
                        )}
                    </>
                )}

                {currentView === 'terms' && (
                    <div className="space-y-6">
                        {/* ME System and Training Options for MicroBiz */}
                        {isMicroBiz && (
                            <>
                                <Card className="p-6 bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
                                    <div className="flex items-start gap-4">
                                        <input
                                            type="checkbox"
                                            id="me-system"
                                            checked={includesMESystem}
                                            onChange={(e) => setIncludesMESystem(e.target.checked)}
                                            className="mt-1 h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                        />
                                        <div className="flex-1">
                                            <label htmlFor="me-system" className="font-semibold text-lg cursor-pointer flex items-center gap-2">
                                                <Monitor className="h-5 w-5" />
                                                Add Monitoring & Evaluation System
                                            </label>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Track your business performance, monitor inventory, manage finances, and get business insights with our advanced M&E system.
                                            </p>
                                            <div className="mt-2 flex items-center gap-2">
                                                <span className="text-xl font-bold text-emerald-600">+${(finalAmount * ME_SYSTEM_PERCENTAGE).toFixed(2)}</span>
                                                <span className="text-sm text-gray-500">(10% of selling price)</span>
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                <Card className="p-6 bg-purple-50 dark:bg-purple-950/20 border-purple-200 dark:border-purple-800">
                                    <div className="flex items-start gap-4">
                                        <input
                                            type="checkbox"
                                            id="training"
                                            checked={includesTraining}
                                            onChange={(e) => setIncludesTraining(e.target.checked)}
                                            className="mt-1 h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <div className="flex-1">
                                            <label htmlFor="training" className="font-semibold text-lg cursor-pointer flex items-center gap-2">
                                                <GraduationCap className="h-5 w-5" />
                                                Add Technical and Business Management Training
                                            </label>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Get comprehensive training on technical skills and business management to help you succeed in your business venture.
                                            </p>
                                            <div className="mt-2 flex items-center gap-2">
                                                <span className="text-xl font-bold text-purple-600">+${(finalAmount * TRAINING_PERCENTAGE).toFixed(2)}</span>
                                                <span className="text-sm text-gray-500">(5.5% of selling price)</span>
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            </>
                        )}

                        {/* Total Amount Display */}
                        <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">New Selling Price</p>
                            <p className="text-3xl font-bold text-emerald-600">
                                ${((finalAmount + (includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0) + (includesTraining ? finalAmount * TRAINING_PERCENTAGE : 0)) * 1.06).toLocaleString()}
                            </p>
                            {(includesMESystem || includesTraining) && (
                                <p className="text-sm text-gray-500 mt-1">
                                    Base Price: ${(finalAmount + (includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0) + (includesTraining ? finalAmount * TRAINING_PERCENTAGE : 0)).toLocaleString()}
                                    {includesMESystem && ` (incl. M&E)`}
                                    {includesTraining && ` (incl. Training)`}
                                </p>
                            )}
                        </div>

                        {/* Loan Duration Dropdown */}
                        <div className="max-w-md mx-auto">
                            <label htmlFor="loan-duration" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Loan Duration
                            </label>
                            <select
                                id="loan-duration"
                                value={selectedTermMonths || ''}
                                onChange={handleTermDropdownChange}
                                className="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 transition-all"
                            >
                                <option value="">Select duration</option>
                                {creditTerms.map((term) => (
                                    <option key={term.months} value={term.months}>
                                        {term.months} months
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Animated Loan Details Container */}
                        {selectedTermMonths && (
                            <div className="max-w-2xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <Card className="p-6 border-emerald-200 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                        <Calendar className="h-5 w-5 text-emerald-600" />
                                        Loan Details
                                    </h3>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {/* Net Loan (Selling Price) */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Net Loan (selling price)</p>
                                            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const netLoan = finalAmount + meSystemFee + trainingFee;
                                                    return netLoan.toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* Gross Loan */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">New Selling Price</p>
                                            <p className="text-2xl font-bold text-emerald-600">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const netLoan = finalAmount + meSystemFee + trainingFee;
                                                    const grossLoan = netLoan * 1.06;
                                                    return grossLoan.toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* Monthly Payment */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Monthly Payment</p>
                                            <p className="text-2xl font-bold text-blue-600">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const netLoan = finalAmount + meSystemFee + trainingFee;
                                                    const grossLoan = netLoan * 1.06;
                                                    const interestRate = 0.96; // 96% annual
                                                    const monthlyInterestRate = interestRate / 12;
                                                    const monthlyPayment = grossLoan > 0
                                                        ? (grossLoan * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                                                        (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                                                        : 0;
                                                    return monthlyPayment.toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* First Payment */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">First Payment</p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {(() => {
                                                    const today = new Date();
                                                    const firstPaymentDate = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                                                    return firstPaymentDate.toLocaleDateString('en-US', {
                                                        month: 'short',
                                                        year: 'numeric'
                                                    });
                                                })()}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Additional Info */}
                                    <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <p className="text-sm text-blue-800 dark:text-blue-300">
                                            Loan Duration: {selectedTermMonths} months | Last Payment: {(() => {
                                                const today = new Date();
                                                const lastPaymentDate = new Date(today.getFullYear(), today.getMonth() + selectedTermMonths, 1);
                                                return lastPaymentDate.toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    year: 'numeric'
                                                });
                                            })()}
                                        </p>
                                    </div>
                                </Card>
                            </div>
                        )}

                        {/* Validation Error */}
                        {validationError && (
                            <div className="max-w-md mx-auto p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg animate-in fade-in slide-in-from-top-2 duration-300">
                                <p className="text-sm text-red-800 dark:text-red-300 font-medium">{validationError}</p>
                            </div>
                        )}
                    </div>
                )}
            </div>

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    onClick={goBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back
                </Button>

                {currentView === 'terms' && (
                    <Button
                        onClick={handleContinue}
                        disabled={loading || !selectedTermMonths}
                        className="flex items-center gap-2"
                    >
                        Continue
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                )}
            </div>

            <Dialog open={showDestinationModal} onOpenChange={setShowDestinationModal}>
                <DialogContent className="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle className="text-2xl font-bold text-emerald-700">
                            {selectedDestination?.name}
                        </DialogTitle>
                        <DialogDescription>
                            Destination Resort Details
                        </DialogDescription>
                    </DialogHeader>

                    {selectedDestination && (
                        <div className="space-y-6 py-4">
                            <div className="aspect-video w-full bg-gray-100 rounded-lg overflow-hidden relative">
                                <div className="absolute inset-0 flex items-center justify-center bg-emerald-50 text-emerald-800 font-bold text-4xl">
                                    {selectedDestination.name.charAt(0)}
                                </div>
                                {/* <img src={selectedDestination.imageUrl} alt={selectedDestination.name} className="w-full h-full object-cover" /> */}
                            </div>

                            <div>
                                <h4 className="font-semibold text-gray-900 mb-2">Description</h4>
                                <p className="text-gray-600 leading-relaxed">
                                    {selectedDestination.description}
                                </p>
                            </div>

                            <div className="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
                                <p className="text-sm text-emerald-800 italic">
                                    * Package inclusions (nights, activities, meals) vary based on the package size selected in the next step.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDestinationModal(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleDestinationConfirm} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                            Select & Proceed
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default ProductSelection;