<?php
namespace App\Filament\Resources\SchoolItemResource\Pages;
use App\Filament\Resources\SchoolItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSchoolItem extends EditRecord {
    protected static string $resource = SchoolItemResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
