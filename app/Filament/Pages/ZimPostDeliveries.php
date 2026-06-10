<?php

namespace App\Filament\Pages;

use App\Models\ApplicationState;
use App\Services\ZimPost\Exceptions\ZimPostApiException;
use App\Services\ZimPost\ZimPostService;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ZimPostDeliveries extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'ZimPost Deliveries';
    protected static ?string $title = 'ZimPost Courier Deliveries';
    protected static ?string $navigationGroup = 'Deliveries';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.zimpost-deliveries';

    public ?string $status = null;
    public ?string $search = null;
    public int $perPage = 25;
    public int $page = 1;

    public array $error = [];

    protected function getViewData(): array
    {
        $svc = app(ZimPostService::class);

        $filters = array_filter([
            'status' => $this->status,
            'reference' => $this->search,
        ], fn ($v) => $v !== null && $v !== '');

        $offset = max(0, ($this->page - 1) * $this->perPage);

        try {
            $response = $svc->listDeliveries($filters, $this->perPage, $offset);
        } catch (ZimPostApiException $e) {
            $this->error = [
                'message' => $e->getMessage(),
                'code' => $e->errorCode,
                'hint' => $e->hint,
            ];
            $response = ['deliveries' => [], 'total' => 0, 'limit' => $this->perPage, 'offset' => $offset];
        }

        $items = ZimPostService::items($response);
        $total = ZimPostService::total($response);

        $referenceCodes = collect($items)->pluck('reference')->filter()->map(fn ($r) => $svc->normaliseReference($r))->all();
        $localApps = [];
        if (! empty($referenceCodes)) {
            $localApps = ApplicationState::query()
                ->whereIn('reference_code', collect($items)->pluck('reference')->filter()->all())
                ->get()
                ->keyBy(fn ($a) => $svc->normaliseReference($a->reference_code));
        }

        $rows = collect($items)->map(function ($d) use ($localApps, $svc) {
            $refKey = $svc->normaliseReference($d['reference'] ?? null);
            return [
                'id' => $d['id'] ?? null,
                'tracking_number' => $d['tracking_number'] ?? null,
                'reference' => $d['reference'] ?? null,
                'status' => $d['status'] ?? null,
                'amount_usd' => $d['amount_usd'] ?? null,
                'distance_km' => $d['distance_km'] ?? null,
                'vehicle_type' => $d['vehicle_type'] ?? null,
                'created_at' => $d['created_at'] ?? null,
                'local_application' => $refKey ? ($localApps[$refKey] ?? null) : null,
            ];
        })->all();

        $paginator = new LengthAwarePaginator(
            items: $rows,
            total: $total,
            perPage: $this->perPage,
            currentPage: $this->page,
            options: ['path' => request()->url(), 'pageName' => 'page'],
        );
        $paginator->appends(array_filter([
            'status' => $this->status,
            'search' => $this->search,
        ]));

        return [
            'paginator' => $paginator,
            'rows' => $rows,
            'statuses' => ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled'],
            'currentStatus' => $this->status,
            'currentSearch' => $this->search,
            'error' => $this->error,
            'detailRouteName' => static::detailRouteName(),
        ];
    }

    public function mount(): void
    {
        $this->status = request()->query('status') ?: null;
        $this->search = request()->query('search') ?: null;
        $this->page = max(1, (int) request()->query('page', 1));
    }

    protected static function detailRouteName(): string
    {
        return 'filament.admin.pages.zim-post-delivery-detail';
    }
}
