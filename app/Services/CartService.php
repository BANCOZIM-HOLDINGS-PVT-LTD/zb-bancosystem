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
            if ($inventory && $inventory->available_stock < $quantity) {
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
                
                if ($inventory && $inventory->available_stock < $newQuantity) {
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
            if ($inventory && $inventory->available_stock < $quantity) {
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

    public function getTotal(string $sessionId): float
    {
        $cart = $this->getCart($sessionId);

        if (!$cart) {
            return 0.0;
        }

        return (float) $cart->items->sum('subtotal');
    }

    public function validateStock(string $sessionId): array
    {
        $cart = $this->getCart($sessionId);
        $errors = [];

        if (!$cart) {
            return ['valid' => false, 'errors' => ['Cart not found.']];
        }

        foreach ($cart->items as $item) {
            $inventory = $item->product?->inventory;

            if ($inventory && $inventory->available_stock < $item->quantity) {
                $errors[] = "{$item->product->name} has {$inventory->available_stock} available; {$item->quantity} requested.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function applyBulkDiscounts(string $sessionId): array
    {
        $cart = $this->getCart($sessionId);

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart not found.'];
        }

        foreach ($cart->items as $item) {
            $discount = match (true) {
                $item->quantity >= 50 => 0.10,
                $item->quantity >= 20 => 0.05,
                default => 0,
            };

            $unitPrice = (float) $item->unit_price;
            $item->subtotal = round($item->quantity * $unitPrice * (1 - $discount), 2);
            $item->save();
        }

        return ['success' => true, 'cart' => $this->getCart($sessionId)];
    }

    public function reserveStock(string $sessionId): array
    {
        $validation = $this->validateStock($sessionId);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $cart = $this->getCart($sessionId);
        foreach ($cart->items as $item) {
            $item->product?->inventory?->reserveStock($item->quantity);
        }

        return ['success' => true];
    }
}
