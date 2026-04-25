<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\Log;

class CartService
{
    /**
     * Get or create a cart for a session
     */
    public function getOrCreateCart(string $sessionId): Cart
    {
        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'status' => 'active']
        );
    }

    /**
     * Get cart details with items
     */
    public function getCart(string $sessionId): ?Cart
    {
        return Cart::with('items.product.inventory')
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Add an item to the cart
     */
    public function addItem(string $sessionId, int $productId, int $quantity): array
    {
        try {
            $cart = $this->getOrCreateCart($sessionId);
            $product = Product::findOrFail($productId);
            
            // Check inventory (if inventory tracking is enabled)
            $inventory = ProductInventory::where('product_id', $productId)->first();
            if ($inventory && $inventory->quantity < $quantity) {
                return ['success' => false, 'message' => 'Not enough stock available.'];
            }

            // Check if item already exists in cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->first();

            $price = $product->selling_price ?? $product->base_price;

            if ($cartItem) {
                // Update existing item
                $newQuantity = $cartItem->quantity + $quantity;
                
                if ($inventory && $inventory->quantity < $newQuantity) {
                    return ['success' => false, 'message' => 'Not enough stock available for total quantity.'];
                }

                $cartItem->quantity = $newQuantity;
                $cartItem->subtotal = $newQuantity * $price;
                $cartItem->save();
            } else {
                // Create new item
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'subtotal' => $quantity * $price,
                ]);
            }

            return ['success' => true, 'cart' => $this->getCart($sessionId)];
        } catch (\Exception $e) {
            Log::error('Cart addItem error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add item to cart.'];
        }
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(string $sessionId, int $cartItemId, int $quantity): array
    {
        try {
            $cart = $this->getCart($sessionId);
            if (!$cart) {
                return ['success' => false, 'message' => 'Cart not found.'];
            }

            $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $cartItemId)->first();
            if (!$cartItem) {
                return ['success' => false, 'message' => 'Item not found in cart.'];
            }

            if ($quantity <= 0) {
                $cartItem->delete();
                return ['success' => true, 'cart' => $this->getCart($sessionId)];
            }

            // Check inventory
            $inventory = ProductInventory::where('product_id', $cartItem->product_id)->first();
            if ($inventory && $inventory->quantity < $quantity) {
                return ['success' => false, 'message' => 'Not enough stock available.'];
            }

            $cartItem->quantity = $quantity;
            $cartItem->subtotal = $quantity * $cartItem->unit_price;
            $cartItem->save();

            return ['success' => true, 'cart' => $this->getCart($sessionId)];
        } catch (\Exception $e) {
            Log::error('Cart updateQuantity error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update quantity.'];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(string $sessionId, int $cartItemId): array
    {
        try {
            $cart = $this->getCart($sessionId);
            if (!$cart) {
                return ['success' => false, 'message' => 'Cart not found.'];
            }

            CartItem::where('cart_id', $cart->id)->where('id', $cartItemId)->delete();

            return ['success' => true, 'cart' => $this->getCart($sessionId)];
        } catch (\Exception $e) {
            Log::error('Cart removeItem error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to remove item.'];
        }
    }

    /**
     * Clear cart
     */
    public function clearCart(string $sessionId): bool
    {
        $cart = $this->getCart($sessionId);
        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
            return true;
        }
        return false;
    }
}
