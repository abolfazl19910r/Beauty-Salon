<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // ⭐ Customer identity redesign (see "🔴 بازطراحی هویت مشتری" in
            // Rasta_unified_prompt.md): only customer accounts become per-salon; admin/specialist
            // accounts keep globally-unique phone behavior untouched, since they connect to a
            // salon through salon_admins / specialists.salon_id already, not through this column.
            // Deliberately no onDelete() action (plain RESTRICT, the schema default) — a STORED
            // generated column below (customer_salon_phone_key) references salon_id, and
            // MySQL/MariaDB refuse to let a STORED generated column reference any column whose
            // foreign key has an ON DELETE/ON UPDATE action that could change its value out from
            // under the generated computation (confirmed against a real MySQL/MariaDB server,
            // error 1901 — this project's test suite runs on SQLite and never enforces this).
            $table->foreignId('salon_id')->nullable()->constrained('salons');
            // `user_type` exists ONLY because a generated column (needed for the split unique
            // constraints below, same MySQL-has-no-partial-unique-index situation as
            // bookings.active_slot_key) can only reference columns on its own row — it cannot
            // join out to `specialists` to check "does this user have a specialist record", which
            // is how "customer" is actually defined everywhere else in this codebase (is_admin=
            // false AND no linked Specialist). This column is a persisted, same-row-only stand-in
            // for that check, kept in sync at the two points a User's nature changes:
            // registration (always 'customer') and admin/specialist creation.
            $table->enum('user_type', ['staff', 'customer'])->default('customer');
            $table->string('name');
            $table->string('phone');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            // password_changed_at: read by SecurityController; password_strength_score is
            // calculated only once, at the moment of registration/password change (when the raw
            // password itself is still available, before it is hashed) and stored here — it must
            // never be rebuilt from the hash later, since a bcrypt hash is always 60 characters of
            // mixed-case/numbers/special characters and would always score itself as nearly
            // maximum strength regardless of the actual password's real strength.
            $table->timestamp('password_changed_at')->nullable();
            $table->unsignedTinyInteger('password_strength_score')->nullable();
            $table->boolean('is_admin')->default(false);
            // Backing column for TwoFactorAuthService::isEnabled()/enable()/disable() and
            // SecurityController's 2FA flow.
            $table->boolean('two_factor_enabled')->default(false);
            // Dedicated OTP pair for the 2FA flow, deliberately separate from
            // login_verification_code/login_verification_code_expire_at below (the login-OTP
            // pair) so the two flows never clobber each other's codes.
            // ⭐ طول ۱۰ (نه ۶): TWO_FACTOR_CODE_LENGTH تا ۱۰ رقم مجاز است (TwoFactorAuthService) — روی MySQL کد بلندتر
            // از ستون ذخیره نمی‌شد (۲۰۲۶-۰۹-۲۷).
            $table->string('two_factor_code', 10)->nullable();
            $table->timestamp('two_factor_code_expires_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expire_at')->nullable();
            $table->string('login_verification_code', 6)->nullable();
            $table->timestamp('login_verification_code_expire_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            // انصراف مشتری از پیامک‌های تبلیغاتی (پیگیری خودکار مشتریان غایب، ۲۰۲۶-۱۰-۱۴)
            $table->timestamp('marketing_sms_opted_out_at')->nullable();
            // بسته‌ی 5ه، تصمیم ۱۶: «برداشتن محدودیت» مشتری بدقول توسط مدیر؛ «نیامد»های پیش از این لحظه در شمارش بدقولی حساب نمی‌شوند
            $table->timestamp('no_show_cleared_at')->nullable();

            $table->index('two_factor_enabled');
        });

        // ⭐ Split unique constraint (Postgres has real partial unique indexes; MySQL/SQLite need
        // a generated-column stand-in): a 'staff' phone must be globally unique across the whole
        // platform, but a 'customer' phone only needs to be unique within their own salon (the
        // same phone number can belong to different customers at different salons).
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX users_staff_phone_unique ON users (phone) WHERE user_type = 'staff'");
            DB::statement("CREATE UNIQUE INDEX users_customer_salon_phone_unique ON users (salon_id, phone) WHERE user_type = 'customer'");
        } else {
            $concat = $driver === 'sqlite'
                ? "salon_id || '_' || phone"
                : "CONCAT(salon_id, '_', phone)";

            Schema::table('users', function (Blueprint $table) use ($concat) {
                // after(): همان جای پیش از ادغام سوم migrationها (روی MySQL/MariaDB؛ SQLite ستون را ته جدول می‌گذارد)
                $table->string('staff_phone_key', 20)
                    ->storedAs("CASE WHEN user_type = 'staff' THEN phone ELSE NULL END")
                    ->after('updated_at');
                $table->string('customer_salon_phone_key', 191)
                    ->storedAs("CASE WHEN user_type = 'customer' THEN {$concat} ELSE NULL END")
                    ->after('staff_phone_key');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->unique('staff_phone_key', 'users_staff_phone_unique');
                $table->unique('customer_salon_phone_key', 'users_customer_salon_phone_unique');
            });
        }

        // ⭐ salons.created_by و salons.paused_by_user_id به users اشاره می‌کنند و users.salon_id به salons — وابستگی دوری. salons قبل از
        // users ساخته می‌شود (0000_01_01_000000) و این دو ستون همین‌جا، بلافاصله بعد از ساخت users، اضافه می‌شوند.
        Schema::table('salons', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            // توقف موقت رزرو (بسته‌ی «ح»، ۸): ابرمدیر یا مدیری که رزرو را بست
            $table->foreignId('paused_by_user_id')->nullable()->after('paused_by')->constrained('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by_user_id');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
    }
};
