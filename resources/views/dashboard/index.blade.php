@extends('layouts.app')
@section('title', 'Analytics')

@push('styles')
<style>
    /* Palette roles. Series hexes are the validated categorical slots 1 & 2,
       checked against the Sneat card surface (#fff). Chrome/ink use Sneat's
       own tokens so text never wears a series color. */
    :root {
        --viz-collection: #2a78d6;
        --viz-consumed:   #eb6834;
        --viz-grid:       #e7e7f1;
        --viz-axis:       #a1acb8;
    }
    .viz-empty {
        display: flex; align-items: center; justify-content: center;
        height: 100%; min-height: 180px; color: #a1acb8; font-size: .875rem;
    }
    .filter-bar .form-label {
        font-size: .6875rem; font-weight: 600; letter-spacing: .4px;
        text-transform: uppercase; color: #a1acb8; margin-bottom: .25rem;
    }
    .period-badge { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')

{{-- ---------- Header ---------- --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-1">Analytics</h4>
        <p class="mb-0 text-muted">
            @if ($filters['brand'])
                {{ config("brands.items.{$filters['brand']}.label") }}
            @else
                All {{ count($brands) }} brands
            @endif
        </p>
    </div>
    <span class="badge bg-label-primary period-badge px-3 py-2">
        <i class="bx bx-calendar me-1"></i>{{ $filters['from'] }} &rarr; {{ $filters['to'] }}
    </span>
</div>

{{-- ---------- Filters ---------- --}}
<div class="card mb-4 filter-bar">
    <div class="card-body py-4">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="form-label" for="filterBrand">Brand</label>
                <select name="brand" id="filterBrand" class="form-select">
                    <option value="">All Brands</option>
                    @foreach ($brands as $key => $brand)
                        <option value="{{ $key }}" @selected($filters['brand'] === $key)>{{ $brand['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-md-6">
                <label class="form-label" for="filterLocation">Branch</label>
                <select name="location" id="filterLocation" class="form-select" {{ $filters['brand'] ? '' : 'disabled' }}>
                    <option value="">All Branches</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label class="form-label" for="filterPeriod">Period</label>
                <select name="period" id="filterPeriod" class="form-select">
                    @php
                        $periods = [
                            'today' => 'Today', 'yesterday' => 'Yesterday', 'last7days' => 'Last 7 Days',
                            'week' => 'This Week', 'thismonth' => 'This Month', 'lastmonth' => 'Last Month',
                            'custom' => 'Custom',
                        ];
                    @endphp
                    @foreach ($periods as $k => $label)
                        <option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-md-4 custom-date-field" style="display:none;">
                <label class="form-label" for="filterFrom">From</label>
                <input type="date" name="from" id="filterFrom" class="form-control" value="{{ $filters['from'] }}">
            </div>
            <div class="col-xl-2 col-md-4 custom-date-field" style="display:none;">
                <label class="form-label" for="filterTo">To</label>
                <input type="date" name="to" id="filterTo" class="form-control" value="{{ $filters['to'] }}">
            </div>

            <div class="col-xl-2 col-md-4">
                <button type="submit" class="btn btn-primary w-100" id="applyBtn">
                    <i class="bx bx-filter-alt me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ---------- Unreachable ---------- --}}
<div id="unreachableAlert"
     class="alert alert-warning d-flex align-items-center {{ empty($data['unreachable']) ? 'd-none' : '' }}"
     role="alert">
    <i class="bx bx-error-circle me-2"></i>
    <span>Not included &mdash; database unreachable or not configured:
        <strong id="unreachableList">{{ implode(', ', $data['unreachable'] ?? []) }}</strong>
    </span>
</div>

{{-- ---------- KPI row ---------- --}}
<div class="row g-4 mb-4" id="statCards">
    @include('dashboard.partials.stats', ['totals' => $data['totals']])
</div>

{{-- ---------- Trend ---------- --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center pb-0">
                <div>
                    <h5 class="card-title mb-0">Daily Collection</h5>
                    <small class="text-muted">Cash received per day over the selected period</small>
                </div>
            </div>
            <div class="card-body pt-3">
                <div id="chartTrend"></div>
            </div>
        </div>
    </div>
</div>

{{-- ---------- Comparison + branches ---------- --}}
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h5 class="card-title mb-0">Collection vs Consumed</h5>
                <small class="text-muted">Per brand</small>
            </div>
            <div class="card-body pt-3"><div id="chartBrandCompare"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h5 class="card-title mb-0">Collection by Branch</h5>
                <small class="text-muted">Cash received per centre</small>
            </div>
            <div class="card-body pt-3"><div id="chartCollectionBranch"></div></div>
        </div>
    </div>
</div>

{{-- ---------- Categories ---------- --}}
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h5 class="card-title mb-0">Collection by Service Category</h5>
                <small class="text-muted">Top 10 categories</small>
            </div>
            <div class="card-body pt-3"><div id="chartCollectionCategory"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h5 class="card-title mb-0">Consumed by Service Category</h5>
                <small class="text-muted">Top 10 categories</small>
            </div>
            <div class="card-body pt-3"><div id="chartConsumedCategory"></div></div>
        </div>
    </div>
</div>

{{-- ---------- Services ---------- --}}
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h5 class="card-title mb-0">Top Services by Consumed</h5>
                <small class="text-muted">Top 12 by invoiced value</small>
            </div>
            <div class="card-body pt-3"><div id="chartConsumedService"></div></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const routes = {
        stats:     @json(route('dashboard.stats')),
        locations: @json(route('dashboard.locations')),
    };
    let payload = @json($data);

    const C = {
        collection: '#2a78d6',
        consumed:   '#eb6834',
        grid:       '#e7e7f1',
        axis:       '#a1acb8',
    };
    const FONT = '"Public Sans", system-ui, -apple-system, "Segoe UI", sans-serif';

    const fmtFull  = v => 'PKR ' + Math.round(Number(v) || 0).toLocaleString();
    const fmtShort = v => {
        const n = Math.abs(Number(v) || 0);
        const s = Number(v) < 0 ? '-' : '';
        if (n >= 1e7) return s + (n / 1e7).toFixed(1).replace(/\.0$/, '') + 'Cr';
        if (n >= 1e5) return s + (n / 1e5).toFixed(1).replace(/\.0$/, '') + 'L';
        if (n >= 1e3) return s + Math.round(n / 1e3) + 'k';
        return s + Math.round(n);
    };

    // ---- data shaping -----------------------------------------------------

    // Fold the same name across brands into one row: a category like "Hair
    // Transplant" is the same thing everywhere, and keeping them apart turned
    // 28 categories into 41 rows, most of which fell off the chart. Matched
    // case-insensitively ("Weight loss" / "Weight Loss"), keeping whichever
    // spelling appeared first.
    function merge(rows, keyField) {
        rows = rows || [];
        const map = {};
        rows.forEach(r => {
            const name = r[keyField] || 'Unknown';
            const key  = name.trim().toLowerCase();
            if (!map[key]) map[key] = { label: name, value: 0 };
            map[key].value += Number(r.amount || 0);
        });
        const out = {};
        Object.values(map).forEach(e => { out[e.label] = e.value; });
        return out;
    }

    // Branches keep their own row per brand — they are distinct places. Branch
    // names already carry the brand ("American Aesthetics Bahadurabad"), so the
    // prefix is only added where one name is used by two brands, as
    // "Alkhaleej DHA Karachi" is by both Hair and Skin.
    function mergeBranches(rows) {
        rows = rows || [];
        const brandsPerName = {};
        rows.forEach(r => {
            const k = (r.branch || 'Unknown').trim().toLowerCase();
            (brandsPerName[k] = brandsPerName[k] || new Set()).add(r.brand);
        });

        const map = {};
        rows.forEach(r => {
            const name     = r.branch || 'Unknown';
            const collides = brandsPerName[name.trim().toLowerCase()].size > 1;
            const label    = (collides && r.brand ? r.brand + ' · ' : '') + name;
            map[label] = (map[label] || 0) + Number(r.amount || 0);
        });
        return map;
    }

    // Sort desc and keep the top n.
    //
    // No "Other" bar: the server already caps each brand's series, so a
    // remainder computed here would cover only part of the tail while looking
    // like the whole of it — and it came out longer than every real bar,
    // dominating the chart with a number that wasn't true. These cards say
    // "Top N", which is what they now show.
    function top(map, n) {
        const sorted = Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, n);
        return { labels: sorted.map(e => e[0]), values: sorted.map(e => e[1]) };
    }

    // ---- chart factories --------------------------------------------------

    const charts = {};

    function mount(id, options, hasData) {
        const el = document.querySelector('#' + id);
        if (!el) return;
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
        el.innerHTML = '';
        if (!hasData) {
            el.innerHTML = '<div class="viz-empty">No data for this selection</div>';
            return;
        }
        charts[id] = new ApexCharts(el, options);
        charts[id].render();
    }

    const baseGrid = {
        borderColor: C.grid,
        strokeDashArray: 0,
        padding: { top: -8, right: 8, bottom: 0, left: 8 },
    };
    // Horizontal bars print their value past the bar end, so the plot needs
    // room on the right or the longest bar's label is clipped at the frame.
    const hBarGrid = { ...baseGrid, padding: { top: -8, right: 56, bottom: 0, left: 8 } };
    const baseLabelStyle = { colors: C.axis, fontSize: '12px', fontFamily: FONT };

    // Horizontal bar: the default for long category/branch names.
    function hBar(id, map, color, cap, labelWidth) {
        const { labels, values } = top(map, cap || 10);
        const rows = labels.length;
        // Headroom past the longest bar, so its value label has somewhere to
        // sit instead of being clipped against the plot frame.
        const axisMax = Math.max(...values, 0) * 1.18 || undefined;

        mount(id, {
            chart: {
                type: 'bar',
                height: Math.max(200, rows * 38 + 60),
                toolbar: { show: false },
                fontFamily: FONT,
                parentHeightOffset: 0,
            },
            series: [{ name: 'Amount', data: values }],
            colors: [color],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    borderRadiusApplication: 'end',
                    barHeight: '62%',
                    distributed: false,
                    dataLabels: { position: 'top' },
                },
            },
            dataLabels: {
                enabled: true,
                offsetX: 34,
                style: { fontSize: '12px', fontWeight: 500, colors: [C.axis], fontFamily: FONT },
                formatter: fmtShort,
            },
            grid: { ...hBarGrid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            // On a horizontal bar ApexCharts still reads the names off xaxis;
            // without this it falls back to numeric row indices.
            xaxis: {
                categories: labels,
                // Both ends pinned: giving only a max lets ApexCharts pick a
                // negative minimum, so the axis started below zero.
                min: 0,
                max: axisMax,
                tickAmount: 4,
                labels: { style: baseLabelStyle, formatter: fmtShort },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                // Capped to what the card can actually hold: left longer, the
                // label overflows the card edge instead of ellipsising.
                labels: { style: baseLabelStyle, maxWidth: labelWidth || 180 },
            },
            tooltip: {
                style: { fontFamily: FONT },
                y: { formatter: fmtFull, title: { formatter: () => '' } },
            },
            legend: { show: false },
            states: { hover: { filter: { type: 'darken', value: 0.9 } } },
        }, rows > 0);
    }

    function renderTrend(points) {
        const data = (points || []).map(p => ({ x: p.date, y: Number(p.amount || 0) }));
        const any  = data.some(p => p.y !== 0);

        mount('chartTrend', {
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                fontFamily: FONT,
                zoom: { enabled: false },
                parentHeightOffset: 0,
            },
            series: [{ name: 'Collection', data }],
            colors: [C.collection],
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 95] },
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { size: 5 } },
            grid: { ...baseGrid, xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } } },
            xaxis: {
                type: 'datetime',
                labels: { style: baseLabelStyle, format: 'dd MMM' },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false },
            },
            yaxis: { labels: { style: baseLabelStyle, formatter: fmtShort } },
            tooltip: {
                style: { fontFamily: FONT },
                x: { format: 'ddd, dd MMM yyyy' },
                y: { formatter: fmtFull },
            },
            legend: { show: false },
        }, data.length > 0 && any);
    }

    function renderBrandCompare(rows) {
        rows = rows || [];
        const labels = rows.map(r => r.brand);

        mount('chartBrandCompare', {
            chart: {
                type: 'bar',
                height: Math.max(240, rows.length * 56 + 70),
                toolbar: { show: false },
                fontFamily: FONT,
                parentHeightOffset: 0,
            },
            series: [
                { name: 'Collection', data: rows.map(r => Number(r.collection || 0)) },
                { name: 'Consumed',   data: rows.map(r => Number(r.consumed   || 0)) },
            ],
            colors: [C.collection, C.consumed],
            plotOptions: {
                bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '74%' },
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['#fff'] },
            grid: { ...baseGrid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            xaxis: {
                categories: labels,
                labels: { style: baseLabelStyle, formatter: fmtShort },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { labels: { style: baseLabelStyle, maxWidth: 200 } },
            tooltip: { style: { fontFamily: FONT }, y: { formatter: fmtFull } },
            legend: {
                position: 'top', horizontalAlign: 'left', offsetY: 4,
                markers: { size: 6 }, fontFamily: FONT, labels: { colors: C.axis },
                itemMargin: { horizontal: 10 },
            },
        }, rows.length > 0);
    }

    function renderAll(d) {
        renderTrend(d.trend);
        renderBrandCompare(d.brand_breakdown);
        //                                                                cap  label width
        hBar('chartCollectionBranch',   mergeBranches(d.collection_by_branch),      C.collection, 14, 230);
        hBar('chartCollectionCategory', merge(d.collection_by_category, 'category'), C.collection, 10, 170);
        hBar('chartConsumedCategory',   merge(d.consumed_by_category,   'category'), C.consumed,   10, 170);
        hBar('chartConsumedService',    merge(d.consumed_by_service,    'service'),  C.consumed,   12, 300);
    }

    renderAll(payload);

    // ---- filters ----------------------------------------------------------

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

    async function loadLocations(preselect) {
        locSel.innerHTML = '<option value="">All Branches</option>';
        if (!brandSel.value) { locSel.disabled = true; return; }
        locSel.disabled = false;
        try {
            const res  = await fetch(routes.locations + '?brand=' + encodeURIComponent(brandSel.value),
                                     { headers: { Accept: 'application/json' } });
            const json = await res.json();
            (json.data || []).forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                locSel.appendChild(opt);
            });
            if (preselect) locSel.value = preselect;
        } catch (e) { /* leave "All Branches" */ }
    }

    brandSel.addEventListener('change', function () {
        locSel.value = '';
        loadLocations(null);
    });
    if (brandSel.value) loadLocations(@json($filters['location']));

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        applyBtn.disabled = true;
        const original = applyBtn.innerHTML;
        applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading';

        try {
            const params = new URLSearchParams(new FormData(form));
            const res  = await fetch(routes.stats + '?' + params.toString(),
                                     { headers: { Accept: 'application/json' } });
            const json = await res.json();
            payload = json.data;

            const t = payload.totals || {};
            document.querySelectorAll('[data-stat]').forEach(el => {
                const v = t[el.dataset.stat];
                el.textContent = (v === undefined || v === null)
                    ? '—'
                    : Math.round(Number(v)).toLocaleString();
            });

            const un = payload.unreachable || [];
            document.getElementById('unreachableList').textContent = un.join(', ');
            document.getElementById('unreachableAlert').classList.toggle('d-none', un.length === 0);

            renderAll(payload);
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = original;
        }
    });
})();
</script>
@endpush
