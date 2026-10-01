<?php

namespace App\Support;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\SendQueuedNotifications;

/**
 * سالنِ یک job یا اعلان صف‌شده (حالت سخت‌گیر BelongsToSalon، ۲۰۲۶-۱۰-۰۱). worker سالن جاری ندارد؛ بدون این، خواندن
 * رابطه‌ها (مثلاً $booking->service در متن اعلان) خطای MissingSalonContextException می‌داد. ترتیب:
 * ۱) متد salonId() روی job یا اعلان؛ ۲) settingsSalonId() اعلان (سالنِ رکوردی که اعلان درباره‌ی آن است)؛
 * ۳) اولین property که مدل Salon یا مدلی با salon_id است. jobهایی که فقط شناسه دارند (ProcessWithdrawalJob،
 * GeneratePdfReportJob) سالن را خودشان از رکورد ست می‌کنند.
 */
final class SalonOfQueuedJob
{
    public static function resolve(object $command): ?int
    {
        $target = $command instanceof SendQueuedNotifications ? $command->notification : $command;

        foreach ([$command, $target] as $candidate) {
            if (method_exists($candidate, 'salonId') && ($id = $candidate->salonId())) {
                return (int) $id;
            }
        }

        if (method_exists($target, 'settingsSalonId')) {
            $id = (fn () => $this->settingsSalonId())->call($target);
            if ($id) {
                return (int) $id;
            }
        }

        foreach ((new \ReflectionObject($target))->getProperties() as $property) {
            $value = $property->getValue($target);
            if ($value instanceof Salon) {
                return $value->id;
            }
            if ($value instanceof Model && ! empty($value->getAttributes()['salon_id'])) {
                return (int) $value->getAttributes()['salon_id'];
            }
        }

        return null;
    }
}
