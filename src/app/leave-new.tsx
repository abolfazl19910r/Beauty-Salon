import { router } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/lib/api';
import { isoDate } from '@/lib/jalali';
import { useLeaveMutations } from '@/lib/queries';
import { JalaliDateField } from '@/ui/JalaliDatePicker';
import { Button, ErrorBox, Field, Notice, Screen } from '@/ui/kit';

export default function LeaveNew() {
  const { create } = useLeaveMutations();
  const today = isoDate(new Date());
  const [start, setStart] = useState<string | null>(null);
  const [end, setEnd] = useState<string | null>(null);
  const [reason, setReason] = useState('');
  const fieldErrors = create.error instanceof ApiError ? create.error : null;

  return (
    <Screen>
      <Notice text="مرخصی بعد از تأیید مدیر سالن ثبت می‌شود. روزهایی که نوبت دارند قابل درخواست نیستند." />
      <JalaliDateField label="از تاریخ" value={start} minDate={today} onChange={(d) => { setStart(d); if (!end || end < d) setEnd(d); }} />
      <JalaliDateField label="تا تاریخ" value={end} minDate={start ?? today} onChange={setEnd} />
      <Field label="دلیل (اختیاری)" value={reason} onChangeText={setReason} maxLength={255} multiline error={fieldErrors?.field('reason')} />
      {create.error ? <ErrorBox error={create.error} /> : null}
      <Button
        title="ثبت درخواست"
        disabled={!start || !end}
        loading={create.isPending}
        onPress={() => start && end && create.mutate({ start_date: start, end_date: end, reason: reason.trim() || undefined }, { onSuccess: () => router.back() })}
      />
    </Screen>
  );
}
