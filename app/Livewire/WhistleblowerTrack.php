<?php

namespace App\Livewire;

use App\Models\WhistleblowerReport;
use Livewire\Component;

class WhistleblowerTrack extends Component
{
    public string $token = '';
    public ?array $report = null;
    public bool $searched = false;
    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'token' => ['required', 'min:10'],
        ];
    }

    public function track(): void
    {
        $this->validate();
        $this->searched = true;
        $this->errorMessage = '';
        $this->report = null;

        $found = WhistleblowerReport::where('anonymous_token', $this->token)->first();

        if (!$found) {
            $this->errorMessage = __('pwa.wb_not_found');
            return;
        }

        // Return status only — NEVER expose encrypted content
        $this->report = [
            'ticket_number' => $found->ticket_number,
            'category'      => $found->category,
            'severity'      => $found->severity,
            'status'        => $found->status,
            'created_at'    => $found->created_at->format('Y-m-d H:i'),
            'resolved_at'   => $found->resolved_at?->format('Y-m-d H:i'),
        ];
    }

    public function render()
    {
        return view('livewire.whistleblower-track')
            ->layout('layouts.pwa', ['title' => __('pwa.wb_track_title')]);
    }
}
