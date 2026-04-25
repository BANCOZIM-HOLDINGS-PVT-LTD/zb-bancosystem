<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoosterCategory;
use App\Models\BoosterBusiness;
use Illuminate\Http\Request;

class BoosterController extends Controller
{
    /**
     * Get the fully nested catalog for the frontend wizard.
     * Maps BoosterCategory -> Category, BoosterBusiness -> Subcategory, BoosterTier -> BusinessType/Scales
     */
    public function getFrontendCatalog()
    {
        try {
            $categories = BoosterCategory::with(['businesses.tiers' => function ($query) {
                $query->orderBy('amount');
            }])->orderBy('name')->get();

            // Map to the format expected by the frontend ProductSelection component
            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id' => 'booster_' . $category->id,
                    'name' => $category->name,
                    'emoji' => $category->emoji ?? '💼',
                    'subcategories' => $category->businesses->map(function ($business) {
                        return [
                            'name' => $business->name,
                            'businesses' => [
                                [
                                    'id' => $business->id,
                                    'name' => $business->name, // ProductSelection uses this as the business name
                                    'description' => $business->description,
                                    'image_url' => $business->image_url,
                                    'basePrice' => 0, // Using scales for actual prices
                                    'scales' => $business->tiers->map(function ($tier) {
                                        return [
                                            'id' => $tier->id,
                                            'name' => $tier->name,
                                            'multiplier' => 1,
                                            'custom_price' => $tier->amount,
                                            'description' => $tier->description,
                                        ];
                                    })->values()->all(),
                                ]
                            ]
                        ];
                    })->values()->all(),
                ];
            });

            return response()->json($formattedCategories);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch booster catalog: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Get all booster categories.
     */
    public function getCategories()
    {
        try {
            $categories = BoosterCategory::withCount('businesses')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch booster categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load booster categories'
            ], 500);
        }
    }

    /**
     * Get businesses for a specific booster category.
     */
    public function getBusinessesByCategory($categoryId)
    {
        try {
            $businesses = BoosterBusiness::where('booster_category_id', $categoryId)
                ->with('tiers')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $businesses
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch booster businesses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load booster businesses'
            ], 500);
        }
    }

    /**
     * Get a specific business with its tiers.
     */
    public function getBusiness($businessId)
    {
        try {
            $business = BoosterBusiness::with(['category', 'tiers' => function ($query) {
                $query->orderBy('amount');
            }])->findOrFail($businessId);

            return response()->json([
                'success' => true,
                'data' => $business
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch booster business: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load booster business details'
            ], 500);
        }
    }
}
