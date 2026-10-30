<?php

namespace App\Support;

/**
 * ⭐ ۲۰۲۶-۱۰-۳۱ (بند «5ج»، تصمیم ابوالفضل): قانون image فقط محتوا را می‌سنجد؛ PNG با نام photo.svg قبول می‌شد. پسوند هم باید
 * تصویر رایج باشد (SVG آپلودی در storage عمومی راه XSS است).
 */
final class ImageUpload
{
    public const EXTENSIONS = 'extensions:jpg,jpeg,png,webp';

    public const EXTENSION_MESSAGE = 'پسوند تصویر باید jpg، jpeg، png یا webp باشد.';
}
