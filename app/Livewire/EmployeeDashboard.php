<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmployeeDashboard extends Component
{
    public function render()
    {
        return view('livewire.employee-dashboard')
            ->layout('layouts.pwa', ['title' => __('pwa.dashboard')]);
    }
}
