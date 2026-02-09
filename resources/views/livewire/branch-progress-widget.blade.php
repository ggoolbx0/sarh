<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        {{ __('pwa.branch_progress_title') }}
    </div>

    @if($branchName)
        {{-- Branch Identity --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-semibold text-gray-800">{{ $branchName }}</h3>
                <p class="text-xs text-gray-500">{{ $branchEmployees }} {{ __('competition.employees') }}</p>
            </div>
            <div class="text-center">
                <span class="text-lg">
                    @switch($currentLevel)
                        @case('legendary') 👑 @break
                        @case('diamond')   💎 @break
                        @case('gold')      🥇 @break
                        @case('silver')    🥈 @break
                        @case('bronze')    🥉 @break
                        @default           🏁
                    @endswitch
                </span>
                <div class="text-xs font-medium {{ match($currentLevel) {
                    'legendary' => 'text-purple-600',
                    'diamond'   => 'text-cyan-600',
                    'gold'      => 'text-yellow-600',
                    'silver'    => 'text-gray-500',
                    'bronze'    => 'text-orange-600',
                    default     => 'text-gray-400',
                } }}">{{ __('competition.level_' . $currentLevel) }}</div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="stat-card">
                <div class="stat-value {{ $attendanceRate >= 90 ? 'text-emerald-600' : ($attendanceRate >= 70 ? 'text-amber-500' : 'text-red-600') }}">
                    {{ $attendanceRate }}%
                </div>
                <div class="stat-label">{{ __('pwa.branch_attendance_rate') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-value {{ $branchDelayCost > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ number_format($branchDelayCost, 0) }}
                </div>
                <div class="stat-label">{{ __('competition.financial_loss') }} ({{ __('competition.sar') }})</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-emerald-600">{{ $perfectEmployees }}</div>
                <div class="stat-label">{{ __('competition.perfect_employees') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-red-500">{{ $lateCount }}</div>
                <div class="stat-label">{{ __('competition.late_checkins') }}</div>
            </div>
        </div>

        {{-- Level Progress Bar --}}
        <div>
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>{{ __('competition.level_' . $currentLevel) }} ({{ number_format($currentScore) }})</span>
                @if($nextLevel)
                    <span>{{ __('competition.level_' . $nextLevel) }} ({{ number_format($nextLevelThreshold) }})</span>
                @else
                    <span>{{ __('pwa.max_level') }} ✓</span>
                @endif
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-700 {{ match($currentLevel) {
                    'legendary' => 'bg-gradient-to-r from-purple-500 to-purple-600',
                    'diamond'   => 'bg-gradient-to-r from-cyan-400 to-cyan-600',
                    'gold'      => 'bg-gradient-to-r from-yellow-400 to-yellow-600',
                    'silver'    => 'bg-gradient-to-r from-gray-300 to-gray-500',
                    'bronze'    => 'bg-gradient-to-r from-orange-400 to-orange-600',
                    default     => 'bg-gradient-to-r from-gray-300 to-gray-400',
                } }}"
                     style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-400 text-center py-4">{{ __('pwa.no_branch_assigned') }}</p>
    @endif
</div>
