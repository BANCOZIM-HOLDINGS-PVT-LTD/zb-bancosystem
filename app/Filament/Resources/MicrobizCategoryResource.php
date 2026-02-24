<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MicrobizCategoryResource\Pages;
use App\Models\MicrobizCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MicrobizCategoryResource extends BaseResource
{
    protected static ?string $model = MicrobizCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'MicroBiz Categories';

    protected static ?string $navigationGroup = 'MicroBiz';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'microbiz-categories';

    protected static ?string $modelLabel = 'Business Category';

    protected static ?string $pluralModelLabel = 'Business Categories';

    /**
     * Only show microbiz domain categories.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('domain', 'microbiz');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('emoji')
                                    ->label('Emoji')
                                    ->required()
                                    ->maxLength(10)
                                    ->default('📦')
                                    ->placeholder('e.g., 🐔'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Category Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., Chicken Projects')
                                    ->columnSpan(2),
                            ]),
                        Forms\Components\Hidden::make('domain')
                            ->default('microbiz'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emoji')
                    ->label('')
                    ->width('40px')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('subcategories_count')
                    ->counts('subcategories')
                    ->label('Businesses')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (MicrobizCategory $record) {
                        // Prevent deletion if category has subcategories
                        if ($record->subcategories()->count() > 0) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot delete')
                                ->body("This category has {$record->subcategories()->count()} business subcategories. Remove them first.")
                                ->send();
                            throw new \Exception('Cannot delete category with subcategories.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMicrobizCategories::route('/'),
            'create' => Pages\CreateMicrobizCategory::route('/create'),
            'edit' => Pages\EditMicrobizCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
}
