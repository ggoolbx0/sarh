<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
        {{ __('pwa.gamification_title') }}
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($totalPoints) }}</div>
            <div class="stat-label">{{ __('pwa.points') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-amber-500">{{ $currentStreak }}</div>
            <div class="stat-label">{{ __('pwa.current_streak') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-blue-500">{{ $longestStreak }}</div>
            <div class="stat-label">{{ __('pwa.best_streak') }}</div>
        </div>
    </div>

    {{-- Badges --}}
    @if(count($badges) > 0)
    <div>
        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('pwa.earned_badges') }}</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($badges as $badge)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium"
                      style="background-color: {{ $badge->color }}20; color: {{ $badge->color }}">
                    {{ $badge->name }}
                </span>
            @endforeach
        </div>
    </div>
    @else
    <p class="text-sm text-gray-400 text-center py-2">{{ __('pwa.no_badges') }}</p>
    @endif
</div>
