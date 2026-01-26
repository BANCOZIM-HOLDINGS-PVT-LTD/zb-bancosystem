import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ChevronLeft, ChevronRight, ArrowLeft, DollarSign, Calendar, Loader2, Monitor, GraduationCap, Info, ShoppingBasket, X } from 'lucide-react';
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
    const [selectedScale, setSelectedScale] = useState<{ id?: number; name: string; multiplier: number; custom_price?: number } | null>(null);
    const [selectedColor, setSelectedColor] = useState<string | null>(null);
    const [selectedInteriorColor, setSelectedInteriorColor] = useState<string | null>(null);
    const [selectedExteriorColor, setSelectedExteriorColor] = useState<string | null>(null);
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

    // Cart State
    const [cart, setCart] = useState<{ businessId: number; name: string; price: number; quantity: number; color?: string; interiorColor?: string; exteriorColor?: string; scale?: string }[]>(data.cart || []);
    const [cartQuantity, setCartQuantity] = useState<number>(1);
    const isCartMode = selectedCategory?.name === 'Building Materials' || (selectedCategory?.name === 'Agricultural Inputs' && data.intent !== 'microBiz') || data.intent === 'homeConstruction';

    const ME_SYSTEM_PERCENTAGE = 0.10; // 10% of loan amount
    const TRAINING_PERCENTAGE = 0.055; // 5.5%

    // Currency Logic
    const selectedCurrency = data.currency || 'USD';
    const isZiG = selectedCurrency === 'ZiG';
    const ZIG_RATE = 35;

    const formatCurrency = (amount: number) => {
        const symbol = isZiG ? 'ZiG' : '$';
        // If ZiG, we assume the amount is already converted (or we convert for display if needed)
        // But for consistency, let's assume all 'amount' variables holding money are in the selected currency.
        return `${symbol}${amount.toLocaleString()}`;
    };

    // Determine if this is a personal products or MicroBiz application
    const isPersonalProducts = data.intent === 'hirePurchase' || data.intent === 'personalGadgets';
    const isMicroBiz = data.intent === 'microBiz';



    const filterProducts = (categories: Category[]) => {
        let intentKeywords: string[] = [];

        switch (data.intent) {
            case 'microBiz':
                intentKeywords = ['Agriculture', 'Agricultural', 'Fertilizer', 'Seed', 'Chemicals', 'Broiler', 'Grocery', 'Tuckshop', 'Tuck shop', 'Groceries', 'Business', 'Maize', 'Irrigation', 'Water', 'Pumping', 'Planter', 'Sheller', 'Banking', 'Agency', 'POS', 'Purification', 'Refill', 'Cleaning', 'Beauty', 'Hair', 'Cosmetics', 'Food', 'Butchery', 'Events', 'Snack', 'Entertainment', 'Printing', 'Digital', 'Multimedia', 'Tailoring', 'Construction', 'Mining', 'Retailing', 'Delivery', 'Vehicle', 'Photocopying', 'Small', 'Support', 'Fee', 'Licens', 'Company', 'Reg'];
                break;
            case 'homeConstruction':
                intentKeywords = ['Building', 'Cement', 'Roofing', 'Plumbing', 'Hardware', 'Paint', 'Timber', 'Electrical', 'Tank', 'Brick', 'Door', 'Window', 'Construction', 'Solar', 'Tile', 'Glass', 'Steel'];
                break;
            case 'personalServices':
                intentKeywords = ['Nurse', 'License', 'Holiday', 'School', 'Fees', 'Vacation', 'Travel', 'Tourism', 'Clinic', 'Zimparks', 'Driving'];
                break;
            case 'personalGadgets':
            case 'hirePurchase':
            default:
                intentKeywords = ['Phone', 'Laptop', 'TV', 'Fridge', 'Stove', 'Bed', 'Sofa', 'Furniture', 'Solar', 'Appliance', 'Techno', 'Redmi', 'Samsung', 'Gadget', 'Computer', 'Radio', 'Audio', 'Freezer', 'Microwave', 'Kettle', 'Iron', 'Agriculture', 'Fertilizer', 'Seed', 'Small', 'Business', 'Support', 'Fee', 'Licens', 'Company', 'Reg'];
                break;
        }

        // ZiG allowed list (subset of everything that is allowed on ZiG)
        const zigAllowedKeywords = [
            'Techno', 'Redmi', 'Samsung',
            'Building', 'Cement', 'Roofing', 'Plumbing', 'Hardware', 'Paint', 'Timber', 'Tank',
            'holiday', 'vacation', 'Zimparks',
            'school', 'fees',
            'Agriculture', 'Broiler', 'Grocery', 'Tuckshop',
            'water storage', 'pumping', 'maize', 'irrigation'
        ];

        return categories.map(cat => {
            const isIntentMatch = (text: string) => intentKeywords.some(k => text.toLowerCase().includes(k.toLowerCase()));
            const isZiGMatch = (text: string) => !isZiG || zigAllowedKeywords.some(k => text.toLowerCase().includes(k.toLowerCase()));

            // Helper: Item is valid if it matches Intent AND (if ZiG, matches ZiG rules)
            const isValid = (text: string) => isIntentMatch(text) && isZiGMatch(text);

            // Optimization: If category itself is a strong match (e.g. "Building Materials" for Construction), 
            // we might want to be permissive with children, but filtering deep is safer to avoid mix-ins.

            const filteredSubcategories = cat.subcategories.map(sub => {
                const filteredBusinesses = sub.businesses.filter(b =>
                    isValid(b.name) || isValid(sub.name) || isValid(cat.name)
                );

                const filteredSeries = sub.series?.filter(s =>
                    isValid(s.name) || isValid(sub.name) || isValid(cat.name) || s.products.some(p => isValid(p.name))
                );

                return {
                    ...sub,
                    businesses: filteredBusinesses,
                    series: filteredSeries
                };
            }).filter(sub => sub.businesses.length > 0 || (sub.series && sub.series.length > 0));

            return {
                ...cat,
                subcategories: filteredSubcategories
            };
        }).filter(cat => cat.subcategories.length > 0);
    };

    // Load products from API on component mount
    useEffect(() => {
        const loadProducts = async () => {
            try {
                setIsLoadingProducts(true);
                setError(null);
                const categories = await productService.getProductCategories(data.intent);

                // Apply intent and currency filtering
                const filteredCategories = filterProducts(categories);
                setProductCategories(filteredCategories);
            } catch (err) {
                console.error('Failed to load products:', err);
                setError('Failed to load product catalog. Please try again.');
            } finally {
                setIsLoadingProducts(false);
            }
        };

        loadProducts();
    }, [data.intent, selectedCurrency]); // Re-run if intent or currency changes

    const handleCategorySelect = (category: Category) => {
        setSelectedCategory(category);
        setCurrentView('subcategories');
    };

    const handleSubcategorySelect = (subcategory: Subcategory) => {
        setSelectedSubcategory(subcategory);

        // Special handling for Zimparks Holiday: Skip directly to ZimparksHolidayStep
        if (subcategory.name === 'Destinations' || subcategory.name === 'Zimparks Lodges/Cottages') {
            onNext({
                category: selectedCategory?.name || 'Zimparks Holiday Package',
                subcategory: subcategory.name,
                business: 'Zimparks Vacation Package',
                scale: 'Standard',
                amount: 0, // Will be calculated in ZimparksHolidayStep
                creditTerm: null,
                monthlyPayment: 0,
                productId: null,
                productName: 'Zimparks Holiday Package',
                categoryId: selectedCategory?.id,
                selectedBusiness: {
                    id: null,
                    name: 'Zimparks Vacation Package',
                    basePrice: 0,
                    salesData: []
                },
                selectedScale: null,
                color: null,
                interiorColor: null,
                exteriorColor: null
            });
            return;
        }

        // Special handling for Driving School / License Courses: Skip directly to LicenseCoursesStep
        if (subcategory.name === 'Driving School' || subcategory.name === 'License Courses') {
            onNext({
                category: selectedCategory?.name || 'Drivers License',
                subcategory: subcategory.name,
                business: 'License Courses',
                scale: 'Standard',
                amount: 0, // Will be calculated in LicenseCoursesStep
                creditTerm: null,
                monthlyPayment: 0,
                productId: null,
                productName: 'License Courses',
                categoryId: selectedCategory?.id,
                selectedBusiness: {
                    id: null,
                    name: 'License Courses',
                    basePrice: 0,
                    salesData: []
                },
                selectedScale: null,
                color: null,
                interiorColor: null,
                exteriorColor: null
            });
            return;
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
        setSelectedInteriorColor(null); // Reset interior color
        setSelectedExteriorColor(null); // Reset exterior color
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

        if (business.name === 'Company Registration') {
            if (business.scales.length > 0) {
                const scale = business.scales[0];
                const amount = scale.custom_price || 195.00;
                const grossLoan = amount * 1.06; // Apply standard markup

                // Pass a complete data payload so validation passes
                onNext({
                    // Legacy fields for backward compatibility
                    category: selectedCategory?.name || 'Small Business Support',
                    subcategory: selectedSubcategory?.name || 'Fees and Licensing',
                    business: business.name,
                    scale: scale.name,
                    amount: grossLoan,
                    creditTerm: null, // Will be selected in CreditTermSelection step
                    monthlyPayment: 0, // Will be calculated later
                    // New fields with IDs for better tracking
                    productId: business.id,
                    productName: business.name,
                    scaleId: scale.id,
                    categoryId: selectedCategory?.id,
                    // Loan fields
                    loanAmount: grossLoan,
                    netLoan: amount,
                    grossLoan: grossLoan,
                    interestRate: '96%',
                    firstPaymentDate: null,
                    lastPaymentDate: null,
                    // Product details
                    selectedBusiness: {
                        id: business.id?.toString(),
                        name: business.name,
                        basePrice: amount,
                        salesData: []
                    },
                    selectedScale: {
                        id: scale.id?.toString(),
                        name: scale.name,
                        custom_price: scale.custom_price
                    },
                    color: null,
                    interiorColor: null,
                    exteriorColor: null
                });
            }
            return;
        }

        // Handle License Courses - skip to dedicated step
        if (business.name === 'License Courses') {
            onNext({
                category: selectedCategory?.name || 'Small Business Support',
                subcategory: selectedSubcategory?.name || 'Driving School',
                business: business.name,
                scale: 'Standard',
                amount: 0, // Will be calculated in LicenseCoursesStep
                creditTerm: null,
                monthlyPayment: 0,
                productId: business.id,
                productName: business.name,
                categoryId: selectedCategory?.id,
                selectedBusiness: {
                    id: business.id?.toString(),
                    name: business.name,
                    basePrice: 0,
                    salesData: []
                },
                selectedScale: null,
                color: null,
                interiorColor: null,
                exteriorColor: null
            });
            return;
        }

        if (isPersonalProducts || data.intent === 'homeConstruction') {
            // ... existing logic ...
            if (business.scales.length === 1) {
                handleScaleSelect(business.scales[0]);
            }
            setCurrentView('product_detail');
        } else {
            setCurrentView('scales');
        }
    };

    const handleScaleSelect = (scale: { id?: number; name: string; multiplier: number; custom_price?: number }) => {
        setSelectedScale(scale);

        let amount = 0;
        if (scale.custom_price) {
            amount = scale.custom_price;
        } else {
            amount = (selectedBusiness?.basePrice || 0) * scale.multiplier;
        }

        // Apply currency conversion
        if (isZiG) {
            amount = parseFloat((amount * ZIG_RATE).toFixed(2));
        }

        setFinalAmount(amount);
        setSelectedTermMonths(null); // Reset term selection
        setValidationError(''); // Clear validation error

        // For Zimparks and MicroBiz, stay on scales view to show description
        const isZimparks = selectedBusiness?.name === 'Zimparks Vacation Package';

        if (isPersonalProducts || data.intent === 'homeConstruction') {
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

        if (isCartMode) {
            onNext({
                category: selectedCategory?.id,
                amount: grossLoan,
                cart: cart,
                currency: selectedCurrency,
                business: cart.map(item => item.name).join(', '),
                finalPrice: finalAmount, // Cart total
                loanAmount: grossLoan,
                selectedBusiness: null,
                selectedScale: null,
                color: null,
                creditTerm: selectedTermMonths,
                monthlyPayment: parseFloat(monthlyPayment.toFixed(2)),
                interestRate: '96%',
                firstPaymentDate: firstPaymentDate.toISOString().split('T')[0],
                lastPaymentDate: lastPaymentDate.toISOString().split('T')[0],
            });
            return;
        }

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
            interestRate: '96%',
            firstPaymentDate: firstPaymentDate.toISOString().split('T')[0],
            lastPaymentDate: lastPaymentDate.toISOString().split('T')[0],
            // Additional details
            includesMESystem,
            meSystemFee,
            includesTraining,
            trainingFee,
            selectedBusiness: {
                id: selectedBusiness?.id?.toString(),
                name: selectedBusiness?.name,
                salesData: []
            },
            selectedScale: selectedScale ? {
                id: selectedScale.id?.toString(),
                name: selectedScale.name
            } : undefined,
            color: selectedColor,
            interiorColor: selectedInteriorColor,
            exteriorColor: selectedExteriorColor
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
                if (isPersonalProducts || isCartMode) {
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



    const handleAddToCart = () => {
        // Validation: Must have business.
        if (!selectedBusiness) return;
        // Validation: If colors exist, must have color selected.
        if (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor) return;
        // Validation: If interior colors exist, must have interior color selected.
        if (selectedBusiness.interiorColors && Array.isArray(selectedBusiness.interiorColors) && selectedBusiness.interiorColors.length > 0 && !selectedInteriorColor) return;
        // Validation: If exterior colors exist, must have exterior color selected.
        if (selectedBusiness.exteriorColors && Array.isArray(selectedBusiness.exteriorColors) && selectedBusiness.exteriorColors.length > 0 && !selectedExteriorColor) return;

        const scale = selectedScale; // Can be null
        const price = scale?.custom_price || (selectedBusiness.basePrice * (scale?.multiplier || 1));

        const newItem = {
            businessId: selectedBusiness.id || 0,
            name: selectedBusiness.name,
            price: price,
            quantity: cartQuantity,
            color: selectedColor || undefined,
            interiorColor: selectedInteriorColor || undefined,
            exteriorColor: selectedExteriorColor || undefined,
            scale: scale?.name
        };

        const newCart = [...cart, newItem];
        setCart(newCart);

        // Auto-proceed for Core House in Construction flow
        if (data.intent === 'homeConstruction' && selectedBusiness.name.toLowerCase().includes('core house')) {
            onNext({
                cart: newCart,
                // Provide validation data required by validateProductStep
                category: 'Construction', // Ensure a category is present
                subcategory: 'Core House', // Ensure subcategory is present for step filtering
                amount: price, // Ensure an amount is present
                isCoreHouseFlow: true // Explicitly flag as Core House flow
            });
            return;
        }

        setCartQuantity(1);
        setSelectedBusiness(null);
        setSelectedSubcategory(null);
        setSelectedInteriorColor(null);
        setSelectedExteriorColor(null);
        setCurrentView('subcategories');
    };

    const handleRemoveFromCart = (index: number) => {
        const newCart = [...cart];
        newCart.splice(index, 1);
        setCart(newCart);
    };

    const cartTotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    // Sync finalAmount with cartTotal when cart changes in cart mode (when viewing terms)
    useEffect(() => {
        if (isCartMode && currentView === 'terms') {
            setFinalAmount(cartTotal);
        }
    }, [cart, cartTotal, isCartMode, currentView]);

    const handleNext = () => {
        if (isCartMode) {
            if (cart.length === 0) {
                setValidationError('Please add at least one item to your basket.');
                return;
            }
            setFinalAmount(cartTotal);
            setCurrentView('terms');
            return;
        }

        // Standard Flow
        if (!selectedBusiness) return;

        // Check if scale/storage is required but not selected
        if (selectedBusiness.scales.length > 0 && !selectedScale) {
            setValidationError('Please select an option');
            return;
        }

        // Check if color is required but not selected
        if (selectedBusiness.colors && selectedBusiness.colors.length > 0 && !selectedColor) {
            setValidationError('Please select a color');
            return;
        }

        // For MicroBiz/Zimparks, validation happens before this step or logic is different, 
        // but generally we proceed to terms or show package details.
        // The buttons handling this call ensure we have selections.

        setCurrentView('terms');
    };

    // If isCartMode, render Basket Summary
    const renderCartSummary = () => {
        // If it's a Core House flow and we have items, we might not want to show the specific cart if it's just the core house
        // However, the user said "only building materials should add to basket".
        // Let's hide the basket display if the ONLY thing in it is a Core House
        // This makes it feel like "Selection" rather than "Shopping"
        const hasCoreHouse = cart.some(item => item.name.toLowerCase().includes('core house'));
        if (hasCoreHouse && cart.length === 1 && data.intent === 'homeConstruction') return null;

        return (
            <Card className="p-4 mb-6 bg-white dark:bg-[#1b1b18] border-[#e5e7eb] dark:border-[#27272a]">
                <h3 className="font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC] flex items-center">
                    <ShoppingBasket className="w-5 h-5 mr-2" />
                    Shopping Basket
                </h3>
                {cart.length === 0 ? (
                    <p className="text-sm text-gray-500">Your basket is empty.</p>
                ) : (
                    <div className="space-y-3">
                        {cart.map((item, index) => (
                            <div key={index} className="flex justify-between items-center text-sm">
                                <div>
                                    <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{item.name}</span>
                                    <div className="text-xs text-gray-500">
                                        {item.color && <span className="mr-2">Color: {item.color}</span>}
                                        {item.scale && <span>Size: {item.scale}</span>}
                                        <span className="ml-2">x{item.quantity}</span>
                                    </div>
                                </div>
                                <div className="flex items-center">
                                    <span className="font-medium mr-3">{formatCurrency(item.price * item.quantity)}</span>
                                    <Button variant="ghost" size="sm" onClick={() => handleRemoveFromCart(index)} className="text-red-500 h-6 w-6 p-0 hover:bg-red-50">
                                        <X className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                        <div className="border-t pt-2 mt-2 flex justify-between font-bold">
                            <span>Total</span>
                            <span>{formatCurrency(cartTotal)}</span>
                        </div>
                    </div>
                )}
            </Card>
        );
    };

    const creditTerms = (selectedBusiness || isCartMode) ? getCreditTermOptions(finalAmount) : [];

    if (isLoadingProducts) {
        return (
            <div className="flex justify-center items-center py-12">
                <Loader2 className="h-8 w-8 animate-spin text-emerald-600" />
            </div>
        );
    }

    if (error) {
        return (
            <Alert className="border-red-500 bg-red-50">
                <AlertDescription className="text-red-800">{error}</AlertDescription>
                <Button onClick={() => window.location.reload()} variant="outline" className="mt-2">
                    Retry
                </Button>
            </Alert>
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
            'beige': '#F5F5DC',
            'natural teak': '#C19A6B',
            'dark oak': '#4B3621',
            'varnish': '#D2691E',
            'natural pine': '#F4A460',
            'clear varnish': '#DEB887',
            'peach': '#FFDAB9',
            'terracotta': '#E2725B',
            'sky blue': '#87CEEB',
            'light grey': '#D3D3D3',
        };
        return map[colorName.toLowerCase()] || colorName.toLowerCase();
    };

    return (
        <div className="space-y-6">
            {isCartMode && renderCartSummary()}
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">
                    {currentView === 'categories' && (isPersonalProducts ? 'Select Product Category' : 'Select Business Category')}
                    {currentView === 'subcategories' && `${selectedCategory?.name} - Select Type`}
                    {currentView === 'businesses' && (isPersonalProducts ? `${selectedSubcategory?.name} - Select Product` : `${selectedSubcategory?.name} - Select Business`)}
                    {currentView === 'zimparks_destinations' && 'Select Your Destination Resort'}
                    {currentView === 'scales' && `${selectedBusiness?.name} - Select ${isPersonalProducts ? 'Quantity' : 'Scale'}`}
                    {currentView === 'terms' && `Net Loan (selling price): ${formatCurrency(finalAmount)}`}
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                    {currentView === 'categories' && (isPersonalProducts ? 'Choose the type of product you want to purchase' : 'Choose the type of business you want to start')}
                    {currentView === 'subcategories' && 'Select a specific category'}
                    {currentView === 'businesses' && (isPersonalProducts ? 'Choose your product' : 'Choose your business type')}
                    {currentView === 'zimparks_destinations' && 'Choose from our exclusive list of 30 premier destinations'}
                    {currentView === 'scales' && (isPersonalProducts ? 'Select quantity or package size' : 'Select the size of your operation')}
                    {currentView === 'terms' && `Net Loan (selling price): ${formatCurrency(finalAmount)}`}
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
                                        <div className="font-semibold mr-1">{isZiG ? 'ZiG' : '$'}</div>
                                        From {formatCurrency(isZiG ? business.basePrice * ZIG_RATE : business.basePrice)}
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
                                    {finalAmount > 0
                                        ? formatCurrency(finalAmount)
                                        : formatCurrency(isZiG ? selectedBusiness.basePrice * ZIG_RATE : selectedBusiness.basePrice)
                                    }
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

                            {/* Color Selection (Legacy - single color) */}
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

                            {/* Interior Paint Color Selection */}
                            {selectedBusiness.interiorColors && Array.isArray(selectedBusiness.interiorColors) && selectedBusiness.interiorColors.length > 0 && (
                                <div className="mt-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        🎨 Interior Paint Color: <span className="text-gray-500 font-normal">{selectedInteriorColor || 'Select interior color'}</span>
                                    </label>
                                    <div className="flex flex-wrap gap-3">
                                        {selectedBusiness.interiorColors.map((color) => (
                                            <button
                                                key={`interior-${color}`}
                                                onClick={() => setSelectedInteriorColor(color)}
                                                className={`
                                                    w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
                                                    ${selectedInteriorColor === color
                                                        ? 'border-blue-600 ring-2 ring-blue-100 scale-110'
                                                        : 'border-transparent hover:scale-105'
                                                    }
                                                `}
                                                style={{ backgroundColor: getColorHex(color), boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}
                                                title={`Interior: ${color}`}
                                            >
                                                {selectedInteriorColor === color && (
                                                    <Check className={`h-5 w-5 ${['white', 'yellow', 'cream', 'light grey', 'peach', 'sky blue', 'lavender'].includes(color.toLowerCase()) ? 'text-black' : 'text-white'}`} />
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Exterior Paint Color Selection */}
                            {selectedBusiness.exteriorColors && Array.isArray(selectedBusiness.exteriorColors) && selectedBusiness.exteriorColors.length > 0 && (
                                <div className="mt-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        🏠 Exterior Paint Color: <span className="text-gray-500 font-normal">{selectedExteriorColor || 'Select exterior color'}</span>
                                    </label>
                                    <div className="flex flex-wrap gap-3">
                                        {selectedBusiness.exteriorColors.map((color) => (
                                            <button
                                                key={`exterior-${color}`}
                                                onClick={() => setSelectedExteriorColor(color)}
                                                className={`
                                                    w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
                                                    ${selectedExteriorColor === color
                                                        ? 'border-orange-600 ring-2 ring-orange-100 scale-110'
                                                        : 'border-transparent hover:scale-105'
                                                    }
                                                `}
                                                style={{ backgroundColor: getColorHex(color), boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}
                                                title={`Exterior: ${color}`}
                                            >
                                                {selectedExteriorColor === color && (
                                                    <Check className={`h-5 w-5 ${['white', 'yellow', 'cream', 'light grey', 'sand'].includes(color.toLowerCase()) ? 'text-black' : 'text-white'}`} />
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {/* Action Button */}
                            <div className="pt-6">
                                {isCartMode ? (
                                    <div className="flex flex-col gap-3">
                                        <Button
                                            onClick={handleAddToCart}
                                            disabled={
                                                (selectedBusiness.scales && selectedBusiness.scales.length > 0 && !selectedScale) ||
                                                (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor) ||
                                                (selectedBusiness.interiorColors && Array.isArray(selectedBusiness.interiorColors) && selectedBusiness.interiorColors.length > 0 && !selectedInteriorColor) ||
                                                (selectedBusiness.exteriorColors && Array.isArray(selectedBusiness.exteriorColors) && selectedBusiness.exteriorColors.length > 0 && !selectedExteriorColor)
                                            }
                                            className={`w-full text-white py-6 text-lg ${selectedBusiness.name.toLowerCase().includes('core house')
                                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                                : 'bg-blue-600 hover:bg-blue-700'
                                                }`}
                                        >
                                            {selectedBusiness.name.toLowerCase().includes('core house') ? (
                                                <>
                                                    Select & Continue
                                                    <ChevronRight className="ml-2 h-5 w-5" />
                                                </>
                                            ) : (
                                                <>
                                                    <ShoppingBasket className="mr-2 h-5 w-5" />
                                                    Add to Basket
                                                </>
                                            )}
                                        </Button>
                                        <p className="text-xs text-gray-500 text-center">
                                            Add item to your basket to proceed
                                        </p>

                                        <div className="relative flex py-2 items-center">
                                            <div className="flex-grow border-t border-gray-300"></div>
                                            <span className="flex-shrink-0 mx-4 text-gray-400 text-sm">Review Cart</span>
                                            <div className="flex-grow border-t border-gray-300"></div>
                                        </div>

                                        <Button
                                            onClick={handleNext}
                                            disabled={cart.length === 0}
                                            variant="outline"
                                            className="w-full border-blue-600 text-blue-600 hover:bg-blue-50 py-4"
                                        >
                                            Proceed to Terms
                                            <ChevronRight className="ml-2 h-4 w-4" />
                                        </Button>
                                    </div>
                                ) : (
                                    <Button
                                        onClick={handleNext}
                                        disabled={
                                            (selectedBusiness.scales && selectedBusiness.scales.length > 0 && !selectedScale) ||
                                            (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor) ||
                                            (selectedBusiness.interiorColors && Array.isArray(selectedBusiness.interiorColors) && selectedBusiness.interiorColors.length > 0 && !selectedInteriorColor) ||
                                            (selectedBusiness.exteriorColors && Array.isArray(selectedBusiness.exteriorColors) && selectedBusiness.exteriorColors.length > 0 && !selectedExteriorColor)
                                        }
                                        className="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-6 text-lg"
                                    >
                                        Continue to Payment Terms
                                        <ChevronRight className="ml-2 h-5 w-5" />
                                    </Button>
                                )}

                                {!isCartMode && (
                                    <p className="text-xs text-gray-500 text-center mt-3">
                                        {!selectedScale && selectedBusiness.scales && selectedBusiness.scales.length > 0
                                            ? "Please select an option to continue"
                                            : (selectedBusiness.colors && Array.isArray(selectedBusiness.colors) && selectedBusiness.colors.length > 0 && !selectedColor)
                                                ? "Please select a color to continue"
                                                : (selectedBusiness.interiorColors && Array.isArray(selectedBusiness.interiorColors) && selectedBusiness.interiorColors.length > 0 && !selectedInteriorColor)
                                                    ? "Please select an interior paint color"
                                                    : (selectedBusiness.exteriorColors && Array.isArray(selectedBusiness.exteriorColors) && selectedBusiness.exteriorColors.length > 0 && !selectedExteriorColor)
                                                        ? "Please select an exterior paint color"
                                                        : "Next: Choose your repayment plan"
                                        }
                                    </p>
                                )}
                            </div>
                        </div >
                    </div >
                )}

                {
                    currentView === 'scales' && selectedBusiness && (
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
                                    // Format scale name: add "Package" suffix for Lite, Standard, Full house and handle rebranding
                                    const formatScaleName = (name: string) => {
                                        if (name === 'Lite') return 'Bronze Package';
                                        if (name === 'Standard') return 'Silver Package';
                                        if (name === 'Full house' || name === 'Full House') return 'Gold Package';

                                        const packageScales = ['Bronze Package', 'Silver Package', 'Gold Package'];
                                        if (packageScales.includes(name)) {
                                            return name; // Already correct
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
                                                Cost: {formatCurrency(isZiG ? amount * ZIG_RATE : amount)}
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
                                                Agree & Continue
                                                <ChevronRight className="ml-2 h-4 w-4" />
                                            </Button>
                                        </div>
                                    </Card>
                                </div>
                            )}
                        </>
                    )
                }

                {
                    currentView === 'terms' && (
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
                                                    <span className="text-xl font-bold text-emerald-600">+{formatCurrency(finalAmount * ME_SYSTEM_PERCENTAGE)}</span>
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
                                                    <span className="text-xl font-bold text-purple-600">+{formatCurrency(finalAmount * TRAINING_PERCENTAGE)}</span>
                                                    <span className="text-sm text-gray-500">(5.5% of selling price)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                </>
                            )}

                            {/* Total Amount Display */}
                            <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Gross Loan inclusive of 6% bank charges</p>
                                <p className="text-3xl font-bold text-emerald-600">
                                    {formatCurrency((finalAmount + (includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0) + (includesTraining ? finalAmount * TRAINING_PERCENTAGE : 0)) * 1.06)}
                                </p>
                                {(includesMESystem || includesTraining) && (
                                    <p className="text-sm text-gray-500 mt-1">
                                        Base Price: {formatCurrency(finalAmount + (includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0) + (includesTraining ? finalAmount * TRAINING_PERCENTAGE : 0))}
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
                                                    {(() => {
                                                        const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
                                                        const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                        const netLoan = finalAmount + meSystemFee + trainingFee;
                                                        return formatCurrency(netLoan);
                                                    })()}
                                                </p>
                                            </div>

                                            {/* Gross Loan */}
                                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Gross Loan</p>
                                                <p className="text-2xl font-bold text-emerald-600">
                                                    {(() => {
                                                        const meSystemFee = includesMESystem ? (finalAmount * ME_SYSTEM_PERCENTAGE) : 0;
                                                        const trainingFee = includesTraining ? (finalAmount * TRAINING_PERCENTAGE) : 0;
                                                        const netLoan = finalAmount + meSystemFee + trainingFee;
                                                        const grossLoan = netLoan * 1.06;
                                                        return formatCurrency(grossLoan);
                                                    })()}
                                                </p>
                                            </div>

                                            {/* Monthly Payment */}
                                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Monthly Payment</p>
                                                <p className="text-2xl font-bold text-blue-600">
                                                    {(() => {
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
                                                        return formatCurrency(monthlyPayment);
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
                    )
                }
            </div >

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

                {/* Cart Mode Done Button */}
                {isCartMode && cart.length > 0 && currentView !== 'terms' && (
                    <Button
                        onClick={handleNext}
                        className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        Proceed
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                )}

                {/* Hide Continue button for Core House as it has its own flow */}
                {currentView === 'terms' && (!data.intent || data.intent !== 'homeConstruction') && (
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
        </div >
    );
};

export default ProductSelection;