<?php

namespace App\Services\ZimPost;

use App\Services\ZimPost\Exceptions\ZimPostApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ZimPostService
{
    public function __construct(protected ZimPostClient $client)
    {
    }

    public function getProfile(): array
    {
        return Cache::remember(
            'zimpost.profile',
            (int) config('services.zimpost.cache_ttl_profile', 300),
            fn () => $this->client->get('profile'),
        );
    }

    public function listDeliveries(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = array_filter(array_merge($filters, [
            'limit' => $limit,
            'offset' => $offset,
        ]), fn ($v) => $v !== null && $v !== '');

        $cacheKey = 'zimpost.deliveries.' . md5(json_encode($query));

        return Cache::remember(
            $cacheKey,
            (int) config('services.zimpost.cache_ttl_list', 30),
            fn () => $this->client->get('deliveries', $query),
        );
    }

    public static function items(array $listResponse): array
    {
        return $listResponse['deliveries'] ?? $listResponse['data'] ?? $listResponse['items'] ?? [];
    }

    public static function total(array $listResponse): int
    {
        return (int) ($listResponse['total'] ?? $listResponse['meta']['total'] ?? count(self::items($listResponse)));
    }

    public function getDelivery(string $idOrTrackingNumber): array
    {
        $key = 'zimpost.delivery.' . md5($idOrTrackingNumber);

        return Cache::remember(
            $key,
            (int) config('services.zimpost.cache_ttl_detail', 15),
            fn () => $this->client->get('delivery/' . rawurlencode($idOrTrackingNumber)),
        );
    }

    /**
     * Try to find a ZimPost delivery whose partner-side `reference` matches the given code.
     * Pages through the partner deliveries list until found, or returns null after a bounded sweep.
     */
    public function findByReference(string $reference, int $maxSweep = 200): ?array
    {
        $normalised = $this->normaliseReference($reference);
        if ($normalised === '') {
            return null;
        }

        $cacheKey = 'zimpost.findByReference.' . md5($normalised);
        $ttl = (int) config('services.zimpost.cache_ttl_list', 30);

        return Cache::remember($cacheKey, $ttl, function () use ($normalised, $maxSweep, $reference) {
            $limit = 50;
            $offset = 0;
            $seen = 0;

            do {
                try {
                    $resp = $this->listDeliveries(['reference' => $reference], $limit, $offset);
                } catch (ZimPostApiException $e) {
                    $resp = $this->listDeliveries([], $limit, $offset);
                }

                $items = self::items($resp);
                foreach ($items as $item) {
                    if ($this->normaliseReference($item['reference'] ?? '') === $normalised) {
                        return $item;
                    }
                }

                $seen += count($items);
                $total = self::total($resp);
                $offset += $limit;

                if (count($items) < $limit || $seen >= $total || $seen >= $maxSweep) {
                    break;
                }
            } while (true);

            return null;
        });
    }

    public function normaliseReference(?string $reference): string
    {
        if ($reference === null) {
            return '';
        }
        return strtoupper(preg_replace('/[\s\-_]/', '', trim($reference)));
    }

    /**
     * Returns true if the given tracking number looks like a ZimPost-issued one (ZP-YYYYMMDD-XXX).
     */
    public function looksLikeZimPostTracking(?string $tracking): bool
    {
        if (! $tracking) {
            return false;
        }
        return (bool) preg_match('/^ZP-\d{6,}-[A-Z0-9]+$/i', trim($tracking));
    }
}
