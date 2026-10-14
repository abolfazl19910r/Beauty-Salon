<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** ثبت یک یادآوری دوره (یکتا روی دوره + نوع + شماره‌ی جلسه) تا هرگز دو بار نرود */
class CourseReminder extends Model
{
    protected $fillable = ['customer_course_id', 'kind', 'session_number', 'sent_at', 'sms_parts'];

    protected $casts = ['sent_at' => 'datetime'];
}
