<?php
namespace App\Filament\Resources\SchoolCategoryResource\Pages;
use App\Filament\Resources\SchoolCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSchoolCategory extends EditRecord {
    protected static string $resource = SchoolCategoryResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
