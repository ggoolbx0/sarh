<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MessagingChat extends Component
{
    public Conversation $conversation;
    public string $newMessage = '';

    public function mount(Conversation $conversation): void
    {
        $this->conversation = $conversation;
        $this->markAsRead();
    }

    public function markAsRead(): void
    {
        Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function sendMessage(): void
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id'       => Auth::id(),
            'body'            => $this->newMessage,
            'type'            => 'text',
        ]);

        $this->conversation->touch();
        $this->newMessage = '';
        $this->markAsRead();
    }

    #[Computed]
    public function messages()
    {
        return $this->conversation
            ->messages()
            ->with('sender')
            ->oldest()
            ->get();
    }

    public function render()
    {
        return view('livewire.messaging-chat')
            ->layout('layouts.pwa', ['title' => $this->conversation->title ?? __('pwa.messaging_title')]);
    }
}
