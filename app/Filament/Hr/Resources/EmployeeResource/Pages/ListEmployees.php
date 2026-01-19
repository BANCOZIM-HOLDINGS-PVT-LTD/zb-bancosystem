<?php

namespace App\Filament\Hr\Resources\EmployeeResource\Pages;

use App\Filament\Hr\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('All Records'),
            'employees' => \Filament\Resources\Components\Tab::make('Employees')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('employment_type', ['full_time', 'part_time', 'contract'])),
            'interns' => \Filament\Resources\Components\Tab::make('Interns')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('employment_type', 'intern')),
        ];
    }
}
