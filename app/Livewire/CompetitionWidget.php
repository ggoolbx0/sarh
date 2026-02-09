<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CompetitionWidget extends Component
{
    public array $topBranches = [];
    public ?array $myBranch = null;
    public int $myBranchRank = 0;
    public string $myBranchLevel = 'starter';
    public int $totalBranches = 0;

    public function mount(): void
    {
        $this->loadCompetitionData();
    }

    public function loadCompetitionData(): void
    {
        $user = Auth::user();
        $now = now();

        // Get all active branches with their monthly stats
        $branches = Branch::active()
            ->withCount(['users' => fn ($q) => $q->active()])
            ->get()
            ->map(function (Branch $branch) use ($now) {
                $logs = AttendanceLog::where('branch_id', $branch->id)
                    ->whereMonth('attendance_date', $now->month)
                    ->whereYear('attendance_date', $now->year)
                    ->get();

                $totalLogs = $logs->count();
                $lateLogs = $logs->where('status', 'late')->count();
                $absentLogs = $logs->where('status', 'absent')->count();
                $financialLoss = round($logs->sum('delay_cost'), 2);

                // Perfect employees: those with zero late/absent this month
                $employeesWithIssues = AttendanceLog::where('branch_id', $branch->id)
                    ->whereMonth('attendance_date', $now->month)
                    ->whereYear('attendance_date', $now->year)
                    ->whereIn('status', ['late', 'absent'])
                    ->distinct('user_id')
                    ->count('user_id');

                $perfectEmployees = max(0, $branch->users_count - $employeesWithIssues);

                // Total gamification points for the branch
                $branchPoints = User::where('branch_id', $branch->id)
                    ->active()
                    ->sum('total_points');

                // Score calculation: base 1000, penalties for late/absent, bonuses
                $score = 1000
                    - ($lateLogs * 5)
                    - ($absentLogs * 15)
                    + ($perfectEmployees * 20)
                    + (int) ($branchPoints * 0.1);

                $score = max(0, $score);

                return [
                    'id'                => $branch->id,
                    'name'              => $branch->name,
                    'code'              => $branch->code,
                    'employees'         => $branch->users_count,
                    'late_checkins'     => $lateLogs,
                    'missed_days'       => $absentLogs,
                    'financial_loss'    => $financialLoss,
                    'perfect_employees' => $perfectEmployees,
                    'total_points'      => (int) $branchPoints,
                    'score'             => $score,
                    'level'             => $this->calculateLevel($score),
                ];
            })
            ->sortByDesc('score')
            ->values();

        $this->totalBranches = $branches->count();

        // Find user's branch rank
        if ($user->branch_id) {
            $rank = 1;
            foreach ($branches as $b) {
                if ($b['id'] === $user->branch_id) {
                    $this->myBranchRank = $rank;
                    $this->myBranch = $b;
                    $this->myBranchLevel = $b['level'];
                    break;
                }
                $rank++;
            }
        }

        // Top 3 branches
        $this->topBranches = $branches->take(3)->toArray();
    }

    private function calculateLevel(int $score): string
    {
        return match (true) {
            $score >= 950  => 'legendary',
            $score >= 850  => 'diamond',
            $score >= 700  => 'gold',
            $score >= 500  => 'silver',
            $score >= 300  => 'bronze',
            default        => 'starter',
        };
    }

    public function render()
    {
        return view('livewire.competition-widget');
    }
}
