<?php
namespace App\Filament\Resources\SchoolItemResource\Pages;
use App\Filament\Resources\SchoolItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSchoolItems extends ListRecords {
    protected static string $resource = SchoolItemResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
