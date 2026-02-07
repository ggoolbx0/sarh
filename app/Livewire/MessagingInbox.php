<?php

namespace App\Livewire;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MessagingInbox extends Component
{
    #[Computed]
    public function conversations()
    {
        return Auth::user()
            ->conversations()
            ->active()
            ->with(['latestMessage.sender', 'participants'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('is_read', false)
                      ->where('sender_id', '!=', Auth::id());
            }])
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function totalUnread(): int
    {
        return $this->conversations->sum('unread_count');
    }

    public function render()
    {
        return view('livewire.messaging-inbox')
            ->layout('layouts.pwa', ['title' => __('pwa.nav_messages')]);
    }
}
