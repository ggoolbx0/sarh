<div class="space-y-4 p-4">
    <div>
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('command.decrypted_content') }}</h4>
        <div class="mt-1 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $content }}</p>
        </div>
    </div>

    @if($investigatorNotes)
    <div>
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('command.investigator_notes') }}</h4>
        <div class="mt-1 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $investigatorNotes }}</p>
        </div>
    </div>
    @endif

    @if($resolutionOutcome)
    <div>
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('command.resolution_outcome') }}</h4>
        <div class="mt-1 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-700">
            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $resolutionOutcome }}</p>
        </div>
    </div>
    @endif

    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-xs text-red-600 dark:text-red-400">
        <x-heroicon-o-lock-closed class="w-4 h-4 inline-block" />
        {{ __('command.access_logged') }}
    </div>
</div>
