<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AttendanceStatsWidget extends Component
{
    public int $presentDays = 0;
    public int $lateDays = 0;
    public int $absentDays = 0;
    public int $totalDays = 0;
    public float $avgDelayMinutes = 0;
    public int $totalWorkedMinutes = 0;
    public int $totalOvertimeMinutes = 0;
    public array $weeklyBreakdown = [];
    public int $onTimeStreak = 0;

    public function mount(): void
    {
        $this->loadAttendanceStats();
    }

    public function loadAttendanceStats(): void
    {
        $user = Auth::user();
        $now = now();

        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereMonth('attendance_date', $now->month)
            ->whereYear('attendance_date', $now->year)
            ->orderBy('attendance_date')
            ->get();

        $this->totalDays = $logs->count();
        $this->presentDays = $logs->where('status', 'present')->count();
        $this->lateDays = $logs->where('status', 'late')->count();
        $this->absentDays = $logs->where('status', 'absent')->count();

        $lateMinutes = $logs->where('status', 'late')->sum('delay_minutes');
        $this->avgDelayMinutes = $this->lateDays > 0
            ? round($lateMinutes / $this->lateDays, 1)
            : 0;

        $this->totalWorkedMinutes = (int) $logs->sum('worked_minutes');
        $this->totalOvertimeMinutes = (int) $logs->sum('overtime_minutes');

        // Calculate on-time streak (consecutive present days from most recent)
        $this->onTimeStreak = 0;
        $reversedLogs = $logs->sortByDesc('attendance_date');
        foreach ($reversedLogs as $log) {
            if ($log->status === 'present') {
                $this->onTimeStreak++;
            } else {
                break;
            }
        }

        // Weekly breakdown (last 4 weeks)
        $this->weeklyBreakdown = [];
        $startOfMonth = $now->copy()->startOfMonth();

        for ($week = 0; $week < 4; $week++) {
            $weekStart = $startOfMonth->copy()->addWeeks($week);
            $weekEnd = $weekStart->copy()->endOfWeek();

            if ($weekStart->gt($now)) {
                break;
            }

            $weekLogs = $logs->filter(function ($log) use ($weekStart, $weekEnd) {
                return $log->attendance_date->between($weekStart, $weekEnd);
            });

            $total = $weekLogs->count();
            $onTime = $weekLogs->where('status', 'present')->count();

            $this->weeklyBreakdown[] = [
                'label' => __('pwa.week') . ' ' . ($week + 1),
                'total' => $total,
                'on_time' => $onTime,
                'late' => $weekLogs->where('status', 'late')->count(),
                'absent' => $weekLogs->where('status', 'absent')->count(),
                'rate' => $total > 0 ? round(($onTime / $total) * 100) : 0,
            ];
        }
    }

    public function render()
    {
        return view('livewire.attendance-stats-widget');
    }
}
