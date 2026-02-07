<?php

namespace App\Services;

use App\Exceptions\OutOfGeofenceException;
use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AttendanceService
{
    public function __construct(
        protected GeofencingService $geofencing,
    ) {}

    /**
     * Full check-in flow: geofence → status → financial snapshot → save.
     *
     * @throws OutOfGeofenceException  When employee is outside the branch geofence
     * @throws \Illuminate\Database\QueryException  On duplicate check-in (unique constraint)
     */
    public function checkIn(
        User    $user,
        float   $lat,
        float   $lng,
        ?string $ip = null,
        ?string $device = null,
    ): AttendanceLog {
        // 1 — Resolve the user's branch (eager-load if needed)
        $branch = $user->branch;

        if (!$branch) {
            throw new \RuntimeException(__('attendance.no_branch_assigned'));
        }

        // 2 — Geofence validation
        $geo = $this->geofencing->validatePosition($branch, $lat, $lng);

        if (!$geo['within_geofence']) {
            throw new OutOfGeofenceException($geo['distance_meters'], $branch->geofence_radius);
        }

        // 3 — Resolve shift (user-specific or branch default)
        $shift      = $user->currentShift();
        $shiftStart = $shift?->start_time ?? $branch->default_shift_start;
        $grace      = $shift?->grace_period_minutes ?? $branch->grace_period_minutes ?? 5;

        // 4 — Create the attendance log
        $now = Carbon::now();
        $log = new AttendanceLog([
            'user_id'                   => $user->id,
            'branch_id'                 => $branch->id,
            'attendance_date'           => $now->toDateString(),
            'check_in_at'              => $now,
            'check_in_latitude'        => $lat,
            'check_in_longitude'       => $lng,
            'check_in_distance_meters' => $geo['distance_meters'],
            'check_in_within_geofence' => true,
            'check_in_ip'              => $ip,
            'check_in_device'          => $device,
        ]);

        // 5 — Evaluate attendance status (present / late / absent)
        $log->evaluateAttendance($shiftStart, $grace);

        // 6 — Snapshot the financial data (cost_per_minute is FROZEN here)
        $log->calculateFinancials();

        // 7 — Persist
        $log->save();

        return $log;
    }

    /**
     * Full check-out flow: geofence → worked minutes → overtime/early-leave → recalc → save.
     *
     * @throws ModelNotFoundException  If no check-in record found for today
     */
    public function checkOut(User $user, float $lat, float $lng): AttendanceLog
    {
        // 1 — Find today's check-in
        $log = AttendanceLog::where('user_id', $user->id)
            ->whereDate('attendance_date', Carbon::today())
            ->firstOrFail();

        // 2 — Geofence for check-out coordinates
        $branch = $user->branch;
        $geo    = $this->geofencing->validatePosition($branch, $lat, $lng);

        // 3 — Record check-out data
        $now = Carbon::now();
        $log->check_out_at              = $now;
        $log->check_out_latitude        = $lat;
        $log->check_out_longitude       = $lng;
        $log->check_out_distance_meters = $geo['distance_meters'];
        $log->check_out_within_geofence = $geo['within_geofence'];

        // 4 — Calculate worked minutes
        $workedMinutes = (int) $log->check_in_at->diffInMinutes($now);
        $log->worked_minutes = $workedMinutes;

        // 5 — Compare with shift duration → overtime or early leave
        $shift           = $user->currentShift();
        $expectedMinutes = $shift?->duration_minutes
                         ?? $this->calculateBranchShiftMinutes($branch);

        if ($workedMinutes > $expectedMinutes) {
            $log->overtime_minutes     = $workedMinutes - $expectedMinutes;
            $log->early_leave_minutes  = 0;
        } elseif ($workedMinutes < $expectedMinutes) {
            $log->early_leave_minutes  = $expectedMinutes - $workedMinutes;
            $log->overtime_minutes     = 0;
        } else {
            $log->overtime_minutes     = 0;
            $log->early_leave_minutes  = 0;
        }

        // 6 — Recalculate financials (uses same frozen cost_per_minute)
        $costPerMinute = (float) $log->cost_per_minute;
        $log->delay_cost       = round($log->delay_minutes * $costPerMinute, 2);
        $log->early_leave_cost = round($log->early_leave_minutes * $costPerMinute, 2);
        $log->overtime_value   = round($log->overtime_minutes * $costPerMinute * 1.5, 2);

        // 7 — Save
        $log->save();

        return $log;
    }

    /**
     * Wrapper for User::calculateDelayCost — available as a service method.
     */
    public function calculateDelayCost(User $user, int $minutesDelayed): float
    {
        return $user->calculateDelayCost($minutesDelayed);
    }

    /**
     * Calculate expected shift minutes from branch default times.
     */
    private function calculateBranchShiftMinutes($branch): int
    {
        if (!$branch->default_shift_start || !$branch->default_shift_end) {
            return 480; // Default 8 hours
        }

        $start = Carbon::parse($branch->default_shift_start);
        $end   = Carbon::parse($branch->default_shift_end);

        if ($end->lt($start)) {
            $end->addDay(); // overnight
        }

        return (int) $start->diffInMinutes($end);
    }
}
