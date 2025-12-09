<?php

namespace App\Filament\Accounting\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\CashPurchase;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;

class Cashbook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static string $view = 'filament.accounting.pages.cashbook';

    protected static ?string $navigationLabel = 'Cashbook Ledger';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // We need a union query for the ledger.
                // However, Filament Tables works best with Eloquent Builders.
                // A common pattern is to query one model and union others, or use specific rows.
                // For simplicity/robustness in Filament, we usually use a "Ledger" model or View.
                // Given the constraints, we will query Sales and try to format unrelated models? 
                // OR better: Create a Rows sequence manually.
                
                // Let's rely on `Sale` as the base query for now, but really we want a collection.
                // Filament supports array data if we use `make($rows)`.
                // But for pagination/filtering, a query is best.
                
                // HACK: We will use a `CashbookEntry` model if we had one.
                // Alternative: We just show Sales here for "Cash In" and have a separate tab for "Cash Out"?
                // User asked for "Cashbook".
                
                // Let's try to fetch all data and return as array.
                // Note: Pagination might be tricky.
                
                Sale::query() // Placeholder, we will override rows below if possible or use a trick.
            )
            ->columns([
                TextColumn::make('date')->label('Date')->date()->sortable(),
                TextColumn::make('description')->label('Description')->searchable(),
                TextColumn::make('debit')->label('Debit (Out)')->money('USD')->color('danger'),
                TextColumn::make('credit')->label('Credit (In)')->money('USD')->color('success'),
                TextColumn::make('balance')->label('Balance (Running)')->money('USD'),
            ]);
    }
    
    // Custom method to fetch ledger data
    public function getViewData(): array
    {
        $sales = Sale::all()->map(fn($s) => [
            'date' => $s->sale_date,
            'description' => "Sale: {$s->product->name} (x{$s->quantity})",
            'credit' => $s->total_amount,
            'debit' => 0,
            'created_at' => $s->created_at,
        ]);
        
        $expenses = Expense::all()->map(fn($e) => [
            'date' => $e->expense_date,
            'description' => "Expense: {$e->title}",
            'credit' => 0,
            'debit' => $e->amount,
            'created_at' => $e->created_at,
        ]);
        
        // Cash Purchases (assuming paid in cash)
        $purchases = CashPurchase::where('payment_status', 'completed')->get()->map(fn($p) => [
            'date' => $p->created_at, // assuming created_date is purchase date
            'description' => "Purchase: {$p->product_name}",
            'credit' => 0,
            'debit' => $p->amount_paid,
            'created_at' => $p->created_at,
        ]);
        
        $entries = $sales->concat($expenses)->concat($purchases)->sortBy('date');
        
        // Calculate running balance
        $balance = 0;
        $ledger = $entries->map(function($entry) use (&$balance) {
            $balance += ($entry['credit'] - $entry['debit']);
            $entry['balance'] = $balance;
            return $entry;
        });
        
        return [
            'ledger' => $ledger,
            'total_in' => $entries->sum('credit'),
            'total_out' => $entries->sum('debit'),
            'net_balance' => $balance,
        ];
    }
}
