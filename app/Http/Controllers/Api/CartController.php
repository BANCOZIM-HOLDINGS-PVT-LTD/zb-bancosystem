<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function getCart(Request $request, string $sessionId)
    {
        $cart = $this->cartService->getCart($sessionId);
        
        return response()->json([
            'success' => true,
            'cart' => $cart
        ]);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->cartService->addItem(
            $request->session_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'cart_item_id' => 'required|integer|exists:cart_items,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $result = $this->cartService->updateQuantity(
            $request->session_id,
            $request->cart_item_id,
            $request->quantity
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function removeItem(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'cart_item_id' => 'required|integer|exists:cart_items,id',
        ]);

        $result = $this->cartService->removeItem(
            $request->session_id,
            $request->cart_item_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function clearCart(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $success = $this->cartService->clearCart($request->session_id);

        return response()->json(['success' => $success]);
    }
}
