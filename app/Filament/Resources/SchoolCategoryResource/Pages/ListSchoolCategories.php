<?php
namespace App\Filament\Resources\SchoolCategoryResource\Pages;
use App\Filament\Resources\SchoolCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSchoolCategories extends ListRecords {
    protected static string $resource = SchoolCategoryResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
