<?php

namespace App\Http\Controllers;

use App\Services\BrandStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected BrandStatsService $stats) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('dashboard.index', [
            'brands'  => $this->stats->brands(),
            'filters' => $filters,
            'data'    => $this->aggregate($filters),
        ]);
    }

    public function locations(Request $request)
    {
        $brand = $request->string('brand')->toString();

        if ($brand === '' || ! $this->stats->brand($brand)) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $this->stats->locations($brand)]);
    }

    public function stats(Request $request)
    {
        $filters = $this->filters($request);

        return response()->json([
            'filters' => $filters,
            'data'    => $this->aggregate($filters),
        ]);
    }

    protected function aggregate(array $filters): array
    {
        return $this->stats->aggregate(
            $filters['brand'],
            $filters['from'],
            $filters['to'],
            $filters['location'] ? (int) $filters['location'] : null,
        );
    }

    protected function filters(Request $request): array
    {
        $brand = $request->string('brand')->toString();
        if ($brand === '' || ! $this->stats->brand($brand)) {
            $brand = null;
        }

        $period = $request->input('period', 'thismonth');
        [$start, $end] = $this->resolvePeriod($period, $request->input('from'), $request->input('to'));

        return [
            'brand'    => $brand,
            // A centre only makes sense once a brand is picked.
            'location' => $brand && $request->filled('location') ? $request->string('location')->toString() : null,
            'period'   => $period,
            'from'     => $start->toDateString(),
            'to'       => $end->toDateString(),
        ];
    }

    protected function resolvePeriod(string $period, ?string $from, ?string $to): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today'     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'week'      => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'lastmonth' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'custom'    => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay(),
            ],
            default     => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
