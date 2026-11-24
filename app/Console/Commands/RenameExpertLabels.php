<?php

namespace App\Console\Commands;

use App\Support\Text\ExpertTerm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * یک بار بعد از deploy بسته‌ی ۰ (۲۰۲۶-۱۱-۲۴): برچسب‌های ذخیره‌شده‌ی سیستمی در دیتابیس («متخصصان» در گروه و نام مجوزها، نقش
 * سیستمی «متخصص/پشتیبان»، توضیح پلن‌ها) به «کارشناس/کارشناسان». نقش‌هایی که خود سالن ساخته (salon_id دارد) و متن‌هایی که سالن نوشته
 * (پیامک سفارشی، بلاگ، گالری) دست نمی‌خورند. migrationها فقط ساختارند، پس این کار با دستور است. چند بار اجرا بی‌خطر است.
 */
class RenameExpertLabels extends Command
{
    protected $signature = 'labels:expert-rename {--dry-run : فقط شمار ردیف‌ها، بدون تغییر}';

    protected $description = 'Rename the stored system labels from «متخصص» to «کارشناس» (UI refresh package 0)';

    public function handle(): int
    {
        $targets = [
            'permissions' => [['label', 'group', 'description'], fn ($q) => $q],
            'roles' => [['label'], fn ($q) => $q->whereNull('salon_id')],
            // توضیح پلن‌ها («… ۱۰ متخصص و ۲٬۰۰۰ پیامک») که DefaultPlans ساخته و در لندینگ و صفحه‌ی خرید دیده می‌شود
            'plans' => [['description'], fn ($q) => $q],
        ];

        $total = 0;
        foreach ($targets as $table => [$columns, $scope]) {
            $rows = $scope(DB::table($table))->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.ExpertTerm::OLD.'%');
                }
            })->get(array_merge(['id'], $columns));

            foreach ($rows as $row) {
                $changes = [];
                foreach ($columns as $column) {
                    $new = ExpertTerm::replace($row->{$column});
                    if ($new !== $row->{$column}) {
                        $changes[$column] = $new;
                    }
                }
                if ($changes !== [] && ! $this->option('dry-run')) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
                $total += $changes !== [] ? 1 : 0;
            }
            $this->line(sprintf('%s: %d ردیف', $table, $rows->count()));
        }

        $this->info(sprintf('%s %d ردیف.', $this->option('dry-run') ? 'تغییر می‌کند:' : 'به‌روز شد:', $total));

        return self::SUCCESS;
    }
}
