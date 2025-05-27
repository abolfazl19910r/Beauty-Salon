<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SupportTicketMessage;

class SupportTicketMessageFactory extends Factory
{
    protected $model = SupportTicketMessage::class;

    public function definition(): array
    {
        $ticket = SupportTicket::factory()->create();

        return [
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'message' => fake()->paragraphs(2, true),
            'is_staff_reply' => false,
            'attachments' => null,
        ];
    }

    public function staffReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->admin(),
            'is_staff_reply' => true,
        ]);
    }

    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => [
                'file1.jpg',
                'document.pdf'
            ],
        ]);
    }
}
