<x-filament-panels::page>
    <div class="space-y-4">
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
            <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                <x-heroicon-o-shield-exclamation class="w-5 h-5" />
                <span class="font-medium">{{ __('command.level_10_only') }}</span>
                <span class="mx-2">•</span>
                <span>{{ __('command.access_logged') }}</span>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
