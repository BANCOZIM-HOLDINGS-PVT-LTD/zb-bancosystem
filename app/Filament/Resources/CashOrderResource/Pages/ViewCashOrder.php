<?php

namespace App\Filament\Resources\CashOrderResource\Pages;

use App\Filament\Resources\CashOrderResource;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCashOrder extends ViewRecord
{
    protected static string $resource = CashOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_invoice')
                ->label('Download Invoice PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () =>
                    $this->record->payments->where('provider', 'cash')->first()?->status === 'paid'
                )
                ->action(function () {
                    /** @var ApplicationState $record */
                    $record = $this->record;

                    try {
                        $pdfGenerator = app(PDFGeneratorService::class);
                        $pdfPath = $pdfGenerator->generateReceiptPDF($record);
                        $filename = "invoice_{$record->reference_code}.pdf";

                        return response()->download(
                            storage_path("app/public/{$pdfPath}"),
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Invoice generation failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
