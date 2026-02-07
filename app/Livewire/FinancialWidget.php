<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinancialWidget extends Component
{
    public float $totalDelayCost = 0;
    public float $onTimeRate = 0;
    public int $totalDays = 0;
    public int $lateDays = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->get();

        $this->totalDays = $logs->count();
        $this->lateDays = $logs->where('status', 'late')->count();
        $this->totalDelayCost = round($logs->sum('delay_cost'), 2);
        $this->onTimeRate = $this->totalDays > 0
            ? round((($this->totalDays - $this->lateDays) / $this->totalDays) * 100, 1)
            : 100;
    }

    public function render()
    {
        return view('livewire.financial-widget');
    }
}
