@php
    $cards = [
        ['key' => 'total_collection',    'label' => 'Total Collection',    'icon' => 'ti-cash',             'bg' => 'bg-grad-indigo', 'money' => true],
        ['key' => 'total_consumed',      'label' => 'Total Consumed',      'icon' => 'ti-wallet',           'bg' => 'bg-grad-teal',   'money' => true],
        ['key' => 'total_consultations', 'label' => 'Consultations',       'icon' => 'ti-stethoscope',      'bg' => 'bg-grad-cyan',   'money' => false],
        ['key' => 'total_treatments',    'label' => 'Treatments',          'icon' => 'ti-vaccine',          'bg' => 'bg-grad-amber',  'money' => false],
        ['key' => 'total_leads',         'label' => 'Leads',               'icon' => 'ti-users',            'bg' => 'bg-grad-rose',   'money' => false],
        ['key' => 'total_plans',         'label' => 'Plans',               'icon' => 'ti-clipboard-list',   'bg' => 'bg-grad-slate',  'money' => false],
    ];
@endphp

@foreach ($cards as $c)
<div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
    <div class="card stat-tile">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="stat-ico {{ $c['bg'] }}">
                <i class="ti {{ $c['icon'] }}"></i>
            </div>
            <div>
                <div class="stat-val" data-stat="{{ $c['key'] }}">
                    @php $val = $totals[$c['key']] ?? 0; @endphp
                    {{ $c['money'] ? 'PKR ' . number_format($val) : number_format($val) }}
                </div>
                <div class="stat-lbl">{{ $c['label'] }}</div>
            </div>
        </div>
    </div>
</div>
@endforeach
