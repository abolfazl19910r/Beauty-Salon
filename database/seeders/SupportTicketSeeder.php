<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ⭐ ۲۰۲۶-۰۹-۲۵ — تیکت‌های نمونه‌ی واقع‌گرایانه.
 *
 * تیکت پشتیبانی یعنی «مدیر سالن ← تیم پلتفرم»: مالک/ادمین سالن از /admin تیکت می‌زنه و سوپرادمین از
 * /superadmin جواب می‌ده (مشتری‌های سالن اصلاً مسیر ثبت تیکت ندارن). نسخه‌ی قبلی این seeder ۳۱ تیکت
 * از ۱۰ کاربر تصادفی (مشتری) با متن‌های مشتری‌وار («پرداخت کردم ولی نوبتم ثبت نشد») می‌ساخت، که صندوق
 * سوپرادمین رو گمراه‌کننده می‌کرد. حالا چند تیکت از مالک‌های سالن‌های دمو، درباره‌ی موضوع‌های پلتفرم.
 */
class SupportTicketSeeder extends Seeder
{
    private const TICKETS = [
        ['payment', 'high', 'open', 'درگاه زیبال در تست اتصال خطای IP می‌دهد',
            'در صفحه‌ی درگاه‌های پرداخت، تست اتصال زیبال پیام «IP سرور در پنل زیبال ثبت نشده است» می‌دهد. IP را از کجا بگیرم؟', null],
        ['other', 'medium', 'in_progress', 'تمدید اشتراک سالانه',
            'اشتراک ما ماه آینده تمام می‌شود. آیا با تمدید سالانه تخفیفی شامل می‌شود؟',
            'بله، در صفحه‌ی اشتراک پلن سالانه را انتخاب کنید؛ مبلغ همان‌جا نمایش داده می‌شود.'],
        ['technical', 'medium', 'resolved', 'پیامک یادآوری نوبت برای مشتری‌ها ارسال نمی‌شود',
            'از دیروز پیامک یادآوری یک ساعت قبل از نوبت به مشتری‌ها نمی‌رسد.',
            'سقف پیامک ماهانه‌ی سالن پر شده بود؛ سقف را در تنظیمات اطلاع‌رسانی افزایش دادیم.'],
        ['technical', 'low', 'closed', 'اضافه کردن متخصص ششم',
            'موقع افزودن متخصص جدید پیام «سقف تعداد متخصص» می‌آید.',
            'پلن فعلی شما تا ۵ متخصص است؛ برای متخصص بیشتر پلن را ارتقا دهید.'],
        ['payment', 'urgent', 'open', 'پرداخت مشتری کسر شده ولی نوبت پرداخت‌نشده مانده',
            'یکی از مشتری‌ها می‌گوید مبلغ از کارتش کم شده، ولی نوبتش در پنل «در انتظار پرداخت» است. کد پیگیری را پیوست کرده‌ام.', null],
    ];

    public function run(): void
    {
        $staff = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first()
            ?? User::where('phone', '09399717435')->first();

        $owners = Salon::withoutGlobalScopes()->with(['admins' => fn ($q) => $q->wherePivot('role', 'owner')])->get()
            ->flatMap->admins->unique('id')->values();

        if ($owners->isEmpty()) {
            return; // بدون مالک سالن تیکتی معنی نداره
        }

        foreach (self::TICKETS as $i => [$category, $priority, $status, $title, $question, $answer]) {
            $owner = $owners[$i % $owners->count()];

            $ticket = SupportTicket::create([
                'user_id' => $owner->id,
                'title' => $title,
                'description' => $question,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'assigned_to' => $status !== 'open' ? $staff?->id : null,
                'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now()->subDays(3) : null,
                'closed_at' => $status === 'closed' ? now()->subDay() : null,
            ]);

            SupportTicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'message' => $question, 'is_staff_reply' => false]);

            if ($answer && $staff) {
                SupportTicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $staff->id, 'message' => $answer, 'is_staff_reply' => true]);
            }
        }
    }
}
