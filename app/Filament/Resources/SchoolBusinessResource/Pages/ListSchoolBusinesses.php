<?php
namespace App\Filament\Resources\SchoolBusinessResource\Pages;
use App\Filament\Resources\SchoolBusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSchoolBusinesses extends ListRecords {
    protected static string $resource = SchoolBusinessResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
