<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolCategory;
use App\Models\SchoolBusiness;
use Illuminate\Support\Facades\Log;

class SchoolBoosterController extends Controller
{
    /**
     * Nested catalog for the frontend wizard.
     * SchoolCategory → subcategory, SchoolBusiness → business, SchoolPackage → scales
     */
    public function getFrontendCatalog()
    {
        try {
            $categories = SchoolCategory::with([
                'businesses.packages.tierItems.item',
            ])->orderBy('name')->get();

            $formatted = $categories->map(function ($category) {
                return [
                    'id'            => 'school_' . $category->id,
                    'name'          => $category->name,
                    'emoji'         => $category->emoji ?? '🏫',
                    'subcategories' => $category->businesses->map(function ($business) {
                        $scales = $business->packages
                            ->where('is_active', true)
                            ->sortBy('price')
                            ->map(function ($package) {
                                return [
                                    'id'                  => $package->id,
                                    'name'                => $package->name,
                                    'group_name'          => match ($package->tier) {
                                        'essential'    => 'Essential Package',
                                        'intermediate' => 'Intermediate Package',
                                        'advanced'     => 'Advanced Package',
                                        'premium'      => 'Premium Package',
                                        default        => ucfirst($package->tier) . ' Package',
                                    },
                                    'multiplier'          => 1.0,
                                    'custom_price'        => (float) $package->price,
                                    'description'         => $package->description,
                                    'loan_term'           => $package->loan_term,
                                    'deposit'             => (float) $package->deposit,
                                    'monthly_installment' => (float) $package->monthly_installment,
                                    'included_items'      => $package->tierItems->map(fn ($t) => [
                                        'name'     => $t->item?->name,
                                        'quantity' => $t->quantity,
                                        'unit'     => $t->item?->unit,
                                    ])->values()->all(),
                                ];
                            })->values()->all();

                        return [
                            'name'       => $business->name,
                            'businesses' => [[
                                'id'          => $business->id,
                                'name'        => $business->name,
                                'description' => $business->description,
                                'image_url'   => $business->image_url,
                                'basePrice'   => 0,
                                'scales'      => $scales,
                            ]],
                        ];
                    })->values()->all(),
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            Log::error('Failed to fetch school booster catalog: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function getCategories()
    {
        return response()->json([
            'success' => true,
            'data'    => SchoolCategory::withCount('businesses')->orderBy('name')->get(),
        ]);
    }

    public function getBusiness($businessId)
    {
        try {
            $business = SchoolBusiness::with(['category', 'packages' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
                ->findOrFail($businessId);
            return response()->json(['success' => true, 'data' => $business]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }
}
