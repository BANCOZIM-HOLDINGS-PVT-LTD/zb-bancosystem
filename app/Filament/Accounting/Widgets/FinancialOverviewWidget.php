<?php

namespace App\Filament\Accounting\Widgets;

use App\Models\ApplicationState;
use App\Models\CashPurchase;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Sales: Sum of finalPrice from form_data for approved/completed applications
        // Converting JSON string to number. This might be slow on large datasets.
        $sales = ApplicationState::whereIn('current_step', ['completed', 'approved'])
            ->get()
            ->sum(function ($record) {
                return (float) ($record->form_data['finalPrice'] ?? 0);
            });

        // Purchases: sum of total_amount_paid for completed purchases
        $purchases = CashPurchase::where('payment_status', 'completed')
            ->sum('amount_paid');

        // Expenses
        $expenses = Expense::sum('amount');
        
        // Commissions: sum of paid commissions
        $commissions = \App\Models\Commission::paid()->sum('amount');

        // Cash Sales from Inventory
        $cashSales = \App\Models\Sale::sum('total_amount');

        // Total Revenue (Loans + Cash Sales)
        $totalRevenue = $sales + $cashSales;

        // Profit
        $profit = $totalRevenue - ($purchases + $expenses + $commissions);

        return [
            Stat::make('Loan Sales', '$' . number_format($sales, 2))
                ->description('Approved Applications')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Cash Sales', '$' . number_format($cashSales, 2))
                ->description('Inventory Sales')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            
            Stat::make('Total Purchases', '$' . number_format($purchases, 2))
                ->description('Completed Cash Purchases')
                ->color('danger'),

            Stat::make('Total Expenses', '$' . number_format($expenses, 2))
                ->description('Operational expenses')
                ->color('danger'),
                
            Stat::make('Commissions Paid', '$' . number_format($commissions, 2))
                ->description('Agent commissions')
                ->color('danger'),

            Stat::make('Net Profit', '$' . number_format($profit, 2))
                ->description('Revenue - (Purchases + Expenses + Commissions)')
                ->color($profit >= 0 ? 'success' : 'danger'),
        ];
    }
}
