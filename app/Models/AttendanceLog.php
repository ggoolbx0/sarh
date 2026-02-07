<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'attendance_date',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_in_within_geofence',
        'check_in_ip',
        'check_in_device',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_within_geofence',
        'status',
        'delay_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'worked_minutes',
        'cost_per_minute',
        'delay_cost',
        'early_leave_cost',
        'overtime_value',
        'notes',
        'approved_by',
        'approved_at',
        'is_manual_entry',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date'          => 'date',
            'check_in_at'             => 'datetime',
            'check_out_at'            => 'datetime',
            'approved_at'             => 'datetime',
            'check_in_within_geofence'=> 'boolean',
            'check_out_within_geofence'=> 'boolean',
            'is_manual_entry'         => 'boolean',
            'cost_per_minute'         => 'decimal:4',
            'delay_cost'              => 'decimal:2',
            'early_leave_cost'        => 'decimal:2',
            'overtime_value'          => 'decimal:2',
            'delay_minutes'           => 'integer',
            'early_leave_minutes'     => 'integer',
            'overtime_minutes'        => 'integer',
            'worked_minutes'          => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL CALCULATION
    |--------------------------------------------------------------------------
    */

    /**
     * Snapshot the employee's cost_per_minute at check-in time
     * and calculate financial impacts.
     */
    public function calculateFinancials(): self
    {
        $costPerMinute = $this->user->cost_per_minute;

        $this->cost_per_minute   = $costPerMinute;
        $this->delay_cost        = round($this->delay_minutes * $costPerMinute, 2);
        $this->early_leave_cost  = round($this->early_leave_minutes * $costPerMinute, 2);
        $this->overtime_value    = round($this->overtime_minutes * $costPerMinute * 1.5, 2); // 1.5x overtime rate

        return $this;
    }

    /**
     * Calculate delay & status from shift times.
     */
    public function evaluateAttendance(string $shiftStart, int $gracePeriod = 5): self
    {
        if (!$this->check_in_at) {
            $this->status = 'absent';
            return $this;
        }

        $expectedStart = $this->attendance_date->copy()->setTimeFromTimeString($shiftStart);
        $graceEnd = $expectedStart->copy()->addMinutes($gracePeriod);

        if ($this->check_in_at <= $graceEnd) {
            $this->status = 'present';
            $this->delay_minutes = 0;
        } else {
            $this->status = 'late';
            $this->delay_minutes = (int) $expectedStart->diffInMinutes($this->check_in_at);
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeWithDelayCost($query)
    {
        return $query->where('delay_cost', '>', 0);
    }

    /**
     * Get total financial loss for a query scope.
     */
    public function scopeTotalDelayCost($query): float
    {
        return (float) $query->sum('delay_cost');
    }
}
