<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->action(fn () => $this->record->approve(auth()->user()->name ?? 'System'))
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->status === 'pending'),

            Actions\Action::make('mark_ordered')
                ->label('Mark as Ordered')
                ->action(fn () => $this->record->markAsOrdered())
                ->requiresConfirmation()
                ->color('info')
                ->icon('heroicon-o-truck')
                ->visible(fn (): bool => $this->record->status === 'approved'),

            Actions\Action::make('mark_received')
                ->label('Mark as Received')
                ->action(fn () => $this->record->markAsReceived())
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-inbox-in')
                ->visible(fn (): bool => $this->record->status === 'ordered'),

            Actions\Action::make('print')
                ->label('Print PO')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => null), // Will implement PDF generation later
        ];
    }
}
