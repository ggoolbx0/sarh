<div class="max-w-2xl mx-auto space-y-6">
    <div class="card">
        <div class="card-header flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ __('pwa.wb_track_title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('pwa.wb_track_subtitle') }}</p>
            </div>
        </div>

        <form wire:submit="track" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pwa.wb_enter_token') }}</label>
                <input type="text" wire:model="token" class="input-field font-mono" placeholder="{{ __('pwa.wb_token_placeholder') }}">
                @error('token') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn-primary w-full">
                <span wire:loading.remove>{{ __('pwa.wb_track_btn') }}</span>
                <span wire:loading class="animate-pulse">{{ __('pwa.loading') }}...</span>
            </button>
        </form>
    </div>

    {{-- Error Message --}}
    @if($errorMessage)
    <div class="p-4 bg-red-50 rounded-xl text-sm text-red-700">
        {{ $errorMessage }}
    </div>
    @endif

    {{-- Report Status --}}
    @if($report)
    <div class="card space-y-4">
        <h3 class="text-lg font-bold text-gray-800">{{ __('pwa.wb_report_status') }}</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_ticket') }}</p>
                <p class="font-bold font-mono">{{ $report['ticket_number'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_status') }}</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $report['status'] === 'resolved' ? 'bg-emerald-100 text-emerald-800' :
                       ($report['status'] === 'investigating' ? 'bg-amber-100 text-amber-800' :
                       'bg-blue-100 text-blue-800') }}">
                    {{ __('pwa.wb_status_' . $report['status']) }}
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_category') }}</p>
                <p class="font-medium">{{ __('pwa.wb_cat_' . $report['category']) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_severity') }}</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $report['severity'] === 'critical' ? 'bg-red-100 text-red-800' :
                       ($report['severity'] === 'high' ? 'bg-amber-100 text-amber-800' :
                       'bg-gray-100 text-gray-800') }}">
                    {{ __('pwa.wb_sev_' . $report['severity']) }}
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_submitted_at') }}</p>
                <p class="font-medium">{{ $report['created_at'] }}</p>
            </div>
            @if($report['resolved_at'])
            <div>
                <p class="text-xs text-gray-500">{{ __('pwa.wb_resolved_at') }}</p>
                <p class="font-medium">{{ $report['resolved_at'] }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="text-center">
        <a href="{{ route('whistleblower.form') }}" class="text-sm text-emerald-600 hover:underline">
            {{ __('pwa.wb_new_report') }}
        </a>
    </div>
</div>
