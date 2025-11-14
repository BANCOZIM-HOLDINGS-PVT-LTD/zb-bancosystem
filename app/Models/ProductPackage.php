<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPackage extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'package_type',
        'base_price',
        'discounted_price',
        'discount_percentage',
        'minimum_quantity',
        'maximum_quantity',
        'is_bundle',
        'bundle_discount',
        'is_featured',
        'is_active',
        'availability_start',
        'availability_end',
        'terms_and_conditions',
        'package_benefits',
        'package_limitations',
        'custom_pricing_rules',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'minimum_quantity' => 'integer',
        'maximum_quantity' => 'integer',
        'is_bundle' => 'boolean',
        'bundle_discount' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'availability_start' => 'datetime',
        'availability_end' => 'datetime',
        'package_benefits' => 'array',
        'package_limitations' => 'array',
        'custom_pricing_rules' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Package types
     */
    const TYPE_STANDARD = 'standard';

    const TYPE_PREMIUM = 'premium';

    const TYPE_ECONOMY = 'economy';

    const TYPE_BUNDLE = 'bundle';

    const TYPE_CUSTOM = 'custom';

    const TYPE_PROMOTIONAL = 'promotional';

    /**
     * Get the product this package belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get loan terms available for this package
     */
    public function loanTerms(): HasMany
    {
        return $this->hasMany(LoanTerm::class, 'product_id', 'product_id');
    }

    /**
     * Get bundled products (if this is a bundle package)
     */
    public function bundledProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'package_products')
            ->withPivot(['quantity', 'discount_amount', 'is_optional'])
            ->withTimestamps();
    }

    /**
     * Get package variants (different configurations)
     */
    public function variants(): HasMany
    {
        return $this->hasMany(PackageVariant::class);
    }

    /**
     * Calculate effective price based on quantity and rules
     */
    public function calculatePrice(int $quantity = 1, array $customerData = []): array
    {
        $basePrice = $this->base_price;
        $finalPrice = $basePrice;
        $appliedDiscounts = [];

        // Apply quantity-based pricing
        if ($this->custom_pricing_rules) {
            $quantityDiscount = $this->calculateQuantityDiscount($quantity);
            if ($quantityDiscount > 0) {
                $finalPrice -= $quantityDiscount;
                $appliedDiscounts[] = [
                    'type' => 'quantity',
                    'amount' => $quantityDiscount,
                    'description' => "Quantity discount for {$quantity} units",
                ];
            }
        }

        // Apply package discount
        if ($this->discounted_price && $this->discounted_price < $basePrice) {
            $packageDiscount = $basePrice - $this->discounted_price;
            $finalPrice = $this->discounted_price;
            $appliedDiscounts[] = [
                'type' => 'package',
                'amount' => $packageDiscount,
                'description' => 'Package discount',
            ];
        }

        // Apply bundle discount if applicable
        if ($this->is_bundle && $this->bundle_discount > 0) {
            $bundleDiscount = ($finalPrice * $this->bundle_discount) / 100;
            $finalPrice -= $bundleDiscount;
            $appliedDiscounts[] = [
                'type' => 'bundle',
                'amount' => $bundleDiscount,
                'description' => "Bundle discount ({$this->bundle_discount}%)",
            ];
        }

        // Apply customer-specific discounts
        $customerDiscount = $this->calculateCustomerDiscount($customerData);
        if ($customerDiscount > 0) {
            $finalPrice -= $customerDiscount;
            $appliedDiscounts[] = [
                'type' => 'customer',
                'amount' => $customerDiscount,
                'description' => 'Customer-specific discount',
            ];
        }

        $totalDiscount = array_sum(array_column($appliedDiscounts, 'amount'));
        $totalPrice = $finalPrice * $quantity;

        return [
            'base_price' => $basePrice,
            'unit_price' => round($finalPrice, 2),
            'quantity' => $quantity,
            'subtotal' => round($basePrice * $quantity, 2),
            'total_discount' => round($totalDiscount * $quantity, 2),
            'total_price' => round($totalPrice, 2),
            'savings' => round(($basePrice - $finalPrice) * $quantity, 2),
            'applied_discounts' => $appliedDiscounts,
        ];
    }

    /**
     * Calculate quantity-based discount
     */
    private function calculateQuantityDiscount(int $quantity): float
    {
        $rules = $this->custom_pricing_rules['quantity_tiers'] ?? [];

        foreach ($rules as $tier) {
            if ($quantity >= $tier['min_quantity'] &&
                ($tier['max_quantity'] === null || $quantity <= $tier['max_quantity'])) {

                if ($tier['discount_type'] === 'percentage') {
                    return ($this->base_price * $tier['discount_value']) / 100;
                } else {
                    return $tier['discount_value'];
                }
            }
        }

        return 0;
    }

    /**
     * Calculate customer-specific discount
     */
    private function calculateCustomerDiscount(array $customerData): float
    {
        $rules = $this->custom_pricing_rules['customer_rules'] ?? [];

        foreach ($rules as $rule) {
            if ($this->customerMatchesRule($customerData, $rule)) {
                if ($rule['discount_type'] === 'percentage') {
                    return ($this->base_price * $rule['discount_value']) / 100;
                } else {
                    return $rule['discount_value'];
                }
            }
        }

        return 0;
    }

    /**
     * Check if customer matches a pricing rule
     */
    private function customerMatchesRule(array $customerData, array $rule): bool
    {
        foreach ($rule['conditions'] as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            $customerValue = $customerData[$field] ?? null;

            switch ($operator) {
                case 'equals':
                    if ($customerValue !== $value) {
                        return false;
                    }
                    break;
                case 'greater_than':
                    if ($customerValue <= $value) {
                        return false;
                    }
                    break;
                case 'less_than':
                    if ($customerValue >= $value) {
                        return false;
                    }
                    break;
                case 'contains':
                    if (strpos($customerValue, $value) === false) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get bundle contents with pricing
     */
    public function getBundleContentsAttribute(): array
    {
        if (! $this->is_bundle) {
            return [];
        }

        return $this->bundledProducts->map(function ($product) {
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $product->pivot->quantity,
                'base_price' => $product->base_price,
                'discount_amount' => $product->pivot->discount_amount,
                'final_price' => $product->base_price - $product->pivot->discount_amount,
                'is_optional' => $product->pivot->is_optional,
                'subtotal' => ($product->base_price - $product->pivot->discount_amount) * $product->pivot->quantity,
            ];
        })->toArray();
    }

    /**
     * Calculate bundle total value
     */
    public function getBundleTotalValueAttribute(): float
    {
        if (! $this->is_bundle) {
            return $this->base_price;
        }

        return collect($this->bundle_contents)->sum('subtotal');
    }

    /**
     * Check if package is currently available
     */
    public function getIsAvailableAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->availability_start && $now < $this->availability_start) {
            return false;
        }

        if ($this->availability_end && $now > $this->availability_end) {
            return false;
        }

        return true;
    }

    /**
     * Get package type label
     */
    public function getPackageTypeLabelAttribute(): string
    {
        return match ($this->package_type) {
            self::TYPE_STANDARD => 'Standard Package',
            self::TYPE_PREMIUM => 'Premium Package',
            self::TYPE_ECONOMY => 'Economy Package',
            self::TYPE_BUNDLE => 'Bundle Package',
            self::TYPE_CUSTOM => 'Custom Package',
            self::TYPE_PROMOTIONAL => 'Promotional Package',
            default => ucfirst($this->package_type).' Package',
        };
    }

    /**
     * Get package benefits as formatted list
     */
    public function getFormattedBenefitsAttribute(): array
    {
        return $this->package_benefits ?? [];
    }

    /**
     * Get package limitations as formatted list
     */
    public function getFormattedLimitationsAttribute(): array
    {
        return $this->package_limitations ?? [];
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available packages (active and within availability period)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('availability_start')
                    ->orWhere('availability_start', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('availability_end')
                    ->orWhere('availability_end', '>', now());
            });
    }

    /**
     * Scope for featured packages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for bundle packages
     */
    public function scopeBundles($query)
    {
        return $query->where('is_bundle', true);
    }

    /**
     * Scope for packages of specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('package_type', $type);
    }
}
