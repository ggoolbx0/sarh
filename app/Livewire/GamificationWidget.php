<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GamificationWidget extends Component
{
    public int $totalPoints = 0;
    public int $currentStreak = 0;
    public int $longestStreak = 0;
    public $badges = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->totalPoints = $user->total_points ?? 0;
        $this->currentStreak = $user->current_streak ?? 0;
        $this->longestStreak = $user->longest_streak ?? 0;
        $this->badges = $user->badges()->latest('pivot_created_at')->take(6)->get();
    }

    public function render()
    {
        return view('livewire.gamification-widget');
    }
}
