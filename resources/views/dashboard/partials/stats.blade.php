@php
    $cards = [
        ['key' => 'total_collection',    'label' => 'Collection',     'icon' => 'bx-wallet',         'tone' => 'primary', 'money' => true],
        ['key' => 'total_consumed',      'label' => 'Consumed',       'icon' => 'bx-receipt',        'tone' => 'warning', 'money' => true],
        ['key' => 'total_consultations', 'label' => 'Consultations',  'icon' => 'bx-user-voice',     'tone' => 'info',    'money' => false],
        ['key' => 'total_treatments',    'label' => 'Treatments',     'icon' => 'bx-plus-medical',   'tone' => 'success', 'money' => false],
        ['key' => 'total_leads',         'label' => 'Leads',          'icon' => 'bx-user-plus',      'tone' => 'danger',  'money' => false],
        ['key' => 'total_plans',         'label' => 'Plans',          'icon' => 'bx-package',        'tone' => 'secondary','money' => false],
    ];
@endphp

@foreach ($cards as $c)
    @php $val = $totals[$c['key']] ?? 0; @endphp
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
                <h4 class="card-title mb-0 text-nowrap" data-stat="{{ $c['key'] }}" style="font-size:1.375rem;">
                    {{ number_format($val) }}
                </h4>
                {{-- The unit sits under the figure so the number itself stays uncluttered. --}}
                <small class="text-muted">{{ $c['money'] ? 'PKR' : "\u{00A0}" }}</small>
            </div>
        </div>
    </div>
@endforeach
