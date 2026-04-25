import React, { useState, useEffect } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Loader2, Plus, Minus, Trash2, ShoppingCart } from 'lucide-react';
import axios from 'axios';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Product {
    id: number;
    name: string;
    product_code: string;
    base_price: number;
    selling_price?: number;
    image_url?: string;
}

interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    product: Product;
}

interface BuildingMaterialsCartProps {
    sessionId: string;
    packageAmount: number;
    onNext: () => void;
    onBack: () => void;
    onCartUpdate: (items: CartItem[], total: number) => void;
}

const BuildingMaterialsCart: React.FC<BuildingMaterialsCartProps> = ({
    sessionId,
    packageAmount,
    onNext,
    onBack,
    onCartUpdate
}) => {
    const [products, setProducts] = useState<Product[]>([]);
    const [cartItems, setCartItems] = useState<CartItem[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isUpdating, setIsUpdating] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchProductsAndCart();
    }, []);

    const fetchProductsAndCart = async () => {
        try {
            setIsLoading(true);
            // Fetch building materials products (Assuming a specific category ID or just general products for now)
            // In a real app, this should fetch only products belonging to "Building Materials"
            const productsRes = await axios.get('/api/products');
            
            // Fetch current cart
            const cartRes = await axios.get(`/api/cart/${sessionId}`);

            if (productsRes.data.success) {
                // Filter only building materials if needed, but for now we take what's returned
                // Depending on the implementation of /api/products
                setProducts(productsRes.data.data.data || productsRes.data.data);
            }

            if (cartRes.data.success && cartRes.data.cart) {
                setCartItems(cartRes.data.cart.items || []);
                calculateTotal(cartRes.data.cart.items || []);
            }
        } catch (err) {
            console.error('Failed to load cart data', err);
            setError('Failed to load products and cart data.');
        } finally {
            setIsLoading(false);
        }
    };

    const calculateTotal = (items: CartItem[]) => {
        const total = items.reduce((sum, item) => sum + parseFloat(item.subtotal.toString()), 0);
        onCartUpdate(items, total);
        return total;
    };

    const currentTotal = cartItems.reduce((sum, item) => sum + parseFloat(item.subtotal.toString()), 0);
    const remainingAmount = packageAmount - currentTotal;

    const handleAddToCart = async (product: Product) => {
        const price = product.selling_price || product.base_price;
        
        // Ensure adding 1 doesn't exceed package limit
        if (currentTotal + parseFloat(price.toString()) > packageAmount) {
            setError(`Adding this item exceeds your package limit of $${packageAmount.toFixed(2)}`);
            return;
        }

        try {
            setIsUpdating(true);
            setError('');
            const res = await axios.post('/api/cart/add', {
                session_id: sessionId,
                product_id: product.id,
                quantity: 1
            });

            if (res.data.success) {
                setCartItems(res.data.cart.items);
                calculateTotal(res.data.cart.items);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to add item to cart');
        } finally {
            setIsUpdating(false);
        }
    };

    const handleUpdateQuantity = async (cartItemId: number, newQuantity: number) => {
        // Find item to check price diff
        const item = cartItems.find(i => i.id === cartItemId);
        if (!item) return;

        const diffQty = newQuantity - item.quantity;
        const diffAmount = diffQty * item.unit_price;

        if (currentTotal + diffAmount > packageAmount) {
            setError(`Updating this item exceeds your package limit of $${packageAmount.toFixed(2)}`);
            return;
        }

        try {
            setIsUpdating(true);
            setError('');
            const res = await axios.post('/api/cart/update', {
                session_id: sessionId,
                cart_item_id: cartItemId,
                quantity: newQuantity
            });

            if (res.data.success) {
                setCartItems(res.data.cart.items || []);
                calculateTotal(res.data.cart.items || []);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to update quantity');
        } finally {
            setIsUpdating(false);
        }
    };

    const handleRemoveItem = async (cartItemId: number) => {
        try {
            setIsUpdating(true);
            setError('');
            const res = await axios.post('/api/cart/remove', {
                session_id: sessionId,
                cart_item_id: cartItemId
            });

            if (res.data.success) {
                setCartItems(res.data.cart.items || []);
                calculateTotal(res.data.cart.items || []);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to remove item');
        } finally {
            setIsUpdating(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex justify-center items-center py-12">
                <Loader2 className="h-8 w-8 animate-spin text-emerald-600" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Build Your Material Cart</h2>
                <p className="text-gray-600">Select items up to your package limit.</p>
                <div className="mt-4 inline-block bg-emerald-50 text-emerald-800 px-4 py-2 rounded-lg border border-emerald-200">
                    <span className="font-semibold text-lg">Package Limit: ${packageAmount.toFixed(2)}</span>
                    <span className="mx-2">|</span>
                    <span className="font-semibold text-lg">Remaining: ${remainingAmount.toFixed(2)}</span>
                </div>
            </div>

            {error && (
                <Alert className="border-red-500 bg-red-50">
                    <AlertDescription className="text-red-800">{error}</AlertDescription>
                </Alert>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Product List */}
                <div className="lg:col-span-2 space-y-4 max-h-[600px] overflow-y-auto pr-2">
                    <h3 className="text-lg font-semibold">Available Materials</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {products.map(product => (
                            <Card key={product.id} className="p-4 flex flex-col justify-between hover:shadow-md transition-shadow">
                                <div>
                                    <h4 className="font-semibold truncate">{product.name}</h4>
                                    <p className="text-sm text-gray-500 mt-1">Code: {product.product_code}</p>
                                    <p className="text-emerald-600 font-bold mt-2">
                                        ${parseFloat((product.selling_price || product.base_price).toString()).toFixed(2)}
                                    </p>
                                </div>
                                <Button 
                                    className="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 text-white"
                                    onClick={() => handleAddToCart(product)}
                                    disabled={isUpdating || currentTotal + parseFloat((product.selling_price || product.base_price).toString()) > packageAmount}
                                >
                                    <Plus className="w-4 h-4 mr-2" /> Add
                                </Button>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Cart Sidebar */}
                <div className="lg:col-span-1">
                    <Card className="p-4 sticky top-4">
                        <div className="flex items-center gap-2 mb-4 pb-4 border-b">
                            <ShoppingCart className="w-5 h-5 text-emerald-600" />
                            <h3 className="text-lg font-semibold">Your Cart</h3>
                        </div>

                        {cartItems.length === 0 ? (
                            <div className="text-center py-8 text-gray-500">
                                <ShoppingCart className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                <p>Your cart is empty.</p>
                                <p className="text-sm mt-1">Add items from the list to continue.</p>
                            </div>
                        ) : (
                            <div className="space-y-4 max-h-[400px] overflow-y-auto">
                                {cartItems.map(item => (
                                    <div key={item.id} className="flex flex-col bg-gray-50 p-3 rounded-lg border border-gray-100">
                                        <div className="flex justify-between items-start mb-2">
                                            <span className="font-medium text-sm leading-tight pr-2">{item.product.name}</span>
                                            <button 
                                                onClick={() => handleRemoveItem(item.id)}
                                                className="text-red-500 hover:text-red-700 p-1 rounded-md hover:bg-red-50 transition-colors"
                                                disabled={isUpdating}
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div className="flex justify-between items-center mt-auto">
                                            <div className="flex items-center bg-white border rounded-md">
                                                <button
                                                    onClick={() => handleUpdateQuantity(item.id, item.quantity - 1)}
                                                    className="p-1 text-gray-500 hover:bg-gray-100 disabled:opacity-50 transition-colors"
                                                    disabled={isUpdating || item.quantity <= 1}
                                                >
                                                    <Minus className="w-4 h-4" />
                                                </button>
                                                <span className="px-3 text-sm font-medium w-8 text-center">{item.quantity}</span>
                                                <button
                                                    onClick={() => handleUpdateQuantity(item.id, item.quantity + 1)}
                                                    className="p-1 text-gray-500 hover:bg-gray-100 disabled:opacity-50 transition-colors"
                                                    disabled={isUpdating || currentTotal + parseFloat(item.unit_price.toString()) > packageAmount}
                                                >
                                                    <Plus className="w-4 h-4" />
                                                </button>
                                            </div>
                                            <span className="font-semibold text-emerald-700">
                                                ${parseFloat(item.subtotal.toString()).toFixed(2)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="mt-4 pt-4 border-t space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Total Selection:</span>
                                <span className="font-semibold">${currentTotal.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Package Amount:</span>
                                <span>${packageAmount.toFixed(2)}</span>
                            </div>
                            
                            {/* Visual progress bar */}
                            <div className="w-full bg-gray-200 rounded-full h-2.5 mt-2 overflow-hidden flex">
                                <div 
                                    className={`h-2.5 rounded-full ${currentTotal === packageAmount ? 'bg-emerald-500' : 'bg-blue-500'}`}
                                    style={{ width: `${Math.min(100, (currentTotal / packageAmount) * 100)}%` }}
                                ></div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <div className="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                <Button 
                    onClick={onBack} 
                    variant="outline"
                    className="min-w-[120px]"
                >
                    Back
                </Button>
                <Button 
                    onClick={onNext} 
                    className="bg-emerald-600 hover:bg-emerald-700 text-white min-w-[120px]"
                    disabled={cartItems.length === 0 || isUpdating}
                >
                    Continue
                </Button>
            </div>
        </div>
    );
};

export default BuildingMaterialsCart;
