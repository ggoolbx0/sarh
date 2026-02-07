<div class="card" x-data="{ latitude: null, longitude: null, geoError: null }" x-init="
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => { latitude = pos.coords.latitude; longitude = pos.coords.longitude; },
            err => { geoError = err.message; }
        );
    }
">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ __('pwa.attendance_title') }}
    </div>

    {{-- Status Badge --}}
    <div class="flex items-center gap-3 mb-4">
        @if($status === 'checked_in')
            <span class="badge-success">
                <span class="w-2 h-2 bg-emerald-500 rounded-full me-2 animate-pulse"></span>
                {{ __('pwa.status_checked_in') }}
            </span>
            <span class="text-sm text-gray-500">{{ $checkInTime }}</span>
        @elseif($status === 'checked_out')
            <span class="badge-warning">
                {{ __('pwa.status_checked_out') }}
            </span>
            <span class="text-sm text-gray-500">{{ $checkInTime }} → {{ $checkOutTime }}</span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                {{ __('pwa.status_not_checked_in') }}
            </span>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-3">
        @if($status === 'not_checked_in')
            <button
                @click="if(latitude) $wire.checkIn(latitude, longitude)"
                :disabled="!latitude"
                class="btn-primary text-sm flex-1"
                :class="{ 'opacity-50 cursor-not-allowed': !latitude }">
                {{ __('pwa.btn_check_in') }}
            </button>
        @elseif($status === 'checked_in')
            <button
                @click="if(latitude) $wire.checkOut(latitude, longitude)"
                :disabled="!latitude"
                class="btn-secondary text-sm flex-1"
                :class="{ 'opacity-50 cursor-not-allowed': !latitude }">
                {{ __('pwa.btn_check_out') }}
            </button>
        @endif
    </div>

    {{-- Messages --}}
    @if($message)
        <div class="mt-3 text-sm {{ str_contains($message, 'success') || str_contains($message, 'نجاح') ? 'text-emerald-600' : 'text-red-600' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Geo Error --}}
    <template x-if="geoError">
        <div class="mt-3 text-sm text-amber-600" x-text="'{{ __('pwa.geo_error') }}: ' + geoError"></div>
    </template>
</div>
