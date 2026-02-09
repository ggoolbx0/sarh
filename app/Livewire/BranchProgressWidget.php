<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BranchProgressWidget extends Component
{
    public ?string $branchName = null;
    public int $branchEmployees = 0;
    public float $attendanceRate = 0;
    public float $branchDelayCost = 0;
    public int $perfectEmployees = 0;
    public int $lateCount = 0;
    public int $absentCount = 0;
    public string $currentLevel = 'starter';
    public string $nextLevel = '';
    public int $currentScore = 0;
    public int $nextLevelThreshold = 0;
    public float $progressPercent = 0;

    private array $levelThresholds = [
        'starter'   => 0,
        'bronze'    => 300,
        'silver'    => 500,
        'gold'      => 700,
        'diamond'   => 850,
        'legendary' => 950,
    ];

    public function mount(): void
    {
        $this->loadBranchProgress();
    }

    public function loadBranchProgress(): void
    {
        $user = Auth::user();

        if (!$user->branch_id) {
            return;
        }

        $branch = Branch::withCount(['users' => fn ($q) => $q->active()])
            ->find($user->branch_id);

        if (!$branch) {
            return;
        }

        $this->branchName = $branch->name;
        $this->branchEmployees = $branch->users_count;

        $now = now();

        $logs = AttendanceLog::where('branch_id', $branch->id)
            ->whereMonth('attendance_date', $now->month)
            ->whereYear('attendance_date', $now->year)
            ->get();

        $totalLogs = $logs->count();
        $this->lateCount = $logs->where('status', 'late')->count();
        $this->absentCount = $logs->where('status', 'absent')->count();
        $this->branchDelayCost = round($logs->sum('delay_cost'), 2);

        $this->attendanceRate = $totalLogs > 0
            ? round((($totalLogs - $this->lateCount - $this->absentCount) / $totalLogs) * 100, 1)
            : 100;

        // Perfect employees
        $employeesWithIssues = AttendanceLog::where('branch_id', $branch->id)
            ->whereMonth('attendance_date', $now->month)
            ->whereYear('attendance_date', $now->year)
            ->whereIn('status', ['late', 'absent'])
            ->distinct('user_id')
            ->count('user_id');

        $this->perfectEmployees = max(0, $this->branchEmployees - $employeesWithIssues);

        // Calculate score
        $branchPoints = User::where('branch_id', $branch->id)
            ->active()
            ->sum('total_points');

        $this->currentScore = max(0, 1000
            - ($this->lateCount * 5)
            - ($this->absentCount * 15)
            + ($this->perfectEmployees * 20)
            + (int) ($branchPoints * 0.1));

        // Determine level and progress
        $this->currentLevel = $this->calculateLevel($this->currentScore);
        $this->calculateProgress();
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

    private function calculateProgress(): void
    {
        $levels = array_keys($this->levelThresholds);
        $currentIndex = array_search($this->currentLevel, $levels);

        if ($currentIndex === false || $currentIndex >= count($levels) - 1) {
            // Already at max level
            $this->nextLevel = '';
            $this->progressPercent = 100;
            $this->nextLevelThreshold = $this->levelThresholds['legendary'];
            return;
        }

        $this->nextLevel = $levels[$currentIndex + 1];
        $currentThreshold = $this->levelThresholds[$this->currentLevel];
        $this->nextLevelThreshold = $this->levelThresholds[$this->nextLevel];

        $range = $this->nextLevelThreshold - $currentThreshold;
        $progress = $this->currentScore - $currentThreshold;

        $this->progressPercent = $range > 0
            ? min(100, round(($progress / $range) * 100, 1))
            : 100;
    }

    public function render()
    {
        return view('livewire.branch-progress-widget');
    }
}
