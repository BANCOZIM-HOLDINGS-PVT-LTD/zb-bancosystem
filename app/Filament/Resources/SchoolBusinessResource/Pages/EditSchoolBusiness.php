<?php
namespace App\Filament\Resources\SchoolBusinessResource\Pages;
use App\Filament\Resources\SchoolBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSchoolBusiness extends EditRecord {
    protected static string $resource = SchoolBusinessResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
