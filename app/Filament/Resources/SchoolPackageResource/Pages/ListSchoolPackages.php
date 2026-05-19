<?php
namespace App\Filament\Resources\SchoolPackageResource\Pages;
use App\Filament\Resources\SchoolPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSchoolPackages extends ListRecords {
    protected static string $resource = SchoolPackageResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
