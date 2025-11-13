<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $categories = DB::table('product_categories')->get();

        $productCatalog = [];

        foreach ($categories as $category) {
            $subcategories = DB::table('product_sub_categories')->where('product_category_id', $category->id)->get();

            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'emoji' => $category->emoji,
                'subcategories' => [],
            ];

            foreach ($subcategories as $subcategory) {
                $products = DB::table('products')->where('product_sub_category_id', $subcategory->id)->get();

                $subcategoryData = [
                    'name' => $subcategory->name,
                    'businesses' => [],
                ];

                foreach ($products as $product) {
                    $packageSizes = DB::table('product_package_sizes')->where('product_id', $product->id)->get();

                    $productData = [
                        'name' => $product->name,
                        'basePrice' => (float) $product->base_price,
                        'scales' => [],
                    ];

                    foreach ($packageSizes as $packageSize) {
                        $productData['scales'][] = [
                            'name' => $packageSize->name,
                            'multiplier' => (float) $packageSize->multiplier,
                            'custom_price' => isset($packageSize->custom_price) ? (float) $packageSize->custom_price : null,
                        ];
                    }

                    $subcategoryData['businesses'][] = $productData;
                }

                $categoryData['subcategories'][] = $subcategoryData;
            }

            $productCatalog[] = $categoryData;
        }

        return response()->json($productCatalog);
    }

    /**
     * Get all product categories with subcategories (Enhanced version)
     */
    public function getCategories(): JsonResponse
    {
        $categories = ProductCategory::with(['subCategories.products.packageSizes'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'emoji' => $category->emoji,
                    'product_count' => $category->product_count,
                    'price_range' => $category->price_range,
                    'subcategories' => $category->subCategories->map(function ($subCategory) {
                        return [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                            'product_count' => $subCategory->product_count,
                            'products' => $subCategory->products->map(function ($product) {
                                return [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'base_price' => $product->base_price,
                                    'formatted_price_range' => $product->formatted_price_range,
                                    'image_url' => $product->image_url,
                                    'package_sizes' => $product->packageSizes->map(function ($size) {
                                        return [
                                            'id' => $size->id,
                                            'name' => $size->name,
                                            'multiplier' => $size->multiplier,
                                            'calculated_price' => $size->calculated_price,
                                            'formatted_price' => $size->formatted_price,
                                            'display_name' => $size->display_name,
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(int $categoryId): JsonResponse
    {
        $category = ProductCategory::with(['subCategories.products.packageSizes'])
            ->findOrFail($categoryId);

        $products = Product::inCategory($categoryId)
            ->with(['subCategory', 'packageSizes'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'emoji' => $category->emoji,
                ],
                'products' => $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'subcategory' => $product->subCategory->name,
                        'base_price' => $product->base_price,
                        'formatted_price_range' => $product->formatted_price_range,
                        'image_url' => $product->image_url,
                        'package_sizes' => $product->packageSizes->map(function ($size) {
                            return [
                                'id' => $size->id,
                                'name' => $size->name,
                                'calculated_price' => $size->calculated_price,
                                'formatted_price' => $size->formatted_price,
                                'display_name' => $size->display_name,
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get a specific product with details
     */
    public function getProduct(int $productId): JsonResponse
    {
        $product = Product::with(['subCategory.category', 'packageSizes'])
            ->findOrFail($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'base_price' => $product->base_price,
                'image_url' => $product->image_url,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'emoji' => $product->category->emoji,
                ],
                'subcategory' => [
                    'id' => $product->subCategory->id,
                    'name' => $product->subCategory->name,
                ],
                'full_category_path' => $product->full_category_path,
                'price_range' => $product->price_range,
                'formatted_price_range' => $product->formatted_price_range,
                'package_sizes' => $product->packageSizes->map(function ($size) {
                    return [
                        'id' => $size->id,
                        'name' => $size->name,
                        'multiplier' => $size->multiplier,
                        'custom_price' => $size->custom_price,
                        'calculated_price' => $size->calculated_price,
                        'formatted_price' => $size->formatted_price,
                        'display_name' => $size->display_name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Search products
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
        ]);

        $query = Product::with(['subCategory.category', 'packageSizes'])
            ->search($request->query);

        if ($request->category_id) {
            $query->inCategory($request->category_id);
        }

        if ($request->min_price && $request->max_price) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        $products = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'subcategory' => $product->subCategory->name,
                    'full_category_path' => $product->full_category_path,
                    'base_price' => $product->base_price,
                    'formatted_price_range' => $product->formatted_price_range,
                    'image_url' => $product->image_url,
                    'package_sizes_count' => $product->packageSizes->count(),
                ];
            }),
        ]);
    }

    /**
     * Get product catalog in frontend format (matches categories.ts structure)
     */
    public function getFrontendCatalog(): JsonResponse
    {
        $categories = ProductCategory::with(['subCategories.products.packageSizes'])
            ->orderBy('name')
            ->get();

        $frontendData = $categories->map(function ($category) {
            return [
                'id' => $category->slug ?? strtolower(str_replace(' ', '-', $category->name)),
                'name' => $category->name,
                'emoji' => $category->emoji,
                'subcategories' => $category->subCategories->map(function ($subCategory) {
                    return [
                        'name' => $subCategory->name,
                        'businesses' => $subCategory->products->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'basePrice' => (float) $product->base_price,
                                'scales' => $product->packageSizes->map(function ($size) {
                                    return [
                                        'id' => $size->id,
                                        'name' => $size->name,
                                        'multiplier' => (float) $size->multiplier,
                                    ];
                                })->toArray(),
                                'tenure' => 24, // Default tenure in months
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();

        return response()->json($frontendData);
    }

    /**
     * Get product statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => ProductCategory::count(),
            'total_subcategories' => ProductSubCategory::count(),
            'price_statistics' => Product::selectRaw('
                MIN(base_price) as min_price,
                MAX(base_price) as max_price,
                AVG(base_price) as avg_price,
                COUNT(*) as total_products
            ')->first(),
            'category_breakdown' => ProductCategory::withCount('products')
                ->orderBy('products_count', 'desc')
                ->get()
                ->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'emoji' => $category->emoji,
                        'product_count' => $category->products_count,
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}