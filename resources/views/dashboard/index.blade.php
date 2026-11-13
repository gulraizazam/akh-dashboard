@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Overview</div>
                <h2 class="page-title">Analytics Dashboard</h2>
            </div>
            <div class="col-auto">
                <span class="badge bg-indigo-lt">
                    <i class="ti ti-calendar me-1"></i>
                    {{ $filters['from'] }} &rarr; {{ $filters['to'] }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- ---------- Filters ---------- --}}
        <div class="card filter-card mb-3">
            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-end">

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Brand</label>
                        <select name="brand" id="filterBrand" class="form-select">
                            <option value="">All Brands</option>
                            @foreach ($brands as $key => $brand)
                                <option value="{{ $key }}" @selected($filters['brand'] === $key)>{{ $brand['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Location</label>
                        <select name="location" id="filterLocation" class="form-select" {{ $filters['brand'] ? '' : 'disabled' }}>
                            <option value="">All Locations</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Period</label>
                        <select name="period" id="filterPeriod" class="form-select">
                            @php
                                $periods = [
                                    'today'     => 'Today',
                                    'yesterday' => 'Yesterday',
                                    'last7days' => 'Last 7 Days',
                                    'week'      => 'This Week',
                                    'thismonth' => 'This Month',
                                    'lastmonth' => 'Last Month',
                                    'custom'    => 'Custom',
                                ];
                            @endphp
                            @foreach ($periods as $k => $label)
                                <option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6 custom-date-field" style="display:none;">
                        <label class="form-label">From</label>
                        <input type="date" name="from" id="filterFrom" class="form-control" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-2 col-sm-6 custom-date-field" style="display:none;">
                        <label class="form-label">To</label>
                        <input type="date" name="to" id="filterTo" class="form-control" value="{{ $filters['to'] }}">
                    </div>

                    <div class="col-md-2 col-sm-6 ms-auto">
                        <button type="submit" class="btn btn-primary w-100" id="applyBtn">
                            <i class="ti ti-filter me-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ---------- Unreachable brands ---------- --}}
        <div id="unreachableAlert" class="alert alert-warning d-flex align-items-center {{ empty($data['unreachable']) ? 'd-none' : '' }}">
            <i class="ti ti-database-exclamation me-2"></i>
            <span>Not counted (database unreachable or not configured): <strong id="unreachableList">{{ implode(', ', $data['unreachable'] ?? []) }}</strong></span>
        </div>

        {{-- ---------- Stat tiles ---------- --}}
        <div class="row row-cards g-3 mb-3" id="statCards">
            @include('dashboard.partials.stats', ['totals' => $data['totals']])
        </div>

        {{-- ---------- Charts ---------- --}}
        <div class="row row-cards g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-building-bank me-1 text-indigo"></i> Collection by Branch</h3>
                    </div>
                    <div class="card-body"><canvas id="chartCollectionBranch" height="140"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-building-skyscraper me-1 text-teal"></i> Consumed by Branch</h3>
                    </div>
                    <div class="card-body"><canvas id="chartConsumedBranch" height="140"></canvas></div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-chart-donut-2 me-1 text-cyan"></i> Collection by Service Category</h3>
                    </div>
                    <div class="card-body"><canvas id="chartCollectionCategory" height="140"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-chart-pie-4 me-1 text-amber"></i> Consumed by Service Category</h3>
                    </div>
                    <div class="card-body"><canvas id="chartConsumedCategory" height="140"></canvas></div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-report-analytics me-1 text-rose"></i> Top Services by Consumed</h3>
                    </div>
                    <div class="card-body"><canvas id="chartConsumedService" height="140"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-arrows-exchange me-1 text-slate"></i> Collection vs Consumed per Brand</h3>
                    </div>
                    <div class="card-body"><canvas id="chartBrandBreakdown" height="140"></canvas></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const routes = {
        stats:     @json(route('dashboard.stats')),
        locations: @json(route('dashboard.locations')),
    };
    let payload = @json($data);
    const palette = ['#6366f1','#14b8a6','#06b6d4','#f59e0b','#f43f5e','#64748b','#8b5cf6','#f97316','#10b981','#ec4899','#0ea5e9','#22c55e'];

    const charts = {};
    function sumByKey(rows, keyField, valueField) {
        const map = {};
        (rows || []).forEach(r => {
            const label = (r['brand'] ? r['brand'] + ' · ' : '') + (r[keyField] || 'Unknown');
            map[label] = (map[label] || 0) + Number(r[valueField] || 0);
        });
        return map;
    }
    function buildChart(id, type, dataMap) {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        const labels = Object.keys(dataMap);
        const values = Object.values(dataMap);
        const colors = labels.map((_, i) => palette[i % palette.length]);
        const cfg = {
            type,
            data: {
                labels,
                datasets: [{
                    label: 'Amount',
                    data: values,
                    backgroundColor: colors,
                    borderColor: type === 'bar' ? colors : '#fff',
                    borderWidth: type === 'bar' ? 0 : 2,
                    borderRadius: type === 'bar' ? 6 : 0,
                }],
            },
            options: {
                plugins: {
                    legend: {
                        display: type !== 'bar',
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 10, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.label + ': PKR ' + Number(ctx.parsed.y ?? ctx.parsed ?? 0).toLocaleString()
                        }
                    },
                },
                scales: type === 'bar'
                    ? { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } } }
                    : {},
                responsive: true,
                maintainAspectRatio: false,
            },
        };
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, cfg);
    }
    function renderCharts(d) {
        buildChart('chartCollectionBranch',   'bar',      sumByKey(d.collection_by_branch,   'branch',   'amount'));
        buildChart('chartConsumedBranch',     'bar',      sumByKey(d.consumed_by_branch,     'branch',   'amount'));
        buildChart('chartCollectionCategory', 'doughnut', sumByKey(d.collection_by_category, 'category', 'amount'));
        buildChart('chartConsumedCategory',   'doughnut', sumByKey(d.consumed_by_category,   'category', 'amount'));
        buildChart('chartConsumedService',    'bar',      sumByKey(d.consumed_by_service,    'service',  'amount'));

        const bb = d.brand_breakdown || [];
        const ctx = document.getElementById('chartBrandBreakdown');
        if (ctx) {
            if (charts.brandBreakdown) charts.brandBreakdown.destroy();
            charts.brandBreakdown = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: bb.map(r => r.brand),
                    datasets: [
                        { label: 'Collection', data: bb.map(r => Number(r.collection || 0)), backgroundColor: palette[0], borderRadius: 6 },
                        { label: 'Consumed',   data: bb.map(r => Number(r.consumed   || 0)), backgroundColor: palette[1], borderRadius: 6 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } } },
                },
            });
        }
    }
    renderCharts(payload);

    // ----- Filter form -----
    const form      = document.getElementById('filterForm');
    const brandSel  = document.getElementById('filterBrand');
    const locSel    = document.getElementById('filterLocation');
    const periodSel = document.getElementById('filterPeriod');
    const applyBtn  = document.getElementById('applyBtn');
    const customEls = document.querySelectorAll('.custom-date-field');

    function togglePeriod() {
        const show = periodSel.value === 'custom';
        customEls.forEach(el => el.style.display = show ? '' : 'none');
    }
    togglePeriod();
    periodSel.addEventListener('change', togglePeriod);

    async function loadLocations() {
        locSel.innerHTML = '<option value="">All Locations</option>';
        if (!brandSel.value) { locSel.disabled = true; return; }
        locSel.disabled = false;
        try {
            const res = await fetch(routes.locations + '?brand=' + encodeURIComponent(brandSel.value), {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            (json.data || []).forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                locSel.appendChild(opt);
            });
        } catch (e) { /* ignore */ }
    }
    brandSel.addEventListener('change', loadLocations);
    if (brandSel.value) { loadLocations().then(() => { locSel.value = @json($filters['location']); }); }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading…';
        try {
            const params = new URLSearchParams(new FormData(form));
            const res = await fetch(routes.stats + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            payload = json.data;

            const t = payload.totals || {};
            document.querySelectorAll('[data-stat]').forEach(el => {
                const key = el.dataset.stat;
                el.textContent = formatValue(key, t[key]);
            });

            const un = payload.unreachable || [];
            const alertEl = document.getElementById('unreachableAlert');
            document.getElementById('unreachableList').textContent = un.join(', ');
            alertEl.classList.toggle('d-none', un.length === 0);

            renderCharts(payload);
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = '<i class="ti ti-filter me-1"></i> Apply';
        }
    });

    function formatValue(key, val) {
        if (val === undefined || val === null) return 'N/A';
        const money = ['total_collection', 'total_consumed'];
        if (money.includes(key)) return 'PKR ' + Number(val).toLocaleString();
        return Number(val).toLocaleString();
    }
})();
</script>
@endpush
