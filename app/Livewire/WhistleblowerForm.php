<?php

namespace App\Livewire;

use App\Models\WhistleblowerReport;
use Livewire\Component;

class WhistleblowerForm extends Component
{
    public string $category = '';
    public string $severity = 'medium';
    public string $content = '';

    public bool $submitted = false;
    public string $ticketNumber = '';
    public string $anonymousToken = '';

    protected function rules(): array
    {
        return [
            'category' => ['required', 'in:fraud,harassment,corruption,safety'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'content'  => ['required', 'min:20'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $ticket = WhistleblowerReport::generateTicketNumber();
        $token = WhistleblowerReport::generateAnonymousToken();

        $report = WhistleblowerReport::create([
            'ticket_number'   => $ticket,
            'encrypted_content' => encrypt($this->content),
            'category'        => $this->category,
            'severity'        => $this->severity,
            'anonymous_token' => $token,
            'status'          => 'new',
        ]);

        $this->ticketNumber = $ticket;
        $this->anonymousToken = $token;
        $this->submitted = true;

        // Reset form
        $this->category = '';
        $this->severity = 'medium';
        $this->content = '';
    }

    public function render()
    {
        return view('livewire.whistleblower-form')
            ->layout('layouts.pwa', ['title' => __('pwa.wb_title')]);
    }
}
