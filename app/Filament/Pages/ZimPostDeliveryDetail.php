<?php

namespace App\Filament\Pages;

use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use App\Services\ZimPost\Exceptions\ZimPostApiException;
use App\Services\ZimPost\ZimPostService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ZimPostDeliveryDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $title = 'ZimPost Delivery';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.zimpost-delivery-detail';

    public ?string $deliveryId = null;
    public array $delivery = [];
    public ?ApplicationState $localApplication = null;
    public ?DeliveryTracking $localTracking = null;
    public array $error = [];

    public function mount(): void
    {
        $this->deliveryId = request()->query('id') ?: request()->query('tracking');
        if (! $this->deliveryId) {
            $this->error = ['message' => 'Missing delivery ID.'];
            return;
        }

        $svc = app(ZimPostService::class);

        try {
            $this->delivery = $svc->getDelivery($this->deliveryId);
        } catch (ZimPostApiException $e) {
            $this->error = [
                'message' => $e->getMessage(),
                'code' => $e->errorCode,
                'hint' => $e->hint,
            ];
            return;
        }

        $reference = $this->delivery['reference'] ?? null;
        if ($reference) {
            $this->localApplication = ApplicationState::query()
                ->where('reference_code', $reference)
                ->first();
            if ($this->localApplication) {
                $this->localTracking = DeliveryTracking::query()
                    ->where('application_state_id', $this->localApplication->id)
                    ->first();
            }
        }

        if (! $this->localTracking && isset($this->delivery['id'])) {
            $this->localTracking = DeliveryTracking::query()
                ->where('zimpost_delivery_id', $this->delivery['id'])
                ->first();
        }
    }

    protected function getHeaderActions(): array
    {
        if (empty($this->delivery) || ! $this->localApplication || ! $this->localTracking) {
            return [];
        }

        $alreadyLinked = ($this->localTracking->zimpost_delivery_id ?? null) === ($this->delivery['id'] ?? null);

        return [
            Action::make('linkLocal')
                ->label($alreadyLinked ? 'Refresh local link' : 'Link to local delivery')
                ->icon('heroicon-o-link')
                ->color($alreadyLinked ? 'gray' : 'primary')
                ->action(function () {
                    $this->localTracking->forceFill([
                        'zimpost_delivery_id' => $this->delivery['id'] ?? null,
                        'zimpost_tracking_number' => $this->delivery['tracking_number'] ?? null,
                        'zimpost_last_synced_at' => now(),
                        'zimpost_snapshot' => $this->delivery,
                    ])->save();

                    Notification::make()
                        ->title('Linked to local delivery')
                        ->success()
                        ->send();
                }),
        ];
    }
}
