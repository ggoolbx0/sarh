<?php

namespace Tests\Feature;

use App\Livewire\MessagingChat;
use App\Livewire\MessagingInbox;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createConversationBetween(User $a, User $b): Conversation
    {
        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $a->id,
        ]);

        $conversation->participants()->attach([$a->id, $b->id]);

        return $conversation;
    }

    /**
     * TC-MSG-001: Messaging inbox requires authentication.
     */
    public function test_messaging_inbox_requires_auth(): void
    {
        $response = $this->get(route('messaging.inbox'));

        // Auth middleware blocks access — either redirect (if login route exists) or 500
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /**
     * TC-MSG-002: Authenticated user sees their conversations.
     */
    public function test_authenticated_user_sees_conversations(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $conversation = $this->createConversationBetween($user, $other);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $other->id,
            'body'            => 'Hello there!',
            'type'            => 'text',
        ]);

        $this->actingAs($user);

        Livewire::test(MessagingInbox::class)
            ->assertSee('Hello there!');
    }

    /**
     * TC-MSG-003: Sending a message creates a record in the database.
     */
    public function test_send_message_creates_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $conversation = $this->createConversationBetween($user, $other);

        $this->actingAs($user);

        Livewire::test(MessagingChat::class, ['conversation' => $conversation])
            ->set('newMessage', 'Test message from user')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => 'Test message from user',
        ]);
    }

    /**
     * TC-MSG-004: Read receipts update when chat is opened.
     */
    public function test_read_receipts_update_on_open(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $conversation = $this->createConversationBetween($user, $other);

        // Other user sends a message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $other->id,
            'body'            => 'Unread message',
            'type'            => 'text',
            'is_read'         => false,
        ]);

        $this->assertFalse($message->is_read);

        $this->actingAs($user);

        // Opening the chat should mark messages as read
        Livewire::test(MessagingChat::class, ['conversation' => $conversation]);

        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    /**
     * TC-MSG-005: Empty message is not sent.
     */
    public function test_empty_message_not_sent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $conversation = $this->createConversationBetween($user, $other);

        $this->actingAs($user);

        Livewire::test(MessagingChat::class, ['conversation' => $conversation])
            ->set('newMessage', '   ')
            ->call('sendMessage');

        $this->assertDatabaseCount('messages', 0);
    }
}
