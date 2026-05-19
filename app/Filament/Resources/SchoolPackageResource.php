<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolPackageResource\Pages;
use App\Models\SchoolBusiness;
use App\Models\SchoolItem;
use App\Models\SchoolPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolPackageResource extends BaseResource
{
    protected static ?string $model = SchoolPackage::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'Packages';
    protected static ?string $navigationGroup = 'School Booster';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'School Package';
    protected static ?string $pluralModelLabel = 'School Packages';
    protected static ?string $slug = 'school-packages';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Package Definition')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('school_business_id')
                        ->label('School Type')
                        ->options(fn () => SchoolBusiness::with('category')->get()
                            ->mapWithKeys(fn ($b) => [$b->id => ($b->category->emoji ?? '🏫') . ' ' . $b->category->name . ' → ' . $b->name]))
                        ->searchable()->required()->reactive()
                        ->helperText('The school type this package belongs to'),
                    Forms\Components\Select::make('tier')
                        ->label('Tier')
                        ->options(['essential' => 'Essential', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'premium' => 'Premium'])
                        ->required()->searchable(),
                ]),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Package Name')->required()->maxLength(255)->placeholder('e.g. Essential Primary School Package'),
                    Forms\Components\TextInput::make('price')->label('Package Price ($)')->numeric()->prefix('$')->step(0.01)->required()->helperText('Total package price'),
                ]),
                Forms\Components\Textarea::make('description')->label('Package Description')->rows(3)->placeholder("Describe what's included...")->helperText('Visible to applicants'),
                Forms\Components\FileUpload::make('image_url')->label('Package Image')->image()->directory('school-packages')->visibility('public'),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ]),

            Forms\Components\Section::make('Loan Terms')->description('Financial terms for this package tier.')->schema([
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('deposit')->label('Deposit ($)')->numeric()->prefix('$')->step(0.01)->default(0),
                    Forms\Components\TextInput::make('loan_term')->label('Loan Term (months)')->numeric()->suffix('mo')->default(24),
                    Forms\Components\TextInput::make('interest_rate')->label('Interest Rate (%)')->numeric()->suffix('%')->default(108.00)->helperText('Annual flat rate'),
                    Forms\Components\TextInput::make('monthly_installment')->label('Monthly Installment ($)')->numeric()->prefix('$')->step(0.01)->helperText('Leave blank to auto-calculate'),
                ]),
            ]),

            Forms\Components\Section::make('Package Contents')->description('Add items for this school package.')->schema([
                Forms\Components\Repeater::make('tierItems')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('school_item_id')
                            ->label('Item')
                            ->options(function (callable $get) {
                                $businessId = $get('../../school_business_id');
                                $query = SchoolItem::where('is_active', true)->orderBy('name');
                                if ($businessId) {
                                    $query->where('school_business_id', $businessId);
                                }
                                return $query->get()->mapWithKeys(fn ($i) => [
                                    $i->id => "[{$i->item_code}] {$i->name}" . ($i->unit ? " ({$i->unit})" : '') . " - \${$i->unit_cost}"
                                ]);
                            })
                            ->searchable()->required()->columnSpan(3),
                        Forms\Components\TextInput::make('quantity')->numeric()->default(1)->minValue(1)->required()->columnSpan(1),
                        Forms\Components\Toggle::make('is_delivered')->label('Delivered?')->helperText('Needs delivery')->default(false)->columnSpan(1),
                    ])
                    ->columns(5)->defaultItems(0)->addActionLabel('Add Item to Package')
                    ->reorderable(false)->collapsible()
                    ->itemLabel(function (array $state): ?string {
                        if (!($state['school_item_id'] ?? null)) return null;
                        $item = SchoolItem::find($state['school_item_id']);
                        if (!$item) return null;
                        $qty = $state['quantity'] ?? 1;
                        $delivered = ($state['is_delivered'] ?? false) ? ' 🚚' : '';
                        return "[{$item->item_code}] {$item->name} × {$qty}{$delivered}";
                    }),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')->label('Image')->square(),
                Tables\Columns\TextColumn::make('business.category.name')->label('Category')->sortable()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('business.name')->label('School Type')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('tier')->label('Tier')
                    ->colors(['gray' => 'essential', 'info' => 'intermediate', 'warning' => 'advanced', 'success' => 'premium'])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'essential'    => '📘 Essential',
                        'intermediate' => '📗 Intermediate',
                        'advanced'     => '📙 Advanced',
                        'premium'      => '🏆 Premium',
                        default        => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('name')->label('Package Name')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('price')->label('Price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('loan_term')->label('Term')->suffix(' mo')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tier_items_count')->counts('tierItems')->label('Items')->badge()->color('info'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')->options(['essential' => 'Essential', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'premium' => 'Premium']),
                Tables\Filters\SelectFilter::make('school_business_id')->label('School Type')
                    ->options(SchoolBusiness::with('category')->get()->mapWithKeys(fn ($b) => [$b->id => $b->category->name . ' → ' . $b->name]))->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('business.name');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchoolPackages::route('/'),
            'create' => Pages\CreateSchoolPackage::route('/create'),
            'view'   => Pages\ViewSchoolPackage::route('/{record}'),
            'edit'   => Pages\EditSchoolPackage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try { return (string) static::getModel()::count() ?: null; } catch (\Throwable $e) { return null; }
    }
}
