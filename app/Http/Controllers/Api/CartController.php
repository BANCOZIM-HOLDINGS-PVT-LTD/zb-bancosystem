<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

    public function index(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        return $this->getCart($request, $request->session_id);
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

    public function updateItem(Request $request, int $id)
    {
        $request->merge(['cart_item_id' => $id]);

        return $this->updateQuantity($request);
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

    public function removeItemById(Request $request, int $id)
    {
        $request->merge(['cart_item_id' => $id]);

        return $this->removeItem($request);
    }

    public function clearCart(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $success = $this->cartService->clearCart($request->session_id);

        return response()->json(['success' => $success]);
    }

    public function products(Request $request)
    {
        $query = Product::query()
            ->with('inventory', 'subCategory.category')
            ->whereHas('subCategory.category', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%building%')
                        ->orWhere('name', 'like', '%material%')
                        ->orWhere('name', 'like', '%hardware%')
                        ->orWhere('name', 'like', '%construction%');
                });
            });

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->inCategory($categoryId);
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange((float) $request->input('min_price', 0), (float) $request->input('max_price', 999999));
        }

        return response()->json([
            'success' => true,
            'products' => $query->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function total(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        return response()->json([
            'success' => true,
            'total' => $this->cartService->getTotal($request->session_id),
        ]);
    }

    public function validateStock(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        return response()->json($this->cartService->validateStock($request->session_id));
    }
}
