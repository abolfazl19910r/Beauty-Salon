<?php

namespace App\Support\Courses;

/**
 * کلاس‌ها و styleهای مشترک صفحه‌های دوره‌ی درمانی (بسته‌ی ب). متغیرهای یک @include به view والد برنمی‌گردند، پس هر صفحه با
 *
 * @php extract(CourseUi::customer()) @endphp آن‌ها را می‌گیرد.
 */
final class CourseUi
{
    /** @return array<string, string> وب مشتری */
    public static function customer(): array
    {
        return [
            'card' => 'background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);',
            'inner' => 'background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.15);',
            'gold' => 'color: var(--rasta-gold-light);',
            'dim' => 'color: var(--rasta-cream); opacity: 0.65;',
            'btn' => 'background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);',
            'ghost' => 'border: 1px solid rgba(201,162,75,0.35); color: var(--rasta-gold-light);',
        ];
    }

    /** @return array<string, string> پنل مدیر */
    public static function admin(): array
    {
        return [
            'input' => 'w-full rounded-lg px-3 py-2 text-sm',
            'inputStyle' => 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);',
            'box' => 'background:var(--admin-surface); border:1px solid var(--admin-border);',
            'dim' => 'color:var(--admin-text-dim);',
            'btn' => 'background:var(--admin-accent); color:#fff;',
            'ghost' => 'border:1px solid var(--admin-border); color:var(--admin-text);',
        ];
    }
}
