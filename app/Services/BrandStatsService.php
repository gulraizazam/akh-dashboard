<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reads KPIs and chart series straight out of each brand's own database.
 *
 * Every brand app runs on this same MySQL server, so the dashboard connects
 * directly instead of asking each site over HTTP. Nothing here ever writes:
 * all access goes through Connection::select().
 */
class BrandStatsService
{
    /** Chart series are capped so one brand with hundreds of services can't dominate. */
    protected const TOP_N = 10;

    public function brands(): array
    {
        return config('brands.items', []);
    }

    public function brand(string $key): ?array
    {
        return config("brands.items.$key");
    }

    public function brandKeys(): array
    {
        return array_keys($this->brands());
    }

    public function label(string $key): string
    {
        return $this->brand($key)['label'] ?? $key;
    }

    /**
     * Registers (once) and returns a read-only connection for a brand.
     */
    public function connection(string $key): Connection
    {
        $name = "brand_$key";

        if (! Config::has("database.connections.$name")) {
            $brand  = $this->brand($key) ?? [];
            $shared = config('brands.db');

            Config::set("database.connections.$name", [
                'driver'    => 'mysql',
                'host'      => $shared['host'],
                'port'      => $shared['port'],
                'database'  => $brand['database'] ?? '',
                'username'  => $brand['username'] ?: $shared['username'],
                'password'  => $brand['password'] ?: $shared['password'],
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
                'engine'    => null,
            ]);
        }

        return DB::connection($name);
    }

    /**
     * Real centres for a brand, with the aggregate pseudo-rows removed.
     */
    public function locations(string $key): array
    {
        return $this->guard($key, function () use ($key) {
            $excluded = config('brands.excluded_locations', []);
            $rows = $this->connection($key)->select(
                'SELECT id, name FROM locations
                  WHERE active = 1 AND deleted_at IS NULL
                    AND name NOT IN (' . $this->placeholders($excluded) . ')
                  ORDER BY COALESCE(sort_no, id), id',
                $excluded
            );

            return array_map(fn($r) => ['id' => (int) $r->id, 'name' => $r->name], $rows);
        }, []);
    }

    /**
     * The six headline numbers for one brand.
     */
    public function totals(string $key, string $from, string $to, ?int $locationId = null): array
    {
        return $this->guard($key, function () use ($key, $from, $to, $locationId) {
            $db      = $this->connection($key);
            $consult = config('brands.appointment_type_consult');
            $service = config('brands.appointment_type_service');

            return [
                'collection'    => (float) $this->scalar($db, $this->collectionSql('', $locationId), $this->collectionBindings($from, $to, $locationId)),
                'consumed'      => (float) $this->scalar($db, $this->consumedSql($locationId), $this->consumedBindings($from, $to, $locationId)),

                'consultations'          => (int) $this->scalar($db, $this->appointmentCountSql($locationId),       $this->appointmentBindings($consult, $from, $to, $locationId)),
                'consultations_arrived'  => (int) $this->scalar($db, $this->appointmentCountSql($locationId, true), $this->appointmentBindings($consult, $from, $to, $locationId, true)),
                'treatments'             => (int) $this->scalar($db, $this->appointmentCountSql($locationId),       $this->appointmentBindings($service, $from, $to, $locationId)),
                'treatments_arrived'     => (int) $this->scalar($db, $this->appointmentCountSql($locationId, true), $this->appointmentBindings($service, $from, $to, $locationId, true)),

                'leads'         => (int) $this->scalar($db, $this->leadsSql($locationId), $this->leadsBindings($from, $to, $locationId)),
                'plans'         => (int) $this->scalar($db, $this->plansSql($locationId), $this->plansBindings($from, $to, $locationId)),
            ];
        }, [
            'collection' => 0.0, 'consumed' => 0.0,
            'consultations' => 0, 'consultations_arrived' => 0,
            'treatments' => 0, 'treatments_arrived' => 0,
            'leads' => 0, 'plans' => 0,
        ]);
    }

    /**
     * Aggregates one brand, or every brand when $brandKey is null.
     */
    public function aggregate(?string $brandKey, string $from, string $to, ?int $locationId = null): array
    {
        $keys = $brandKey ? [$brandKey] : $this->brandKeys();

        $totals = [
            'total_collection' => 0.0, 'total_consumed' => 0.0,
            'total_consultations' => 0, 'total_consultations_arrived' => 0,
            'total_treatments' => 0, 'total_treatments_arrived' => 0,
            'total_leads' => 0, 'total_plans' => 0,
        ];

        $brandBreakdown       = [];
        $collectionByBranch   = [];
        $consumedByBranch     = [];
        $collectionByCategory = [];
        $consumedByCategory   = [];
        $consumedByService    = [];
        $branchArrivals       = [];
        $doctorConversion     = [];
        $unreachable          = [];
        $trend                = [];

        foreach ($keys as $key) {
            $label = $this->label($key);

            if (! $this->isConfigured($key)) {
                $unreachable[] = $label;
                continue;
            }

            // A location id only means something within its own brand.
            $loc = $brandKey ? $locationId : null;

            $t = $this->totals($key, $from, $to, $loc);

            $totals['total_collection']            += $t['collection'];
            $totals['total_consumed']              += $t['consumed'];
            $totals['total_consultations']         += $t['consultations'];
            $totals['total_consultations_arrived'] += $t['consultations_arrived'];
            $totals['total_treatments']            += $t['treatments'];
            $totals['total_treatments_arrived']    += $t['treatments_arrived'];
            $totals['total_leads']                 += $t['leads'];
            $totals['total_plans']                 += $t['plans'];

            $brandBreakdown[] = [
                'brand'      => $label,
                'collection' => $t['collection'],
                'consumed'   => $t['consumed'],
            ];

            foreach ($this->collectionByBranch($key, $from, $to, $loc) as $r) {
                $collectionByBranch[] = ['brand' => $label, 'branch' => $r['name'], 'amount' => $r['amount']];
            }
            foreach ($this->consumedByBranch($key, $from, $to, $loc) as $r) {
                $consumedByBranch[] = ['brand' => $label, 'branch' => $r['name'], 'amount' => $r['amount']];
            }
            foreach ($this->collectionByCategory($key, $from, $to, $loc) as $r) {
                $collectionByCategory[] = ['brand' => $label, 'category' => $r['name'], 'amount' => $r['amount']];
            }
            foreach ($this->consumedByCategory($key, $from, $to, $loc) as $r) {
                $consumedByCategory[] = ['brand' => $label, 'category' => $r['name'], 'amount' => $r['amount']];
            }
            foreach ($this->consumedByService($key, $from, $to, $loc) as $r) {
                $consumedByService[] = ['brand' => $label, 'service' => $r['name'], 'amount' => $r['amount']];
            }

            foreach ($this->branchArrivals($key, $from, $to, $loc) as $r) {
                $branchArrivals[] = $r + ['brand' => $label];
            }
            foreach ($this->doctorConversion($key, $from, $to, $loc) as $r) {
                $doctorConversion[] = $r + ['brand' => $label];
            }

            foreach ($this->collectionTrend($key, $from, $to, $loc) as $date => $amount) {
                $trend[$date] = ($trend[$date] ?? 0) + $amount;
            }
        }

        return [
            'totals'                 => $totals,
            'brand_breakdown'        => $brandBreakdown,
            'collection_by_branch'   => $collectionByBranch,
            'consumed_by_branch'     => $consumedByBranch,
            'collection_by_category' => $collectionByCategory,
            'consumed_by_category'   => $consumedByCategory,
            'consumed_by_service'    => $consumedByService,
            'branch_arrivals'        => $branchArrivals,
            'doctor_conversion'      => $doctorConversion,
            'trend'                  => $this->fillTrendGaps($trend, $from, $to),
            'unreachable'            => $unreachable,
        ];
    }

    /**
     * A day with no collection must plot as zero, not vanish — otherwise the
     * line implies activity on dates that had none.
     *
     * The series stops at today: "this month" runs to the 30th, but plotting
     * dates that haven't happened yet draws a cliff to zero that reads as a
     * collapse in takings rather than as the month simply not being over.
     */
    protected function fillTrendGaps(array $trend, string $from, string $to): array
    {
        $out    = [];
        $cursor = \Carbon\Carbon::parse($from)->startOfDay();
        $end    = \Carbon\Carbon::parse($to)->startOfDay();
        $today  = \Carbon\Carbon::now()->startOfDay();

        if ($end->greaterThan($today)) {
            $end = $today;
        }

        // Guard against an inverted or absurd range producing a runaway loop.
        if ($cursor->greaterThan($end) || $cursor->diffInDays($end) > 400) {
            ksort($trend);

            return array_map(
                fn($d, $v) => ['date' => $d, 'amount' => $v],
                array_keys($trend),
                array_values($trend)
            );
        }

        while ($cursor->lessThanOrEqualTo($end)) {
            $d = $cursor->toDateString();
            $out[] = ['date' => $d, 'amount' => $trend[$d] ?? 0];
            $cursor->addDay();
        }

        return $out;
    }

    // ---------------------------------------------------------------- charts

    public function collectionByBranch(string $key, string $from, string $to, ?int $locationId): array
    {
        $excluded = config('brands.excluded_locations', []);

        return $this->series($key,
            "SELECT l.name AS name,
                    COALESCE(SUM(CASE WHEN pa.cash_flow = 'in' THEN pa.cash_amount ELSE -pa.cash_amount END), 0) AS amount
               FROM package_advances pa
               JOIN locations l ON l.id = pa.location_id
              WHERE pa.account_id = ? AND pa.is_adjustment = 0 AND pa.is_tax = 0 AND pa.is_cancel = 0
                AND (pa.cash_flow = 'in' OR (pa.cash_flow = 'out' AND pa.is_refund = 1))
                AND DATE(pa.created_at) BETWEEN ? AND ?
                AND l.name NOT IN (" . $this->placeholders($excluded) . ")"
                . ($locationId ? ' AND pa.location_id = ?' : '') .
            " GROUP BY l.id, l.name
              HAVING amount <> 0
              ORDER BY amount DESC",
            array_merge([config('brands.account_id'), $from, $to], $excluded, $locationId ? [$locationId] : [])
        );
    }

    public function consumedByBranch(string $key, string $from, string $to, ?int $locationId): array
    {
        $excluded = config('brands.excluded_locations', []);

        return $this->series($key,
            'SELECT l.name AS name, COALESCE(SUM(i.total_price), 0) AS amount
               FROM invoices i
               JOIN locations l ON l.id = i.location_id
              WHERE i.invoice_status_id = ?
                AND DATE(i.created_at) BETWEEN ? AND ?
                AND l.name NOT IN (' . $this->placeholders($excluded) . ')'
                . ($locationId ? ' AND i.location_id = ?' : '') .
            ' GROUP BY l.id, l.name
              HAVING amount <> 0
              ORDER BY amount DESC',
            array_merge([config('brands.invoice_status_paid'), $from, $to], $excluded, $locationId ? [$locationId] : [])
        );
    }

    public function collectionByCategory(string $key, string $from, string $to, ?int $locationId): array
    {
        return $this->series($key,
            "SELECT COALESCE(cat.name, s.name) AS name,
                    COALESCE(SUM(CASE WHEN pa.cash_flow = 'in' THEN pa.cash_amount ELSE -pa.cash_amount END), 0) AS amount
               FROM package_advances pa
               JOIN appointments a ON a.id = pa.appointment_id
               JOIN services s ON s.id = a.service_id
               LEFT JOIN services cat ON cat.id = s.parent_id AND s.parent_id <> 0
              WHERE pa.account_id = ? AND pa.is_adjustment = 0 AND pa.is_tax = 0 AND pa.is_cancel = 0
                AND (pa.cash_flow = 'in' OR (pa.cash_flow = 'out' AND pa.is_refund = 1))
                AND DATE(pa.created_at) BETWEEN ? AND ?"
                . ($locationId ? ' AND pa.location_id = ?' : '') .
            ' GROUP BY name
              HAVING amount <> 0
              ORDER BY amount DESC
              LIMIT ' . self::TOP_N,
            array_merge([config('brands.account_id'), $from, $to], $locationId ? [$locationId] : [])
        );
    }

    public function consumedByCategory(string $key, string $from, string $to, ?int $locationId): array
    {
        return $this->series($key,
            'SELECT COALESCE(cat.name, s.name) AS name, COALESCE(SUM(d.net_amount), 0) AS amount
               FROM invoice_details d
               JOIN invoices i ON i.id = d.invoice_id
               JOIN services s ON s.id = d.service_id
               LEFT JOIN services cat ON cat.id = s.parent_id AND s.parent_id <> 0
              WHERE i.invoice_status_id = ?
                AND DATE(i.created_at) BETWEEN ? AND ?'
                . ($locationId ? ' AND i.location_id = ?' : '') .
            ' GROUP BY name
              HAVING amount <> 0
              ORDER BY amount DESC
              LIMIT ' . self::TOP_N,
            array_merge([config('brands.invoice_status_paid'), $from, $to], $locationId ? [$locationId] : [])
        );
    }

    /**
     * Daily collection across the period, keyed by date.
     */
    public function collectionTrend(string $key, string $from, string $to, ?int $locationId): array
    {
        $rows = $this->guard($key, function () use ($key, $from, $to, $locationId) {
            return $this->connection($key)->select(
                "SELECT DATE(pa.created_at) AS d,
                        COALESCE(SUM(CASE WHEN pa.cash_flow = 'in' THEN pa.cash_amount ELSE -pa.cash_amount END), 0) AS amount
                   FROM package_advances pa
                  WHERE pa.account_id = ? AND pa.is_adjustment = 0 AND pa.is_tax = 0 AND pa.is_cancel = 0
                    AND (pa.cash_flow = 'in' OR (pa.cash_flow = 'out' AND pa.is_refund = 1))
                    AND DATE(pa.created_at) BETWEEN ? AND ?"
                    . ($locationId ? ' AND pa.location_id = ?' : '') .
                ' GROUP BY d ORDER BY d',
                array_merge([config('brands.account_id'), $from, $to], $locationId ? [$locationId] : [])
            );
        }, []);

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->d] = (float) $r->amount;
        }

        return $out;
    }

    public function consumedByService(string $key, string $from, string $to, ?int $locationId): array
    {
        return $this->series($key,
            'SELECT s.name AS name, COALESCE(SUM(d.net_amount), 0) AS amount
               FROM invoice_details d
               JOIN invoices i ON i.id = d.invoice_id
               JOIN services s ON s.id = d.service_id
              WHERE i.invoice_status_id = ?
                AND DATE(i.created_at) BETWEEN ? AND ?'
                . ($locationId ? ' AND i.location_id = ?' : '') .
            ' GROUP BY s.id, s.name
              HAVING amount <> 0
              ORDER BY amount DESC
              LIMIT ' . self::TOP_N,
            array_merge([config('brands.invoice_status_paid'), $from, $to], $locationId ? [$locationId] : [])
        );
    }

    /**
     * Booked vs arrived per centre, split by consultation and treatment.
     */
    public function branchArrivals(string $key, string $from, string $to, ?int $locationId): array
    {
        $excluded = config('brands.excluded_locations', []);

        return $this->guard($key, function () use ($key, $from, $to, $locationId, $excluded) {
            $rows = $this->connection($key)->select(
                'SELECT l.name AS name,
                        SUM(a.appointment_type_id = ?)                                AS consult_booked,
                        SUM(a.appointment_type_id = ? AND a.appointment_status_id = ?) AS consult_arrived,
                        SUM(a.appointment_type_id = ?)                                AS treat_booked,
                        SUM(a.appointment_type_id = ? AND a.appointment_status_id = ?) AS treat_arrived
                   FROM appointments a
                   JOIN locations l ON l.id = a.location_id
                  WHERE DATE(a.scheduled_date) BETWEEN ? AND ?
                    AND l.name NOT IN (' . $this->placeholders($excluded) . ')'
                    . ($locationId ? ' AND a.location_id = ?' : '') .
                // No ORDER BY: MySQL rejects an aggregate alias used inside an
                // expression there ("reference to group function"), and the
                // caller ranks these rows anyway.
                ' GROUP BY l.id, l.name
                  HAVING consult_booked > 0 OR treat_booked > 0',
                array_merge(
                    [
                        config('brands.appointment_type_consult'),
                        config('brands.appointment_type_consult'), config('brands.appointment_status_arrived'),
                        config('brands.appointment_type_service'),
                        config('brands.appointment_type_service'), config('brands.appointment_status_arrived'),
                        $from, $to,
                    ],
                    $excluded,
                    $locationId ? [$locationId] : []
                )
            );

            return array_map(fn($r) => [
                'name'            => $r->name ?: 'Unknown',
                'consult_booked'  => (int) $r->consult_booked,
                'consult_arrived' => (int) $r->consult_arrived,
                'treat_booked'    => (int) $r->treat_booked,
                'treat_arrived'   => (int) $r->treat_arrived,
            ], $rows);
        }, []);
    }

    /**
     * Per doctor: arrived consultations, how many turned into a payment, and
     * what those payments were worth.
     *
     * "Converted" means an arrived consultation with at least one cash-in
     * advance recorded against it inside the period — the same test the brand
     * apps' own doctor-conversion report applies, expressed as one query.
     */
    public function doctorConversion(string $key, string $from, string $to, ?int $locationId): array
    {
        return $this->guard($key, function () use ($key, $from, $to, $locationId) {
            $rows = $this->connection($key)->select(
                "SELECT u.name AS name,
                        COUNT(DISTINCT a.id)              AS arrived,
                        COUNT(DISTINCT pa.appointment_id) AS converted,
                        COALESCE(SUM(pa.cash_amount), 0)  AS amount
                   FROM appointments a
                   JOIN users u ON u.id = a.doctor_id
                   LEFT JOIN package_advances pa
                          ON pa.appointment_id = a.id
                         AND pa.cash_flow = 'in'
                         AND pa.cash_amount > 0
                         AND pa.is_adjustment = 0 AND pa.is_tax = 0 AND pa.is_cancel = 0
                         AND DATE(pa.created_at) BETWEEN ? AND ?
                  WHERE a.appointment_status_id = ?
                    AND a.appointment_type_id = ?
                    AND DATE(a.scheduled_date) BETWEEN ? AND ?"
                    . ($locationId ? ' AND a.location_id = ?' : '') .
                ' GROUP BY u.id, u.name
                  HAVING arrived > 0
                  ORDER BY arrived DESC
                  LIMIT ' . self::TOP_N,
                array_merge(
                    [
                        $from, $to,
                        config('brands.appointment_status_arrived'),
                        config('brands.appointment_type_consult'),
                        $from, $to,
                    ],
                    $locationId ? [$locationId] : []
                )
            );

            return array_map(fn($r) => [
                'name'      => $r->name ?: 'Unknown',
                'arrived'   => (int) $r->arrived,
                'converted' => (int) $r->converted,
                'amount'    => (float) $r->amount,
            ], $rows);
        }, []);
    }

    // ----------------------------------------------------------- totals SQL

    protected function collectionSql(string $extra, ?int $locationId): string
    {
        return "SELECT COALESCE(SUM(CASE WHEN pa.cash_flow = 'in' THEN pa.cash_amount ELSE -pa.cash_amount END), 0) AS v
                  FROM package_advances pa
                 WHERE pa.account_id = ? AND pa.is_adjustment = 0 AND pa.is_tax = 0 AND pa.is_cancel = 0
                   AND (pa.cash_flow = 'in' OR (pa.cash_flow = 'out' AND pa.is_refund = 1))
                   AND DATE(pa.created_at) BETWEEN ? AND ?"
               . ($locationId ? ' AND pa.location_id = ?' : '') . $extra;
    }

    protected function collectionBindings(string $from, string $to, ?int $locationId): array
    {
        return array_merge([config('brands.account_id'), $from, $to], $locationId ? [$locationId] : []);
    }

    protected function consumedSql(?int $locationId): string
    {
        return 'SELECT COALESCE(SUM(i.total_price), 0) AS v
                  FROM invoices i
                 WHERE i.invoice_status_id = ?
                   AND DATE(i.created_at) BETWEEN ? AND ?'
               . ($locationId ? ' AND i.location_id = ?' : '');
    }

    protected function consumedBindings(string $from, string $to, ?int $locationId): array
    {
        return array_merge([config('brands.invoice_status_paid'), $from, $to], $locationId ? [$locationId] : []);
    }

    protected function appointmentCountSql(?int $locationId, bool $arrivedOnly = false): string
    {
        return 'SELECT COUNT(*) AS v
                  FROM appointments a
                 WHERE a.appointment_type_id = ?
                   AND DATE(a.scheduled_date) BETWEEN ? AND ?'
               . ($arrivedOnly ? ' AND a.appointment_status_id = ?' : '')
               . ($locationId ? ' AND a.location_id = ?' : '');
    }

    protected function appointmentBindings(int $type, string $from, string $to, ?int $locationId, bool $arrivedOnly = false): array
    {
        return array_merge(
            [$type, $from, $to],
            $arrivedOnly ? [config('brands.appointment_status_arrived')] : [],
            $locationId ? [$locationId] : []
        );
    }

    /**
     * Leads carry a city, not a centre, so a location filter resolves through
     * that location's city_id.
     */
    protected function leadsSql(?int $locationId): string
    {
        return 'SELECT COUNT(*) AS v
                  FROM leads l
                  JOIN users u ON u.id = l.patient_id
                 WHERE u.user_type_id = ? AND l.active = 1
                   AND DATE(l.created_at) BETWEEN ? AND ?'
               . ($locationId ? ' AND l.city_id = (SELECT city_id FROM locations WHERE id = ?)' : '');
    }

    protected function leadsBindings(string $from, string $to, ?int $locationId): array
    {
        return array_merge([config('brands.user_type_patient'), $from, $to], $locationId ? [$locationId] : []);
    }

    protected function plansSql(?int $locationId): string
    {
        return 'SELECT COUNT(*) AS v
                  FROM packages p
                 WHERE p.deleted_at IS NULL
                   AND DATE(p.created_at) BETWEEN ? AND ?'
               . ($locationId ? ' AND p.location_id = ?' : '');
    }

    protected function plansBindings(string $from, string $to, ?int $locationId): array
    {
        return array_merge([$from, $to], $locationId ? [$locationId] : []);
    }

    // -------------------------------------------------------------- plumbing

    public function isConfigured(string $key): bool
    {
        $brand  = $this->brand($key) ?? [];
        $shared = config('brands.db');

        return ! empty($brand['database'])
            && ! empty($brand['username'] ?: $shared['username']);
    }

    protected function scalar(Connection $db, string $sql, array $bindings): float|int|string
    {
        $row = $db->select($sql, $bindings);

        return $row[0]->v ?? 0;
    }

    protected function series(string $key, string $sql, array $bindings): array
    {
        return $this->guard($key, function () use ($key, $sql, $bindings) {
            $rows = $this->connection($key)->select($sql, $bindings);

            return array_map(fn($r) => [
                'name'   => $r->name ?: 'Unknown',
                'amount' => (float) $r->amount,
            ], $rows);
        }, []);
    }

    /**
     * A brand whose database is down must not take the whole page with it.
     */
    protected function guard(string $key, callable $fn, mixed $fallback): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('Brand database query failed', [
                'brand' => $key,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    protected function placeholders(array $values): string
    {
        return $values ? implode(',', array_fill(0, count($values), '?')) : "''";
    }
}
