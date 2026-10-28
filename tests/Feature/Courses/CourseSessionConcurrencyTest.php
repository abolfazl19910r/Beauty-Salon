<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesCourses;
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * آخرین جلسه‌ی مانده‌ی دوره، دو درخواست هم‌زمان با دو ساعت مختلف (قید یکتای ساعت جلویش را نمی‌گیرد): فقط قفل ردیف دوره
 * (CourseService::bookSession) نمی‌گذارد هر دو رزرو شوند. دو پردازه، فقط MySQL/MariaDB.
 */
class CourseSessionConcurrencyTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;
    use RunsConcurrentProcess;

    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    protected function setUp(): void
    {
        $this->withoutTransactionOnConcurrentDatabase();
        parent::setUp();
        $this->skipUnlessConcurrentDatabase();
        $this->salon = Salon::factory()->create(['slug' => 'course-race-'.uniqid()]);
        app(CurrentSalon::class)->set($this->salon);
        $this->setUpCourseWorld();
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            $id = $this->salon->id;
            $staffUsers = DB::table('specialists')->where('salon_id', $id)->pluck('user_id');
            Schema::disableForeignKeyConstraints();
            foreach (Schema::getTableListing() as $table) {
                $table = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
                if ($table !== 'salons' && Schema::hasColumn($table, 'salon_id')) {
                    DB::table($table)->where('salon_id', $id)->delete();
                }
            }
            DB::table('specialist_services')->whereNotIn('specialist_id', DB::table('specialists')->select('id'))->delete();
            DB::table('specialist_schedules')->whereNotIn('specialist_id', DB::table('specialists')->select('id'))->delete();
            User::whereKey($staffUsers)->delete();
            DB::table('salons')->where('id', $id)->delete();
            Schema::enableForeignKeyConstraints();
        }

        parent::tearDown();
    }

    public function test_the_last_session_cannot_be_booked_twice_at_the_same_moment(): void
    {
        $course = $this->paidCourse(['sessions_count' => 2, 'min_interval_days' => 0, 'interval_days' => 7]);
        $courses = app(CourseService::class);
        $courses->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(1, '09:00'));

        // درخواست دوم وقتی می‌رسد که درخواست اول دوره را قفل کرده و نوبتش هنوز commit نشده
        $fired = false;
        Event::listen('eloquent.creating: '.Booking::class, function () use (&$fired, $course) {
            if (! $fired) {
                $fired = true;
                $this->startConcurrent('book-course-session', (string) $course->id, (string) $this->courseSpecialist->id, $this->sessionAt(2, '15:00'));
            }
        });

        // روز دیگر (بسته‌ی 5ه: سقف روزانه‌ی مشتری) — این تست درباره‌ی آخرین جلسه‌ی دوره است، نه سقف روز
        $mine = $courses->bookSession($course->fresh(), $this->courseSpecialist->id, $this->sessionAt(3, '11:00'));
        $other = $this->finishConcurrent();

        $this->assertSame(2, (int) $mine->course_session_number);
        $this->assertSame('rejected', $other, 'جلسه‌ی دوم برای همان آخرین جلسه نباید رزرو شود');
        $this->assertSame(2, Booking::query()->where('customer_course_id', $course->id)->count());
    }
}
