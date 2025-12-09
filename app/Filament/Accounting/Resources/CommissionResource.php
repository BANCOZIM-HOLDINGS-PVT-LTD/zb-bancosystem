<?php

namespace App\Filament\Accounting\Resources;

use App\Filament\Accounting\Resources\CommissionResource\Pages;
use App\Models\Commission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\CommissionResource as BaseCommissionResource;

class CommissionResource extends BaseCommissionResource
{
    // Inherit everything from BaseCommissionResource but override pages
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'view' => Pages\ViewCommission::route('/{record}'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}
