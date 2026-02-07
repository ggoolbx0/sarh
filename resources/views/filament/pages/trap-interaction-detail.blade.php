<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.triggered_by') }}</h4>
            <p class="text-sm font-medium">{{ $interaction->user?->name_ar ?? '—' }}</p>
        </div>
        <div>
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.trap_type') }}</h4>
            <p class="text-sm font-medium">{{ $interaction->trap_type }}</p>
        </div>
        <div>
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.risk_level') }}</h4>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $interaction->risk_level === 'critical' ? 'bg-red-100 text-red-800' :
                   ($interaction->risk_level === 'high' ? 'bg-orange-100 text-orange-800' :
                   ($interaction->risk_level === 'medium' ? 'bg-amber-100 text-amber-800' :
                   'bg-green-100 text-green-800')) }}">
                {{ __('traps.risk_levels.' . $interaction->risk_level) }}
            </span>
        </div>
        <div>
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.triggered_at') }}</h4>
            <p class="text-sm font-medium">{{ $interaction->created_at->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.page_url') }}</h4>
        <p class="text-sm font-mono bg-gray-50 dark:bg-gray-800 p-2 rounded">{{ $interaction->page_url ?? '—' }}</p>
    </div>

    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.ip_address') }}</h4>
        <p class="text-sm font-mono">{{ $interaction->ip_address ?? '—' }}</p>
    </div>

    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.user_agent') }}</h4>
        <p class="text-sm font-mono text-xs bg-gray-50 dark:bg-gray-800 p-2 rounded break-all">{{ $interaction->user_agent ?? '—' }}</p>
    </div>

    @if($interaction->interaction_data)
    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.interaction_data') }}</h4>
        <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-x-auto">{{ json_encode($interaction->interaction_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

    @if($interaction->review_notes)
    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('command.review_notes') }}</h4>
        <p class="text-sm bg-blue-50 dark:bg-blue-900/20 p-3 rounded">{{ $interaction->review_notes }}</p>
    </div>
    @endif

    {{-- Risk Trajectory --}}
    <div>
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('command.risk_trajectory') }}</h4>
        @php
            $allInteractions = $interaction->user?->trapInteractions()
                ->orderBy('created_at')
                ->get()
                ->map(fn($ti, $i) => [
                    'n'     => $i + 1,
                    'score' => (int)(10 * (pow(2, $i + 1) - 1)),
                    'date'  => $ti->created_at->format('m/d'),
                ]) ?? collect();
        @endphp
        @if($allInteractions->count() > 0)
        <div class="flex items-end gap-1 h-20 mt-2">
            @foreach($allInteractions as $point)
            @php
                $maxScore = $allInteractions->max('score') ?: 1;
                $heightPercent = ($point['score'] / $maxScore) * 100;
            @endphp
            <div class="flex flex-col items-center flex-1">
                <span class="text-[9px] text-gray-400 mb-1">{{ $point['score'] }}</span>
                <div class="w-full rounded-t transition-all duration-300
                    {{ $point['score'] >= 300 ? 'bg-red-500' : ($point['score'] >= 100 ? 'bg-orange-500' : ($point['score'] >= 30 ? 'bg-amber-500' : 'bg-emerald-500')) }}"
                    style="height: {{ max(4, $heightPercent) }}%">
                </div>
                <span class="text-[8px] text-gray-400 mt-1">{{ $point['date'] }}</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-xs text-gray-400">—</p>
        @endif
    </div>
</div>
