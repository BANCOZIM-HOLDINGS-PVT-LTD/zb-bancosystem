<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ApplicationState;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Product statistics
        $totalProducts = Product::count();
        $totalCategories = ProductCategory::count();
        
        // Application statistics related to products
        $applicationsWithProducts = ApplicationState::whereNotNull('form_data->selectedBusiness')
            ->count();
        
        // Most popular category
        $popularCategory = $this->getMostPopularCategory();
        
        // Price range statistics
        $priceStats = Product::selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price, AVG(base_price) as avg_price')
            ->first();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Available in catalog')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Product Categories', $totalCategories)
                ->description('Business categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success'),

            Stat::make('Product Applications', $applicationsWithProducts)
                ->description('Applications with product selection')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Average Price', '$' . number_format($priceStats->avg_price ?? 0, 2))
                ->description('Range: $' . number_format($priceStats->min_price ?? 0, 2) . ' - $' . number_format($priceStats->max_price ?? 0, 2))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    private function getMostPopularCategory(): ?string
    {
        // This would require tracking which products are most requested
        // For now, return the category with the most products
        $category = ProductCategory::withCount('products')
            ->orderBy('products_count', 'desc')
            ->first();

        return $category ? $category->name : 'N/A';
    }
}
