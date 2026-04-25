<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoosterCategory;
use App\Models\BoosterBusiness;
use App\Models\BoosterTier;
use App\Models\Product;
use App\Models\LoanTerm;
use Illuminate\Support\Facades\DB;

class BoosterPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed the Booster Categories, Businesses, and Tiers
        $boosterData = [
            [
                'category' => 'Agricultural Machinery',
                'emoji' => '🚜',
                'businesses' => [
                    [
                        'name' => 'Tractors',
                        'description' => 'Expand your farming capabilities with agricultural tractors.',
                        'image_url' => 'https://images.unsplash.com/photo-1592982537447-6f296da1e0cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    ],
                ]
            ],
            [
                'category' => 'Retail Shops',
                'emoji' => '🏪',
                'businesses' => [
                    [
                        'name' => 'Hardware',
                        'description' => 'Stock up your hardware store with construction materials and tools.',
                        'image_url' => 'https://images.unsplash.com/photo-1530124566582-a618bc2615dc?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    ],
                ]
            ],
            [
                'category' => 'Beauty, Hair and Cosmetics',
                'emoji' => '💇‍♀️',
                'businesses' => [
                    [
                        'name' => 'Saloon equipment',
                        'description' => 'Upgrade your salon with professional chairs, dryers, and supplies.',
                        'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    ],
                ]
            ],
            [
                'category' => 'Chicken Projects',
                'emoji' => '🐔',
                'businesses' => [
                    [
                        'name' => 'Broiler Production',
                        'description' => 'Scale your poultry farming business with commercial broiler setups.',
                        'image_url' => 'https://images.unsplash.com/photo-1548550023-2bf3c49b338c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    ],
                ]
            ]
        ];

        $tiers = [
            ['name' => 'Starter Tier', 'amount' => 2500],
            ['name' => 'Growth Tier', 'amount' => 5000],
            ['name' => 'Premium Tier', 'amount' => 7500],
            ['name' => 'Elite Tier', 'amount' => 10000],
        ];

        DB::transaction(function () use ($boosterData, $tiers) {
            foreach ($boosterData as $catData) {
                $category = BoosterCategory::firstOrCreate(
                    ['name' => $catData['category']],
                    ['emoji' => $catData['emoji']]
                );

                foreach ($catData['businesses'] as $bizData) {
                    $business = BoosterBusiness::firstOrCreate(
                        [
                            'booster_category_id' => $category->id,
                            'name' => $bizData['name']
                        ],
                        [
                            'description' => $bizData['description'],
                            'image_url' => $bizData['image_url']
                        ]
                    );

                    // Only seed tiers if they don't exist yet for this business
                    if ($business->tiers()->count() === 0) {
                        foreach ($tiers as $tierData) {
                            BoosterTier::create([
                                'booster_business_id' => $business->id,
                                'name' => $tierData['name'],
                                'amount' => $tierData['amount'],
                                'description' => "The {$tierData['name']} provides {$tierData['amount']} worth of supplies and equipment to boost your business.",
                            ]);
                        }
                    }
                }
            }

            // 2. Seed the Loan Product and Loan Term for SME Business Booster
            $product = Product::firstOrCreate(
                ['product_code' => 'SME-BOOSTER'],
                [
                    'name' => 'SME Business Booster',
                    'description' => 'A structured booster package to scale up Small and Medium Enterprises.',
                    'product_sub_category_id' => 1,
                    'base_price' => 0, // Placeholder
                    'selling_price' => 0, // Placeholder
                ]
            );

            // Create the Loan Term with 108% annual rate (9% flat monthly)
            LoanTerm::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => 'Booster Package Terms',
                ],
                [
                    'description' => 'SME Booster package standard term.',
                    'duration_months' => 24, // Assuming 24 months, can be changed
                    'interest_rate' => 108.00, // 9% monthly * 12
                    'interest_type' => 'flat', // Crucial for straight-line calculation
                    'calculation_method' => 'standard',
                    'payment_frequency' => 'monthly',
                    'minimum_amount' => 1000,
                    'maximum_amount' => 50000,
                    'processing_fee' => 5.00, // Example 5%
                    'processing_fee_type' => 'percentage',
                    'insurance_rate' => 0.00,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );
        });
    }
}
