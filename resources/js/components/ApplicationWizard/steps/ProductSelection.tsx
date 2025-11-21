import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, ArrowLeft, DollarSign, Calendar, Loader2, Monitor, GraduationCap } from 'lucide-react';
import { productService, type BusinessType, type Subcategory, type Category } from '../../../services/productService';

interface ProductSelectionProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

type ViewMode = 'categories' | 'subcategories' | 'businesses' | 'scales' | 'terms';

const ProductSelection: React.FC<ProductSelectionProps> = ({ data, onNext, onBack, loading }) => {
    const [currentView, setCurrentView] = useState<ViewMode>('categories');
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [selectedSubcategory, setSelectedSubcategory] = useState<Subcategory | null>(null);
    const [selectedBusiness, setSelectedBusiness] = useState<BusinessType | null>(null);
    const [selectedScale, setSelectedScale] = useState<{ name: string; multiplier: number } | null>(null);
    const [finalAmount, setFinalAmount] = useState<number>(0);
    const [includesMESystem, setIncludesMESystem] = useState<boolean>(false);
    const [includesTraining, setIncludesTraining] = useState<boolean>(false);
    const [productCategories, setProductCategories] = useState<Category[]>([]);
    const [isLoadingProducts, setIsLoadingProducts] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedTermMonths, setSelectedTermMonths] = useState<number | null>(null);
    const [validationError, setValidationError] = useState<string>('');

    const ME_SYSTEM_FEE = 9.99;
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
        setCurrentView('businesses');
    };

    const handleBusinessSelect = (business: BusinessType) => {
        setSelectedBusiness(business);
        setCurrentView('scales');
    };

    const handleScaleSelect = (scale: { name: string; multiplier: number }) => {
        setSelectedScale(scale);
        const amount = (selectedBusiness?.basePrice || 0) * scale.multiplier;
        setFinalAmount(amount);
        setSelectedTermMonths(null); // Reset term selection
        setValidationError(''); // Clear validation error
        setCurrentView('terms');
    };

    const handleTermDropdownChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const months = e.target.value ? parseInt(e.target.value) : null;
        setSelectedTermMonths(months);
        setValidationError(''); // Clear validation error when user selects
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

        const meSystemFee = includesMESystem ? ME_SYSTEM_FEE : 0;
        const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
        const totalAmount = finalAmount + meSystemFee + trainingFee;

        // Calculate monthly payment using amortization formula
        const interestRate = 0.10;
        const monthlyInterestRate = interestRate / 12;
        const monthlyPayment = totalAmount > 0
            ? (totalAmount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
              (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
            : 0;

        onNext({
            // Legacy fields for backward compatibility
            category: selectedCategory?.name,
            subcategory: selectedSubcategory?.name,
            business: selectedBusiness?.name,
            scale: selectedScale?.name,
            amount: totalAmount,
            creditTerm: selectedTermMonths,
            monthlyPayment: parseFloat(monthlyPayment.toFixed(2)),
            // New fields with IDs for better tracking
            productId: selectedBusiness?.id,
            scaleId: (selectedScale as any)?.id,
            categoryId: selectedCategory?.id,
            loanAmount: totalAmount,
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
            case 'businesses':
                setCurrentView('subcategories');
                setSelectedSubcategory(null);
                break;
            case 'scales':
                setCurrentView('businesses');
                setSelectedBusiness(null);
                break;
            case 'terms':
                setCurrentView('scales');
                setSelectedScale(null);
                break;
            default:
                onBack();
        }
    };

    const creditTerms = selectedBusiness ? productService.getCreditTermOptions(finalAmount) : [];

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

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">
                    {currentView === 'categories' && (isPersonalProducts ? 'Select Product Category' : 'Select Business Category')}
                    {currentView === 'subcategories' && `${selectedCategory?.name} - Select Type`}
                    {currentView === 'businesses' && (isPersonalProducts ? `${selectedSubcategory?.name} - Select Product` : `${selectedSubcategory?.name} - Select Business`)}
                    {currentView === 'scales' && `${selectedBusiness?.name} - Select ${isPersonalProducts ? 'Quantity' : 'Scale'}`}
                    {currentView === 'terms' && 'Select Credit Terms'}
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                    {currentView === 'categories' && (isPersonalProducts ? 'Choose the type of product you want to purchase' : 'Choose the type of business you want to start')}
                    {currentView === 'subcategories' && 'Select a specific category'}
                    {currentView === 'businesses' && (isPersonalProducts ? 'Choose your product' : 'Choose your business type')}
                    {currentView === 'scales' && (isPersonalProducts ? 'Select quantity or package size' : 'Select the size of your operation')}
                    {currentView === 'terms' && `Loan Amount: $${finalAmount.toLocaleString()}`}
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
                            .filter(category => category.subcategories.some(sub => sub.businesses.length > 0))
                            .map((category) => {
                                const totalProducts = category.subcategories.reduce((sum, sub) => sum + sub.businesses.length, 0);
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
                            .filter(subcategory => subcategory.businesses.length > 0)
                            .map((subcategory, index) => (
                            <Card
                                key={index}
                                className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg"
                                onClick={() => handleSubcategorySelect(subcategory)}
                            >
                                <div className="text-center">
                                    <h3 className="text-lg font-medium mb-2">{subcategory.name}</h3>
                                    <p className="text-sm text-gray-500">
                                        {subcategory.businesses.length} {isPersonalProducts ? 'products' : 'business types'}
                                    </p>
                                    <ChevronRight className="mx-auto mt-4 h-5 w-5 text-gray-400" />
                                </div>
                            </Card>
                        ))}
                    </div>
                )}

                {currentView === 'businesses' && selectedSubcategory && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {selectedSubcategory.businesses.map((business, index) => (
                            <Card
                                key={index}
                                className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg"
                                onClick={() => handleBusinessSelect(business)}
                            >
                                <h3 className="text-lg font-medium mb-2">{business.name}</h3>
                                <div className="flex items-center text-sm text-gray-500 mb-2">
                                    <DollarSign className="h-4 w-4 mr-1" />
                                    Base: ${business.basePrice.toLocaleString()}
                                </div>
                                <p className="text-sm text-gray-500">
                                    {business.scales.length} scale options available
                                </p>
                                <ChevronRight className="mt-4 h-5 w-5 text-gray-400" />
                            </Card>
                        ))}
                    </div>
                )}

                {currentView === 'scales' && selectedBusiness && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {selectedBusiness.scales.map((scale, index) => {
                            const amount = selectedBusiness.basePrice * scale.multiplier;
                            return (
                                <Card
                                    key={index}
                                    className="cursor-pointer p-6 transition-all hover:border-emerald-600 hover:shadow-lg text-center"
                                    onClick={() => handleScaleSelect(scale)}
                                >
                                    <h3 className="text-lg font-medium mb-2">{scale.name}</h3>
                                    <div className="text-2xl font-bold text-emerald-600 mb-2">
                                        ${amount.toLocaleString()}
                                    </div>
                                    <p className="text-sm text-gray-500">
                                        {scale.multiplier}x base price
                                    </p>
                                </Card>
                            );
                        })}
                    </div>
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
                                                <span className="text-xl font-bold text-emerald-600">+$9.99</span>
                                                <span className="text-sm text-gray-500">added to loan amount</span>
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
                                                <span className="text-sm text-gray-500">(5.5% of loan price)</span>
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            </>
                        )}

                        {/* Total Amount Display */}
                        <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Loan Amount</p>
                            <p className="text-3xl font-bold text-emerald-600">
                                ${(finalAmount + (includesMESystem ? ME_SYSTEM_FEE : 0) + (includesTraining ? finalAmount * TRAINING_PERCENTAGE : 0)).toLocaleString()}
                            </p>
                            {(includesMESystem || includesTraining) && (
                                <p className="text-sm text-gray-500 mt-1">
                                    Includes ${finalAmount.toLocaleString()} product
                                    {includesMESystem && ` + $${ME_SYSTEM_FEE} M&E`}
                                    {includesTraining && ` + $${(finalAmount * TRAINING_PERCENTAGE).toFixed(2)} Training`}
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
                                        {/* Monthly Payment */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Monthly Payment</p>
                                            <p className="text-2xl font-bold text-emerald-600">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? ME_SYSTEM_FEE : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const totalAmount = finalAmount + meSystemFee + trainingFee;
                                                    const interestRate = 0.10;
                                                    const monthlyInterestRate = interestRate / 12;
                                                    const monthlyPayment = totalAmount > 0
                                                        ? (totalAmount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                                                          (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                                                        : 0;
                                                    return monthlyPayment.toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* Total Repayment */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Repayment</p>
                                            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? ME_SYSTEM_FEE : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const totalAmount = finalAmount + meSystemFee + trainingFee;
                                                    const interestRate = 0.10;
                                                    const monthlyInterestRate = interestRate / 12;
                                                    const monthlyPayment = totalAmount > 0
                                                        ? (totalAmount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                                                          (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                                                        : 0;
                                                    return (monthlyPayment * selectedTermMonths).toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* Total Interest */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Interest</p>
                                            <p className="text-2xl font-bold text-blue-600">
                                                ${(() => {
                                                    const meSystemFee = includesMESystem ? ME_SYSTEM_FEE : 0;
                                                    const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                    const totalAmount = finalAmount + meSystemFee + trainingFee;
                                                    const interestRate = 0.10;
                                                    const monthlyInterestRate = interestRate / 12;
                                                    const monthlyPayment = totalAmount > 0
                                                        ? (totalAmount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, selectedTermMonths)) /
                                                          (Math.pow(1 + monthlyInterestRate, selectedTermMonths) - 1)
                                                        : 0;
                                                    const totalRepayment = monthlyPayment * selectedTermMonths;
                                                    const totalInterest = totalRepayment - totalAmount;
                                                    return totalInterest.toFixed(2);
                                                })()}
                                            </p>
                                        </div>

                                        {/* First Payment */}
                                        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">First Payment</p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {(() => {
                                                    const today = new Date();
                                                    // Get the first day of next month
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
        </div>
    );
};

export default ProductSelection;