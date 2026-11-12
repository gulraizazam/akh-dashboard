<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrandApiService
{
    public function brands(): array
    {
        return config('brands.items', []);
    }

    public function brand(string $key): ?array
    {
        return config("brands.items.$key");
    }

    public function locations(string $brandKey): array
    {
        $data = $this->call($brandKey, 'locations');
        return is_array($data['data'] ?? null) ? $data['data'] : [];
    }

    public function stats(string $brandKey, array $params): array
    {
        $data = $this->call($brandKey, 'stats', $params);
        return is_array($data['data'] ?? null) ? $data['data'] : [];
    }

    public function aggregateStats(?string $brandKey, array $params): array
    {
        $keys = $brandKey ? [$brandKey] : array_keys($this->brands());

        $totals = [
            'total_collection'   => 0,
            'total_consumed'     => 0,
            'total_consultations' => 0,
            'total_treatments'   => 0,
            'total_leads'        => 0,
            'total_plans'        => 0,
        ];

        $collectionByBranch = [];
        $consumedByBranch   = [];
        $collectionByCategory = [];
        $consumedByCategory = [];
        $collectionByService = [];
        $brandBreakdown     = [];

        foreach ($keys as $key) {
            $data = $this->stats($key, $params);
            if (! $data) {
                continue;
            }

            $totals['total_collection']    += $data['totals']['collection']    ?? 0;
            $totals['total_consumed']      += $data['totals']['consumed']      ?? 0;
            $totals['total_consultations'] += $data['totals']['consultations'] ?? 0;
            $totals['total_treatments']    += $data['totals']['treatments']    ?? 0;
            $totals['total_leads']         += $data['totals']['leads']         ?? 0;
            $totals['total_plans']         += $data['totals']['plans']         ?? 0;

            $label = $this->brand($key)['label'] ?? $key;

            $brandBreakdown[] = [
                'brand'      => $label,
                'collection' => $data['totals']['collection'] ?? 0,
                'consumed'   => $data['totals']['consumed']   ?? 0,
            ];

            foreach (($data['collection_by_branch'] ?? []) as $row) {
                $collectionByBranch[] = [
                    'brand'  => $label,
                    'branch' => $row['name']  ?? 'Unknown',
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            }

            foreach (($data['consumed_by_branch'] ?? []) as $row) {
                $consumedByBranch[] = [
                    'brand'  => $label,
                    'branch' => $row['name']  ?? 'Unknown',
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            }

            foreach (($data['collection_by_category'] ?? []) as $row) {
                $collectionByCategory[] = [
                    'brand'    => $label,
                    'category' => $row['name']  ?? 'Unknown',
                    'amount'   => (float) ($row['amount'] ?? 0),
                ];
            }

            foreach (($data['consumed_by_category'] ?? []) as $row) {
                $consumedByCategory[] = [
                    'brand'    => $label,
                    'category' => $row['name']  ?? 'Unknown',
                    'amount'   => (float) ($row['amount'] ?? 0),
                ];
            }

            foreach (($data['collection_by_service'] ?? []) as $row) {
                $collectionByService[] = [
                    'brand'   => $label,
                    'service' => $row['name']  ?? 'Unknown',
                    'amount'  => (float) ($row['amount'] ?? 0),
                ];
            }
        }

        return [
            'totals'                 => $totals,
            'brand_breakdown'        => $brandBreakdown,
            'collection_by_branch'   => $collectionByBranch,
            'consumed_by_branch'     => $consumedByBranch,
            'collection_by_category' => $collectionByCategory,
            'consumed_by_category'   => $consumedByCategory,
            'collection_by_service'  => $collectionByService,
        ];
    }

    protected function call(string $brandKey, string $endpoint, array $params = []): array
    {
        $brand = $this->brand($brandKey);
        if (! $brand || empty($brand['url'])) {
            return [];
        }

        $url = rtrim($brand['url'], '/') . '/api/dashboard/' . ltrim($endpoint, '/');

        try {
            $response = $this->client($brand)->get($url, $params);
            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning('Brand API returned non-2xx', [
                'brand'  => $brandKey,
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Brand API call failed', [
                'brand' => $brandKey,
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    protected function client(array $brand): PendingRequest
    {
        $client = Http::acceptJson()
            ->timeout(config('brands.timeout', 30));

        if (! empty($brand['token'])) {
            $client = $client->withToken($brand['token']);
        }

        return $client;
    }
}
