<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Form --}}
        <x-filament-panels::form wire:submit="generateReport">
            {{ $this->form }}
            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-funnel">
                    {{ __('command.delay_impact_title') }}
                </x-filament::button>
            </div>
        </x-filament-panels::form>

        @if($impactAnalysis)
        {{-- Delay Impact Analysis --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500">{{ __('command.potential_loss') }}</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ number_format($impactAnalysis['potential_loss'], 2) }}
                        <span class="text-sm">{{ __('command.sar') }}</span>
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500">{{ __('command.actual_loss') }}</p>
                    <p class="text-2xl font-bold text-amber-600">
                        {{ number_format($impactAnalysis['actual_loss'], 2) }}
                        <span class="text-sm">{{ __('command.sar') }}</span>
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500">{{ __('command.discipline_savings') }}</p>
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ number_format($impactAnalysis['discipline_savings'], 2) }}
                        <span class="text-sm">{{ __('command.sar') }}</span>
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500">{{ __('command.roi_percentage') }}</p>
                    <p class="text-2xl font-bold {{ $impactAnalysis['roi_percentage'] >= 20 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $impactAnalysis['roi_percentage'] }}%
                    </p>
                </div>
            </x-filament::section>
        </div>
        @endif

        @if($predictiveData)
        {{-- Predictive Analytics --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('command.predictive_title') }}</x-slot>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <p class="text-xs text-gray-500">{{ __('command.avg_daily_loss') }}</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200">
                        {{ number_format($predictiveData['avg_daily_loss'], 2) }} {{ __('command.sar') }}
                    </p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <p class="text-xs text-gray-500">{{ __('command.accumulated_loss') }}</p>
                    <p class="text-lg font-bold text-amber-600">
                        {{ number_format($predictiveData['accumulated_loss'], 2) }} {{ __('command.sar') }}
                    </p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <p class="text-xs text-gray-500">{{ __('command.remaining_days') }}</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200">
                        {{ $predictiveData['remaining_working_days'] }}
                    </p>
                </div>
                <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-700">
                    <p class="text-xs text-red-500">{{ __('command.predicted_total') }}</p>
                    <p class="text-lg font-bold text-red-600">
                        {{ number_format($predictiveData['predicted_total'], 2) }} {{ __('command.sar') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
