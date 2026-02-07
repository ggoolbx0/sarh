<?php

namespace App\Http\Controllers;

use App\Exceptions\OutOfGeofenceException;
use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * POST /attendance/check-in
     *
     * Validates GPS coordinates and records check-in with financial snapshot.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $log = $this->attendanceService->checkIn(
                user:   $request->user(),
                lat:    (float) $validated['latitude'],
                lng:    (float) $validated['longitude'],
                ip:     $request->ip(),
                device: $request->userAgent(),
            );

            return response()->json([
                'message' => __('attendance.check_in_success'),
                'data'    => $this->formatAttendanceLog($log),
            ], 201);

        } catch (OutOfGeofenceException $e) {
            throw ValidationException::withMessages([
                'geofence' => [$e->getMessage()],
            ])->status(422);
        }
    }

    /**
     * POST /attendance/check-out
     *
     * Records check-out, calculates overtime/early-leave, finalizes financial data.
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $log = $this->attendanceService->checkOut(
            user: $request->user(),
            lat:  (float) $validated['latitude'],
            lng:  (float) $validated['longitude'],
        );

        return response()->json([
            'message' => __('attendance.check_out_success'),
            'data'    => $this->formatAttendanceLog($log),
        ]);
    }

    /**
     * GET /attendance/today
     *
     * Returns today's attendance status for the authenticated user.
     */
    public function todayStatus(Request $request): JsonResponse
    {
        $log = AttendanceLog::where('user_id', $request->user()->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        if (!$log) {
            return response()->json([
                'message' => __('attendance.not_checked_in'),
                'data'    => null,
            ]);
        }

        return response()->json([
            'message' => __('attendance.today_status'),
            'data'    => $this->formatAttendanceLog($log),
        ]);
    }

    /**
     * Format an AttendanceLog for JSON response.
     */
    private function formatAttendanceLog(AttendanceLog $log): array
    {
        return [
            'id'                        => $log->id,
            'attendance_date'           => $log->attendance_date->toDateString(),
            'status'                    => $log->status,
            'check_in_at'              => $log->check_in_at?->toIso8601String(),
            'check_out_at'             => $log->check_out_at?->toIso8601String(),
            'check_in_within_geofence' => $log->check_in_within_geofence,
            'check_in_distance_meters' => $log->check_in_distance_meters,
            'delay_minutes'            => $log->delay_minutes,
            'worked_minutes'           => $log->worked_minutes,
            'overtime_minutes'         => $log->overtime_minutes,
            'early_leave_minutes'      => $log->early_leave_minutes,
            'cost_per_minute'          => $log->cost_per_minute,
            'delay_cost'               => $log->delay_cost,
            'early_leave_cost'         => $log->early_leave_cost,
            'overtime_value'           => $log->overtime_value,
        ];
    }
}
