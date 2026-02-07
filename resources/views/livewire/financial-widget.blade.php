<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        {{ __('pwa.financial_title') }}
    </div>

    {{-- Discipline Score --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="stat-card">
            <div class="stat-value {{ $onTimeRate >= 90 ? 'text-emerald-600' : ($onTimeRate >= 70 ? 'text-amber-500' : 'text-red-600') }}">
                {{ $onTimeRate }}%
            </div>
            <div class="stat-label">{{ __('pwa.on_time_rate') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value {{ $totalDelayCost > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                {{ number_format($totalDelayCost, 2) }}
            </div>
            <div class="stat-label">{{ __('pwa.delay_cost') }} ({{ __('pwa.currency') }})</div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div>
        <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ __('pwa.this_month') }}</span>
            <span>{{ $totalDays - $lateDays }}/{{ $totalDays }} {{ __('pwa.on_time_days') }}</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div class="h-2.5 rounded-full transition-all duration-500 {{ $onTimeRate >= 90 ? 'bg-emerald-500' : ($onTimeRate >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                 style="width: {{ $onTimeRate }}%"></div>
        </div>
    </div>
</div>
