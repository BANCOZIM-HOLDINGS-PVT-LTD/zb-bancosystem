<?php
namespace App\Filament\Resources\SchoolPackageResource\Pages;
use App\Filament\Resources\SchoolPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSchoolPackage extends EditRecord {
    protected static string $resource = SchoolPackageResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make(), Actions\DeleteAction::make()]; }
}
