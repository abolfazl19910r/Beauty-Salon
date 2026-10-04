// خواندن پیام آزمایشی از اعلان. وقتی اپ بسته بوده، اعلان را خود اندروید ساخته و data در دسترس نیست؛
// برای همین سرور شناسه و زمان ارسال را با برچسب [P:<id>:<ms>] داخل متن هم می‌گذارد.
import type { Notification } from 'expo-notifications';

export type ReceiptSource = 'اپ باز' | 'سینی' | 'لمس';

export interface ProbeEntry {
  id: string;
  via: string;
  label: string;
  sentAtMs: number;
  receivedAtMs: number;
  source: ReceiptSource;
}

const MARKER = /\[P:([A-Za-z0-9]+-\d+):(\d{13})\]/;

export function parseProbe(notification: Notification, source: ReceiptSource): Omit<ProbeEntry, 'source'> | null {
  const content = notification.request.content;
  const data = (content.data ?? {}) as Record<string, unknown>;
  const body = content.body ?? '';
  const match = MARKER.exec(body);

  const id = typeof data.probe_id === 'string' ? data.probe_id : match?.[1];
  const sentAt = typeof data.sent_at_ms === 'string' ? Number(data.sent_at_ms) : match ? Number(match[2]) : NaN;
  if (!id || !Number.isFinite(sentAt)) return null;

  // متن: «مسیر FCM · ارسال 14:32:07 · برچسب [P:...]»
  const parts = body.replace(MARKER, '').split(' · ').map((p) => p.trim());
  const via = typeof data.via === 'string' ? data.via : (parts[0] ?? '').replace('مسیر ', '').toLowerCase();
  const label = typeof data.label === 'string' ? data.label : (parts[2] ?? '');

  // notification.date برای پیامی که اپ باز گرفته «زمان ارسال» گوگل است (sentTime)، نه زمان رسیدن؛ پس همان لحظه را ثبت می‌کنیم.
  // برای اعلان سینی، date همان postTime اندروید است (روی MIUI ممکن است دیرتر از نمایش واقعی باشد: کران بالا).
  const receivedAtMs = source === 'سینی' ? notification.date || Date.now() : Date.now();
  return { id, via, label, sentAtMs: sentAt, receivedAtMs };
}

/** هر پیام یک ردیف؛ زودترین زمان رسیدن نگه داشته می‌شود («لمس» زمان رسیدن واقعی نیست، پس اولویت ندارد). */
export function mergeEntry(list: ProbeEntry[], parsed: Omit<ProbeEntry, 'source'>, source: ReceiptSource): ProbeEntry[] {
  const existing = list.find((e) => e.id === parsed.id);
  if (!existing) {
    return [{ ...parsed, source }, ...list].sort((a, b) => b.sentAtMs - a.sentAtMs);
  }
  if (source !== 'لمس' && (existing.source === 'لمس' || parsed.receivedAtMs < existing.receivedAtMs)) {
    return list.map((e) => (e.id === parsed.id ? { ...parsed, source } : e));
  }
  return list;
}

export function formatTime(ms: number): string {
  const d = new Date(ms);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

export function toCsv(entries: ProbeEntry[], device: string): string {
  const rows = entries.map((e) =>
    [e.id, e.via, e.label, formatTime(e.sentAtMs), formatTime(e.receivedAtMs), ((e.receivedAtMs - e.sentAtMs) / 1000).toFixed(1), e.source].join(','),
  );
  return [`device,${device}`, 'probe_id,via,label,sent,received,delay_s,source', ...rows].join('\n');
}
