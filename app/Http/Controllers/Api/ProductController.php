<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\MicrobizCategory;
use App\Models\ProductSubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Get intent parameter to filter by type
        $intent = $request->query('intent');

        $query = DB::table('product_categories');

        // Filter by type based on intent
        if ($intent) {
            // Map legacy/alias intents to database types
            $typeMap = [
                // Microbiz mappings
                'microBiz' => 'microbiz',
                'microbiz' => 'microbiz',
                
                // Hire Purchase mappings (Personal, Construction, Services)
                'personal' => 'hire_purchase',
                'personalGadgets' => 'hire_purchase',
                'personalServices' => 'hire_purchase',
                'hirePurchase' => 'hire_purchase',
                'homeConstruction' => 'hire_purchase',
                'construction' => 'hire_purchase',
            ];
            
            $type = $typeMap[$intent] ?? $intent;
            
            $validTypes = ['microbiz', 'hire_purchase'];
            
            if (in_array($type, $validTypes)) {
                // For MicroBiz, use the dedicated microbiz tables
                if ($type === 'microbiz') {
                    return $this->getMicrobizFrontendCatalog();
                }
                // For services, use microbiz tables with domain='service'
                if (in_array($intent, ['personalServices', 'homeConstruction', 'construction', 'services'])) {
                    return $this->getServiceFrontendCatalog();
                }
                $query->where('type', $type);
            }
        }

        $categories = $query->get();

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

                    // Build proper image URL
                    $imageUrl = null;
                    if ($product->image_url) {
                        // If it's already a full URL, use it as is
                        if (str_starts_with($product->image_url, 'http')) {
                            $imageUrl = $product->image_url;
                        } else {
                            // Images are stored in public/uploads/products/ directory
                            // The image_url contains just the filename or 'products/filename'
                            $path = $product->image_url;
                            // If path doesn't include 'products/', prepend it
                            if (!str_starts_with($path, 'products/')) {
                                $path = 'products/' . $path;
                            }
                            $imageUrl = url('uploads/' . $path);
                        }
                    }



                    $productData = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'basePrice' => (float) $product->base_price,
                        'imageUrl' => $imageUrl,
                        'description' => $product->description ?? null,
                        'description' => $product->description ?? null,
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
    public function getCategories(Request $request): JsonResponse
    {
        // Get intent parameter to filter by type
        $intent = $request->query('intent');

        $query = ProductCategory::with(['subCategories.products.packageSizes']);

        // Filter by type based on intent
        if ($intent) {
            // Map legacy/alias intents to database types
            $typeMap = [
                // Microbiz mappings
                'microBiz' => 'microbiz',
                'microbiz' => 'microbiz',
                
                // Hire Purchase mappings (Personal, Construction, Services)
                'personal' => 'hire_purchase',
                'personalGadgets' => 'hire_purchase',
                'personalServices' => 'hire_purchase',
                'hirePurchase' => 'hire_purchase',
                'homeConstruction' => 'hire_purchase',
                'construction' => 'hire_purchase',
            ];
            
            $type = $typeMap[$intent] ?? $intent;
            
            $validTypes = ['microbiz', 'hire_purchase'];
            
            if (in_array($type, $validTypes)) {
                $query->where('type', $type);
            }
        }

        $categories = $query->orderBy('name')->get();

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
                'series' => $product->series ? [
                    'id' => $product->series->id,
                    'name' => $product->series->name,
                ] : null,
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
    public function getFrontendCatalog(Request $request): JsonResponse
    {
        // Get intent parameter to filter by type
        $intent = $request->query('intent');

        $query = ProductCategory::with(['subCategories.products.packageSizes']);

        // Filter by type based on intent
        if ($intent) {
            // Map legacy/alias intents to database types
            $typeMap = [
                // Microbiz mappings
                'microBiz' => 'microbiz',
                'microbiz' => 'microbiz',
                
                // Hire Purchase mappings (Personal, Construction, Services)
                'personal' => 'hire_purchase',
                'personalGadgets' => 'hire_purchase',
                'personalServices' => 'hire_purchase',
                'hirePurchase' => 'hire_purchase',
                'homeConstruction' => 'hire_purchase',
                'construction' => 'hire_purchase',
            ];
            
            $type = $typeMap[$intent] ?? $intent;
            
            $validTypes = ['microbiz', 'hire_purchase'];
            
            if (in_array($type, $validTypes)) {
                // For MicroBiz, use the dedicated microbiz tables
                if ($type === 'microbiz') {
                    return $this->getMicrobizFrontendCatalog();
                }
                // For services, use microbiz tables with domain='service'
                if (in_array($intent, ['personalServices', 'homeConstruction', 'construction', 'services'])) {
                    return $this->getServiceFrontendCatalog();
                }
                $query->where('type', $type);
            }
        }

        $categories = $query->orderBy('name')->get();

        $frontendData = $categories->map(function ($category) {
            return [
                'id' => $category->slug ?? strtolower(str_replace(' ', '-', $category->name)),
                'name' => $category->name,
                'emoji' => $category->emoji,
                'subcategories' => $category->subCategories->map(function ($subCategory) {
                    // Fetch series for this subcategory
                    $series = \App\Models\ProductSeries::where('product_sub_category_id', $subCategory->id)
                        ->with(['products.packageSizes'])
                        ->get();

                    // Fetch products directly under subcategory (no series)
                    $directProducts = $subCategory->products()
                        ->whereNull('product_series_id')
                        ->with('packageSizes')
                        ->get();

                    return [
                        'name' => $subCategory->name,
                        'series' => $series->map(function ($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'image_url' => $s->image_url,
                                'products' => $s->products->map(function ($product) {
                                    return [
                                        'id' => $product->id,
                                        'name' => $product->name,
                                        'product_code' => $product->product_code,
                                        'specification' => $product->specification,
                                        'basePrice' => (float) $product->selling_price,
                                        'originalPrice' => (float) $product->base_price,
                                        'image_url' => $product->image_url,
                                        'image_url' => $product->image_url,
                                        'scales' => $product->packageSizes->map(function ($size) {
                                            return [
                                                'id' => $size->id,
                                                'name' => $size->name,
                                                'multiplier' => (float) $size->multiplier,
                                                'custom_price' => isset($size->custom_price) ? (float) $size->custom_price : null,
                                            ];
                                        })->toArray(),
                                        'tenure' => 24,
                                    ];
                                })->toArray(),
                            ];
                        })->toArray(),
                        'businesses' => $directProducts->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'product_code' => $product->product_code,
                                'specification' => $product->specification,
                                'basePrice' => (float) $product->selling_price,
                                'originalPrice' => (float) $product->base_price,
                                'image_url' => $product->image_url,
                                'description' => $product->description,
                                'description' => $product->description,
                                'scales' => $product->packageSizes->map(function ($size) {
                                    return [
                                        'id' => $size->id,
                                        'name' => $size->name,
                                        'multiplier' => (float) $size->multiplier,
                                        'custom_price' => isset($size->custom_price) ? (float) $size->custom_price : null,
                                    ];
                                })->toArray(),
                                'tenure' => 24,
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

    /**
     * Get MicroBiz product catalog from dedicated microbiz tables.
     * Maps: MicrobizCategory -> Category, MicrobizSubcategory -> Business, MicrobizPackage tiers -> Scales
     * Only returns domain='microbiz' categories.
     */
    private function getMicrobizFrontendCatalog(): JsonResponse
    {
        $categories = MicrobizCategory::microbiz()
            ->with(['subcategories.packages.items'])
            ->orderBy('name')
            ->get();

        $frontendData = $categories->map(function ($category) {
            return [
                'id' => strtolower(str_replace(' ', '-', $category->name)),
                'name' => $category->name,
                'emoji' => $category->emoji,
                'subcategories' => $category->subcategories->map(function ($subcategory) {
                    $scales = $subcategory->packages
                        ->sortBy('price')
                        ->values()
                        ->map(function ($package) {
                            return [
                                'id' => $package->id,
                                'name' => $package->name,
                                'multiplier' => 1.0,
                                'custom_price' => (float) $package->price,
                                'description' => $package->generated_description, // Use the accessor
                            ];
                        })->toArray();

                    return [
                        'name' => $subcategory->name,
                        'series' => [],
                        'businesses' => [[
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'basePrice' => (float) ($subcategory->packages->sortBy('price')->first()?->price ?? 280.00),
                            'image_url' => $subcategory->image_url,
                            'description' => $subcategory->description,
                            'scales' => $scales,
                            'tenure' => 24,
                        ]],
                    ];
                })->toArray(),
            ];
        })->toArray();

        return response()->json($frontendData);
    }

    /**
     * Get Service catalog from microbiz tables with domain='service'.
     * Same structure as MicroBiz but filtered to services.
     */
    private function getServiceFrontendCatalog(): JsonResponse
    {
        $categories = MicrobizCategory::service()
            ->with(['subcategories.packages.items'])
            ->orderBy('name')
            ->get();

        $frontendData = $categories->map(function ($category) {
            return [
                'id' => strtolower(str_replace(' ', '-', $category->name)),
                'name' => $category->name,
                'emoji' => $category->emoji,
                'subcategories' => $category->subcategories->map(function ($subcategory) {
                    $scales = $subcategory->packages
                        ->sortBy('price')
                        ->values()
                        ->map(function ($package) {
                            return [
                                'id' => $package->id,
                                'name' => $package->name,
                                'multiplier' => 1.0,
                                'custom_price' => (float) $package->price,
                                'description' => $package->generated_description, // Use the accessor
                            ];
                        })->toArray();

                    return [
                        'name' => $subcategory->name,
                        'series' => [],
                        'businesses' => [[
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'basePrice' => (float) ($subcategory->packages->sortBy('price')->first()?->price ?? 0),
                            'image_url' => $subcategory->image_url,
                            'description' => $subcategory->description,
                            'specification' => null,
                            'scales' => $scales,
                            'tenure' => 24,
                        ]],
                    ];
                })->toArray(),
            ];
        })->toArray();

        return response()->json($frontendData);
    }

    /**
     * Get supplier info for a product/subcategory.
     * Used by the Product Delivery Confirmation page to display supplier details and branches.
     * Accepts query params: subcategory, business, category
     */
    public function getSupplierInfo(Request $request): JsonResponse
    {
        $subcategoryName = $request->query('subcategory');
        $businessName = $request->query('business');
        $categoryName = $request->query('category');

        // Try to find supplier via MicrobizSubcategory relationship first
        $supplier = null;

        if ($subcategoryName) {
            $microbizSub = \App\Models\MicrobizSubcategory::where('name', $subcategoryName)
                ->with('supplier')
                ->first();

            if ($microbizSub && $microbizSub->supplier) {
                $supplier = $microbizSub->supplier;
            }
        }

        // Fallback: match supplier by keyword in business/category name
        if (!$supplier) {
            $searchTerm = $businessName ?: $subcategoryName ?: $categoryName ?: '';
            $searchLower = strtolower($searchTerm);

            // Keyword-to-supplier mapping
            $supplierMappings = [
                'chicken' => 'Farm & City',
                'broiler' => 'Farm & City',
                'layer' => 'Farm & City',
                'agriculture' => 'Farm & City',
                'agri' => 'Farm & City',
                'farming' => 'Farm & City',
                'machinery' => 'Farm & City',
                'greenhouse' => 'Farm & City',
                'irrigation' => 'Farm & City',
                'tractor' => 'Farm & City',
                'seed' => 'Farm & City',
                'fertilizer' => 'Farm & City',
                'driving' => 'Easy Go',
                'license course' => 'Easy Go',
                'zimparks' => 'Zimparks',
                'holiday' => 'Zimparks',
                'vacation' => 'Zimparks',
                'building' => 'Gain Hardware',
                'construction' => 'Gain Hardware',
                'hardware' => 'Gain Hardware',
                'cement' => 'Gain Hardware',
                'roofing' => 'Gain Hardware',
            ];

            foreach ($supplierMappings as $keyword => $supplierName) {
                if (str_contains($searchLower, $keyword)) {
                    $supplier = \App\Models\Supplier::where('name', $supplierName)->first();
                    break;
                }
            }
        }

        // Fallback to generic response if still no supplier found
        if (!$supplier) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No specific supplier found for this product type'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'city' => $supplier->city,
                'branches' => $supplier->branches ?? [],
            ]
        ]);
    }
}