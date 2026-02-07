<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use App\Services\GeofencingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AttendanceWidget extends Component
{
    public ?string $status = null;
    public ?string $checkInTime = null;
    public ?string $checkOutTime = null;
    public bool $isInsideGeofence = false;
    public string $message = '';

    public function mount(): void
    {
        $this->loadTodayStatus();
    }

    public function loadTodayStatus(): void
    {
        $user = Auth::user();
        $today = AttendanceLog::where('user_id', $user->id)
            ->where('attendance_date', now()->toDateString())
            ->first();

        if ($today) {
            $this->checkInTime = $today->check_in_at?->format('H:i');
            $this->checkOutTime = $today->check_out_at?->format('H:i');
            $this->status = $today->check_out_at ? 'checked_out' : 'checked_in';
        } else {
            $this->status = 'not_checked_in';
        }
    }

    public function checkIn(float $latitude, float $longitude): void
    {
        try {
            $service = new AttendanceService(new GeofencingService());
            $log = $service->checkIn(Auth::user(), $latitude, $longitude);
            $this->checkInTime = $log->check_in_at->format('H:i');
            $this->status = 'checked_in';
            $this->message = __('pwa.check_in_success');
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
        }
    }

    public function checkOut(float $latitude, float $longitude): void
    {
        try {
            $service = new AttendanceService(new GeofencingService());
            $log = $service->checkOut(Auth::user(), $latitude, $longitude);
            $this->checkOutTime = $log->check_out_at->format('H:i');
            $this->status = 'checked_out';
            $this->message = __('pwa.check_out_success');
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.attendance-widget');
    }
}
