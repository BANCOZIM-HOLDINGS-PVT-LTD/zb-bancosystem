<?php

namespace App\Filament\Resources\QupaAdminUserResource\Pages;

use App\Filament\Resources\QupaAdminUserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditQupaAdminUser extends EditRecord
{
    protected static string $resource = QupaAdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
