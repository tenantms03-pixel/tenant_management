@extends('layouts.managerdashboardlayout')

@section('title', 'Reports')
@section('page-title', '')

@php
    use Illuminate\Support\Str;

    // Define themes for known report keys (key should match the route key you pass)
    $themeMap = [
        'payment-history' => [
            'bg'   => 'linear-gradient(135deg,#0d6efd,#5a9bf6)',
            'icon' => 'bi-cash-stack',
            'text' => '#ffffff'
        ],
        'active-tenants' => [
            'bg'   => 'linear-gradient(135deg,#198754,#48c78e)',
            'icon' => 'bi-people-fill',
            'text' => '#ffffff'
        ],
        'lease-summary' => [
            'bg'   => 'linear-gradient(135deg,#ffc107,#ffd95a)',
            'icon' => 'bi-house-fill',
            'text' => '#1f2d3d' // dark text for light bg
        ],
        'finance' => [
            'bg'   => 'linear-gradient(135deg,#dc3545,#f36c6c)',
            'icon' => 'bi-wallet2',
            'text' => '#ffffff'
        ],
        'maintenance-requests' => [
            'bg'   => 'linear-gradient(135deg,#20c997,#6fdcbf)',
            'icon' => 'bi-tools',
            'text' => '#ffffff'
        ],
        'application' => [
            'bg'   => 'linear-gradient(135deg,#6f42c1,#a678f1)',
            'icon' => 'bi-file-earmark-text',
            'text' => '#ffffff'
        ],
        // fallback theme
        'default' => [
            'bg'   => 'linear-gradient(135deg,#6c757d,#adb5bd)',
            'icon' => 'bi-bar-chart-line-fill',
            'text' => '#ffffff'
        ],
    ];
@endphp

@section('content')
<div class="container">
    <h4 class="mb-4 fw-bold text-dark"> Reports Dashboard</h4>

    <div class="row g-4">
        @foreach($reports as $key => $label)
            @php
                // make a slug-like key for mapping safety
                $slug = Str::slug($key);

                // choose theme (use slug if present otherwise default)
                $theme = $themeMap[$slug] ?? ($themeMap[$key] ?? $themeMap['default']);
            @endphp

            <div class="col-md-4">
                @if(str_contains(strtolower($label), 'coming soon'))
                    <div class="card shadow-sm border-0 h-100 bg-light text-muted text-center p-4">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div class="mb-3">
                                <i class="bi bi-clock-history display-4 text-secondary"></i>
                            </div>
                            <h5 class="card-title">{{ $label }}</h5>
                            <p class="small">🚧 This report is under development</p>
                        </div>
                    </div>
                @else
                    <a href="{{ route('manager.reports.show', $key) }}" class="text-decoration-none" aria-label="Open {{ $label }} report">
                        <div class="card shadow-sm border-0 h-100 report-card"
                             style="background: {{ $theme['bg'] }}; color: {{ $theme['text'] }}; border-radius: 12px;">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                                <div class="mb-3">
                                    <i class="{{ $theme['icon'] }} display-4" style="color: {{ $theme['text'] }};"></i>
                                </div>
                                <h5 class="card-title fw-semibold" style="color: {{ $theme['text'] }};">{{ $label }}</h5>
                                <p class="small" style="color: {{ $theme['text'] }}; opacity: .92;">Click to view detailed report</p>
                            </div>
                        </div>
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Shared hover + transition; color/bg are applied inline per card to avoid selector mismatch */
    .report-card {
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }
    .report-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 14px 40px rgba(0,0,0,0.12);
    }

    /* Small responsive tweak */
    @media (max-width: 576px) {
        .report-card .display-4 { font-size: 2.2rem; }
        .report-card .card-title { font-size: 1rem; }
    }
</style>
@endpush
