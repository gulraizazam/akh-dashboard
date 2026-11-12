<?php

namespace App\Http\Controllers;

use App\Services\BrandApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected BrandApiService $api) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $data    = $this->api->aggregateStats($filters['brand'], $this->apiParams($filters));

        return view('dashboard.index', [
            'brands'  => $this->api->brands(),
            'filters' => $filters,
            'data'    => $data,
        ]);
    }

    public function locations(Request $request)
    {
        $brand = $request->string('brand')->toString();
        if ($brand === '') {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->api->locations($brand),
        ]);
    }

    public function stats(Request $request)
    {
        $filters = $this->filters($request);
        return response()->json([
            'filters' => $filters,
            'data'    => $this->api->aggregateStats($filters['brand'], $this->apiParams($filters)),
        ]);
    }

    protected function filters(Request $request): array
    {
        $period = $request->input('period', 'thismonth');
        [$start, $end] = $this->resolvePeriod($period, $request->input('from'), $request->input('to'));

        return [
            'brand'    => $request->filled('brand') ? $request->string('brand')->toString() : null,
            'location' => $request->filled('location') ? $request->string('location')->toString() : null,
            'period'   => $period,
            'from'     => $start->toDateString(),
            'to'       => $end->toDateString(),
        ];
    }

    protected function apiParams(array $filters): array
    {
        return array_filter([
            'from'     => $filters['from'],
            'to'       => $filters['to'],
            'location' => $filters['location'],
        ], fn($v) => $v !== null && $v !== '');
    }

    protected function resolvePeriod(string $period, ?string $from, ?string $to): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'yesterday':
                $y = $now->copy()->subDay();
                return [$y->copy()->startOfDay(), $y->copy()->endOfDay()];
            case 'last7days':
                return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()];
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'lastmonth':
                return [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()];
            case 'custom':
                return [
                    $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                    $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay(),
                ];
            case 'thismonth':
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }
}
