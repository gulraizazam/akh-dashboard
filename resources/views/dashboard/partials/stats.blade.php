@php
    // `ratio` cards show arrived out of booked, the way each brand's own
    // dashboard reports consultancies and treatments.
    $cards = [
        ['key' => 'total_collection',    'label' => 'Collection',    'icon' => 'bx-wallet',       'tone' => 'primary',   'unit' => 'PKR'],
        ['key' => 'total_consumed',      'label' => 'Consumed',      'icon' => 'bx-receipt',      'tone' => 'warning',   'unit' => 'PKR'],
        ['key' => 'total_consultations', 'label' => 'Consultations', 'icon' => 'bx-user-voice',   'tone' => 'info',      'unit' => 'Arrived / Booked', 'ratio' => 'total_consultations_arrived'],
        ['key' => 'total_treatments',    'label' => 'Treatments',    'icon' => 'bx-plus-medical', 'tone' => 'success',   'unit' => 'Arrived / Booked', 'ratio' => 'total_treatments_arrived'],
        ['key' => 'total_leads',         'label' => 'Leads',         'icon' => 'bx-user-plus',    'tone' => 'danger',    'unit' => ''],
        ['key' => 'total_plans',         'label' => 'Plans',         'icon' => 'bx-package',      'tone' => 'secondary', 'unit' => ''],
    ];
@endphp

@foreach ($cards as $c)
    @php
        $val     = $totals[$c['key']] ?? 0;
        $arrived = isset($c['ratio']) ? ($totals[$c['ratio']] ?? 0) : null;
        $display = $arrived === null
            ? number_format($val)
            : number_format($arrived) . ' / ' . number_format($val);
    @endphp
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar avatar-sm flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-{{ $c['tone'] }}">
                            <i class="bx {{ $c['icon'] }}"></i>
                        </span>
                    </div>
                </div>
                <p class="text-muted text-uppercase mb-1" style="font-size:.6875rem; letter-spacing:.4px;">{{ $c['label'] }}</p>
                <h4 class="card-title mb-0 text-nowrap"
                    data-stat="{{ $c['key'] }}"
                    @isset($c['ratio']) data-stat-arrived="{{ $c['ratio'] }}" @endisset
                    style="font-size:1.375rem;">{{ $display }}</h4>
                {{-- The unit sits under the figure so the number itself stays uncluttered. --}}
                <small class="text-muted">{{ $c['unit'] !== '' ? $c['unit'] : "\u{00A0}" }}</small>
            </div>
        </div>
    </div>
@endforeach
