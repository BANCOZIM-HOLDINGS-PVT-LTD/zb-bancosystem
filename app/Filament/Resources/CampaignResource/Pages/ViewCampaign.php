<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Campaign Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('start_date')
                            ->date('M j, Y'),
                        Infolists\Components\TextEntry::make('end_date')
                            ->date('M j, Y'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'completed' => 'gray',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Performance Targets')
                    ->schema([
                        Infolists\Components\TextEntry::make('target_applications')
                            ->label('Target Applications'),
                        Infolists\Components\TextEntry::make('target_sales')
                            ->money('USD')
                            ->label('Target Sales'),
                        Infolists\Components\TextEntry::make('target_conversions')
                            ->label('Target Conversions'),
                    ])
                    ->columns(3),
                    
                Infolists\Components\Section::make('Assigned Agents & Performance')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('agents')
                            ->schema([
                                Infolists\Components\TextEntry::make('display_name')
                                    ->label('Agent'),
                                Infolists\Components\TextEntry::make('pivot.applications_count')
                                    ->label('Applications')
                                    ->badge()
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('pivot.sales_total')
                                    ->label('Sales')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('pivot.conversions_count')
                                    ->label('Conversions')
                                    ->badge()
                                    ->color('info'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
