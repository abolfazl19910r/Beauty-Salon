<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportTicketMessageFactory extends Factory
{
    use HasFactory;
    protected $model = SupportTicketMessage::class;

    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraphs(2, true),
            'is_staff_reply' => false,
            'attachments' => null,
        ];
    }

    public function staffReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
            'is_staff_reply' => true,
            'message' => 'پاسخ کارشناس: ' . fake()->sentence(),
        ]);
    }

    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => json_encode([
                ['path' => 'attachments/screenshot-' . fake()->randomNumber(4) . '.png'],
                ['path' => 'attachments/receipt-' . fake()->randomNumber(4) . '.pdf']
            ]),
        ]);
    }
}
