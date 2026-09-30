<?php

namespace Tests\Feature\Repositories;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * «عضو سالن» = مشتری همین سالن، مدیر در salon_admins، کاربر متخصص همین سالن (با user_id) یا کاربر staff با تلفن یک متخصص
 * همین سالن. ۲۰۲۶-۰۹-۳۰: شرط OR/EXISTS کل users پلتفرم را اسکن می‌کرد (۱۰۰۰ سالن: ۸۶–۱۴۴ms) → UNION چهار منبع در جدول مشتق.
 * این تست هر چهار منبع، مرز سالن و ترکیب‌پذیری Builder را در هر دو شکل نگه می‌دارد.
 */
class SalonMembersQueryTest extends TestCase
{
    use RefreshDatabase;

    private function members(int $salonId): array
    {
        return app(UserRepositoryInterface::class)->querySalonMembers($salonId)->orderBy('users.id')->pluck('users.id')->all();
    }

    private function salonWithEveryKindOfMember(string $slug): array
    {
        $salon = Salon::factory()->create(['slug' => $slug]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '09121110000']);
        $owner = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        DB::table('salon_admins')->insert(['salon_id' => $salon->id, 'user_id' => $owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $linked = Specialist::factory()->create(['salon_id' => $salon->id]);
        $byPhone = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        Specialist::factory()->create(['salon_id' => $salon->id, 'user_id' => null, 'phone' => $byPhone->phone]);
        $trashed = Specialist::factory()->create(['salon_id' => $salon->id]);
        $trashed->delete();

        return [$salon, [$customer->id, $owner->id, $linked->user_id, $byPhone->id, $trashed->user_id]];
    }

    public function test_members_are_exactly_the_four_sources_of_this_salon(): void
    {
        [$a, $membersA] = $this->salonWithEveryKindOfMember('members-a');
        [$b, $membersB] = $this->salonWithEveryKindOfMember('members-b'); // هم‌شماره‌ی مشتری سالن a
        User::factory()->create(['user_type' => 'staff', 'salon_id' => null]); // کارمندی که به هیچ سالنی وصل نیست

        sort($membersA);
        sort($membersB);
        $this->assertSame($membersA, $this->members($a->id));
        $this->assertSame($membersB, $this->members($b->id));
    }

    public function test_the_query_composes_like_the_callers_use_it(): void
    {
        [$a, $membersA] = $this->salonWithEveryKindOfMember('members-a');
        [$b, $membersB] = $this->salonWithEveryKindOfMember('members-b');
        $repo = app(UserRepositoryInterface::class);

        $this->assertSame(5, $repo->querySalonMembers($a->id)->count());
        $this->assertTrue($repo->isSalonMember(User::find($membersA[0]), $a->id));
        $this->assertFalse($repo->isSalonMember(User::find($membersB[0]), $a->id));
        $this->assertSame(1, $repo->querySalonMembers($a->id)->where('users.user_type', 'customer')->count());
        // subquery در whereIn (نقش‌ها، امنیت)
        $this->assertSame(5, User::whereIn('users.id', $repo->querySalonMembers($a->id)->select('users.id'))->count());
    }
}
