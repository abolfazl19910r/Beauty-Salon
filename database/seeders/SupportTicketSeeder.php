<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $admin = User::where('is_admin', true)->first();

        if (!$admin || $users->isEmpty()) {
            return;
        }

        $ticketTypes = [
            'سوال درباره خدمات' => ['medium', 'service'],
            'مشکل در پرداخت' => ['high', 'payment'],
            'درخواست لغو نوبت' => ['medium', 'booking'],
            'سوال درباره ساعات کاری' => ['low', 'other'],
            'شکایت از خدمات' => ['high', 'service'],
            'مشکل فنی سایت' => ['high', 'other'],
            'درخواست تغییر زمان نوبت' => ['medium', 'booking'],
            'سوال درباره قیمت‌ها' => ['low', 'other'],
        ];

        foreach ($users as $user) {
            $ticketCount = rand(1, 3);

            for ($i = 0; $i < $ticketCount; $i++) {
                $ticketTitle = array_rand($ticketTypes);
                $ticketInfo = $ticketTypes[$ticketTitle];

                $ticket = SupportTicket::create([
                    'user_id' => $user->id,
                    'title' => $ticketTitle,
                    'description' => fake()->paragraphs(2, true),
                    'priority' => $ticketInfo[0],
                    'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'closed']),
                    'category' => $ticketInfo[1],
                    'assigned_to' => rand(0, 1) ? $admin->id : null,
                ]);

                SupportTicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'message' => fake()->paragraphs(2, true),
                    'is_staff_reply' => false,
                ]);

                if (in_array($ticket->status, ['in_progress', 'resolved', 'closed'])) {
                    SupportTicketMessage::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $admin->id,
                        'message' => fake()->paragraphs(2, true),
                        'is_staff_reply' => true,
                    ]);

                    if (rand(0, 1)) {
                        SupportTicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $user->id,
                            'message' => fake()->paragraph(),
                            'is_staff_reply' => false,
                        ]);
                    }

                    if (in_array($ticket->status, ['resolved', 'closed'])) {
                        SupportTicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $admin->id,
                            'message' => fake()->paragraphs(1, true),
                            'is_staff_reply' => true,
                        ]);

                        if ($ticket->status === 'resolved') {
                            $ticket->update([
                                'resolved_at' => now()->subDays(rand(1, 10)),
                            ]);
                        } elseif ($ticket->status === 'closed') {
                            $ticket->update([
                                'resolved_at' => now()->subDays(rand(5, 15)),
                                'closed_at' => now()->subDays(rand(1, 5)),
                            ]);
                        }
                    }
                }
            }
        }

        SupportTicket::create([
            'user_id' => $users->random()->id,
            'title' => 'مشکل اضطراری در پرداخت',
            'description' => 'پرداخت از حساب من انجام شده اما سیستم نوبت من را تایید نکرده است.',
            'priority' => 'urgent',
            'status' => 'open',
            'category' => 'payment',
            'metadata' => json_encode(['payment_id' => rand(1000, 9999)]),
        ]);

        SupportTicketMessage::create([
            'ticket_id' => SupportTicket::latest()->first()->id,
            'user_id' => $users->random()->id,
            'message' => 'لطفا هرچه سریعتر رسیدگی کنید. شماره پیگیری پرداخت: '.rand(10000000, 99999999),
            'is_staff_reply' => false,
        ]);
    }
}
