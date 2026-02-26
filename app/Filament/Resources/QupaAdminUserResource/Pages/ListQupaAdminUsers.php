<?php

namespace App\Filament\Resources\QupaAdminUserResource\Pages;

use App\Filament\Resources\QupaAdminUserResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListQupaAdminUsers extends ListRecords
{
    protected static string $resource = QupaAdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
