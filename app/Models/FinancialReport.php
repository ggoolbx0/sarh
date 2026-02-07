<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_code',
        'scope',
        'period_type',
        'period_start',
        'period_end',
        'user_id',
        'branch_id',
        'department_id',
        'total_working_days',
        'total_present_days',
        'total_late_days',
        'total_absent_days',
        'total_leave_days',
        'total_delay_minutes',
        'total_early_leave_minutes',
        'total_overtime_minutes',
        'total_worked_minutes',
        'total_salary_budget',
        'total_delay_cost',
        'total_early_leave_cost',
        'total_overtime_cost',
        'net_financial_impact',
        'loss_percentage',
        'generated_by',
        'generated_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_start'             => 'date',
            'period_end'               => 'date',
            'generated_at'             => 'datetime',
            'total_salary_budget'      => 'decimal:2',
            'total_delay_cost'         => 'decimal:2',
            'total_early_leave_cost'   => 'decimal:2',
            'total_overtime_cost'      => 'decimal:2',
            'net_financial_impact'     => 'decimal:2',
            'loss_percentage'          => 'decimal:2',
            'meta'                     => 'array',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | REPORT GENERATION
    |--------------------------------------------------------------------------
    */

    /**
     * Build a report for a single employee over a date range.
     */
    public static function generateForEmployee(User $user, string $start, string $end): self
    {
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get();

        $report = new self([
            'report_code'              => 'FIN-EMP-' . $user->employee_id . '-' . now()->format('YmdHis'),
            'scope'                    => 'employee',
            'period_type'              => 'custom',
            'period_start'             => $start,
            'period_end'               => $end,
            'user_id'                  => $user->id,
            'branch_id'                => $user->branch_id,
            'department_id'            => $user->department_id,
            'total_working_days'       => $logs->count(),
            'total_present_days'       => $logs->where('status', 'present')->count(),
            'total_late_days'          => $logs->where('status', 'late')->count(),
            'total_absent_days'        => $logs->where('status', 'absent')->count(),
            'total_leave_days'         => $logs->where('status', 'on_leave')->count(),
            'total_delay_minutes'      => $logs->sum('delay_minutes'),
            'total_early_leave_minutes'=> $logs->sum('early_leave_minutes'),
            'total_overtime_minutes'   => $logs->sum('overtime_minutes'),
            'total_worked_minutes'     => $logs->sum('worked_minutes'),
            'total_salary_budget'      => $user->basic_salary,
            'total_delay_cost'         => $logs->sum('delay_cost'),
            'total_early_leave_cost'   => $logs->sum('early_leave_cost'),
            'total_overtime_cost'      => $logs->sum('overtime_value'),
            'generated_at'             => now(),
        ]);

        // Calculate net impact & loss %
        $report->net_financial_impact = $report->total_delay_cost
                                      + $report->total_early_leave_cost
                                      - $report->total_overtime_cost;

        $report->loss_percentage = $report->total_salary_budget > 0
            ? round(($report->total_delay_cost / $report->total_salary_budget) * 100, 2)
            : 0;

        return $report;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Generate a unique report code.
     */
    public static function generateReportCode(string $scope): string
    {
        $prefix = strtoupper(substr($scope, 0, 3));
        return "FIN-{$prefix}-" . now()->format('YmdHis') . '-' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }
}
