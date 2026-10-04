// اپ آزمایشی پوش ماهرو — بسته‌ی ۰ اپلیکیشن (۲۰۲۶-۱۰-۰۲). فقط برای آزمایش؛ هیچ ربطی به اپ‌های اصلی ندارد.
// کار اپ: گرفتن توکن FCM، ساختن دستور ارسال برای سیستم، و ثبت این‌که هر پیام آزمایشی کی و در چه حالتی رسید.
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { StatusBar } from 'expo-status-bar';
import { useCallback, useEffect, useState } from 'react';
import { AppState, Linking, Platform, Pressable, ScrollView, Share, StyleSheet, Text, TextInput, View } from 'react-native';

import { ntfySubscribeLink, randomTopic } from './src/ntfy';
import { formatTime, mergeEntry, parseProbe, ProbeEntry, ReceiptSource, toCsv } from './src/probe';

const CHANNEL_ID = 'probe';
const LOG_KEY = 'push-probe-log-v1';
const NTFY_KEY = 'push-probe-ntfy-v1';
const STATES = ['باز', 'پس‌زمینه', 'بسته', 'بسته-باتری', 'ریستارت', 'قطعی'] as const;

// وقتی اپ باز است اعلان هم نشان داده شود (پیش‌فرض اندروید نشان نمی‌دهد)
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

function deviceShortName(): string {
  const model = `${Device.manufacturer ?? ''} ${Device.modelName ?? ''}`.toLowerCase();
  if (model.includes('a17')) return 'A17';
  if (model.includes('note 8')) return 'RN8';
  return (Device.modelName ?? 'phone').replace(/\s+/g, '');
}

export default function App() {
  const [permission, setPermission] = useState<string>('…');
  const [token, setToken] = useState<string | null>(null);
  const [tokenInfo, setTokenInfo] = useState<string>('');
  const [state, setState] = useState<(typeof STATES)[number]>('باز');
  const [entries, setEntries] = useState<ProbeEntry[]>([]);
  const [ntfyServer, setNtfyServer] = useState('');
  const [topic, setTopic] = useState('');
  const [ntfyInfo, setNtfyInfo] = useState('');

  const record = useCallback(async (notification: Notifications.Notification, source: ReceiptSource) => {
    const parsed = parseProbe(notification, source);
    if (!parsed) return;
    const stored = JSON.parse((await AsyncStorage.getItem(LOG_KEY)) ?? '[]') as ProbeEntry[];
    const next = mergeEntry(stored, parsed, source);
    await AsyncStorage.setItem(LOG_KEY, JSON.stringify(next));
    setEntries(next);
  }, []);

  // اعلان‌هایی که وقتی اپ بسته/پس‌زمینه بود رسیده‌اند هنوز در سینی‌اند؛ ساعت رسیدنشان را اندروید نگه داشته است
  const scanTray = useCallback(async () => {
    const presented = await Notifications.getPresentedNotificationsAsync();
    for (const notification of presented) {
      await record(notification, 'سینی');
    }
  }, [record]);

  useEffect(() => {
    (async () => {
      if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync(CHANNEL_ID, {
          name: 'آزمایش پوش',
          importance: Notifications.AndroidImportance.MAX,
          vibrationPattern: [0, 250, 250, 250],
          lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
        });
      }
      setPermission((await Notifications.getPermissionsAsync()).status);
      setEntries(JSON.parse((await AsyncStorage.getItem(LOG_KEY)) ?? '[]'));
      // تاپیک یک بار ساخته و نگه داشته می‌شود (تاپیک ntfy مثل آدرس است؛ تصادفی تا کسی حدس نزند)
      const savedNtfy = JSON.parse((await AsyncStorage.getItem(NTFY_KEY)) ?? 'null') as { server: string; topic: string } | null;
      const ntfy = savedNtfy ?? { server: '', topic: randomTopic() };
      if (!savedNtfy) await AsyncStorage.setItem(NTFY_KEY, JSON.stringify(ntfy));
      setNtfyServer(ntfy.server);
      setTopic(ntfy.topic);
      await scanTray();
      const last = await Notifications.getLastNotificationResponseAsync();
      if (last) await record(last.notification, 'لمس');
    })();

    const received = Notifications.addNotificationReceivedListener((n) => record(n, 'اپ باز'));
    const tapped = Notifications.addNotificationResponseReceivedListener((r) => record(r.notification, 'لمس'));
    const appState = AppState.addEventListener('change', (s) => {
      if (s === 'active') scanTray();
    });
    return () => {
      received.remove();
      tapped.remove();
      appState.remove();
    };
  }, [record, scanTray]);

  const askPermission = async () => {
    setPermission((await Notifications.requestPermissionsAsync()).status);
  };

  const fetchToken = async () => {
    const started = Date.now();
    setTokenInfo('در حال گرفتن توکن از گوگل…');
    try {
      const result = await Notifications.getDevicePushTokenAsync();
      setToken(String(result.data));
      setTokenInfo(`گرفته شد در ${((Date.now() - started) / 1000).toFixed(1)} ثانیه — ${formatTime(Date.now())}`);
    } catch (e) {
      // SERVICE_NOT_AVAILABLE معمولاً یعنی گوشی به سرویس‌های گوگل (FCM) وصل نمی‌شود
      setToken(null);
      setTokenInfo(`ناموفق بعد از ${((Date.now() - started) / 1000).toFixed(1)} ثانیه: ${String(e)}`);
    }
  };

  const label = `${deviceShortName()}-${state}`;
  const command = token ? `php artisan push:probe fcm ${token} --label=${label}` : '';
  const ntfyCommand = topic ? `php artisan push:probe ntfy ${topic} --label=${label}` : '';

  const saveNtfyServer = async (server: string) => {
    setNtfyServer(server);
    await AsyncStorage.setItem(NTFY_KEY, JSON.stringify({ server, topic }));
  };

  const subscribeInNtfy = async () => {
    const link = ntfySubscribeLink(ntfyServer, topic, `ماهرو ${deviceShortName()}`);
    if (!link) {
      setNtfyInfo('آدرس سرور ntfy را کامل بنویسید، مثل http://192.168.1.10:8090');
      return;
    }
    try {
      await Linking.openURL(link);
      setNtfyInfo(`باز شد: ${link}`);
    } catch {
      setNtfyInfo('اپ ntfy نصب نیست (از F-Droid یا GitHub نصب کنید) — یا تاپیک را دستی در آن اضافه کنید.');
    }
  };

  const clearLog = async () => {
    await AsyncStorage.removeItem(LOG_KEY);
    await Notifications.dismissAllNotificationsAsync();
    setEntries([]);
  };

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.h1}>آزمایش پوش ماهرو</Text>
        <Text style={styles.muted}>
          {Device.manufacturer} {Device.modelName} — اندروید {Device.osVersion}
        </Text>

        <Section title="۱. اجازه‌ی اعلان">
          <Text style={styles.text}>وضعیت: {permission === 'granted' ? 'داده شده ✓' : permission}</Text>
          {permission !== 'granted' && <Button title="درخواست اجازه" onPress={askPermission} />}
        </Section>

        <Section title="۲. توکن FCM">
          <Button title={token ? 'گرفتن دوباره' : 'گرفتن توکن'} onPress={fetchToken} />
          {!!tokenInfo && <Text style={styles.muted}>{tokenInfo}</Text>}
          {token && <Text selectable style={styles.mono}>{token}</Text>}
        </Section>

        <Section title="۳. حالت آزمایش">
          <View style={styles.row}>
            {STATES.map((s) => (
              <Pressable key={s} onPress={() => setState(s)} style={[styles.chip, s === state && styles.chipOn]}>
                <Text style={s === state ? styles.chipTextOn : styles.text}>{s}</Text>
              </Pressable>
            ))}
          </View>
          <Text style={styles.muted}>برچسب پیام: {label} — بعد از انتخاب، دستور را دوباره بفرستید.</Text>
          {token && (
            <>
              <Text selectable style={styles.mono}>{command}</Text>
              <Button title="فرستادن دستور به سیستم (اشتراک)" onPress={() => Share.share({ message: command })} />
            </>
          )}
        </Section>

        <Section title="۴. ntfy (راه بدون گوگل)">
          <Text style={styles.muted}>
            پیام‌های ntfy را اپ ntfy نشان می‌دهد، نه این اپ؛ زمان رسیدنشان را از اعلان بخوانید (ساعت ارسال داخل متن است).
          </Text>
          <TextInput
            value={ntfyServer}
            onChangeText={saveNtfyServer}
            placeholder="http://192.168.1.10:8090"
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="url"
            style={styles.input}
          />
          <Text style={styles.text}>تاپیک: {topic}</Text>
          <Button title="اشتراک تاپیک در اپ ntfy" onPress={subscribeInNtfy} />
          {!!ntfyInfo && <Text style={styles.muted}>{ntfyInfo}</Text>}
          {!!ntfyCommand && (
            <>
              <Text selectable style={styles.mono}>{ntfyCommand}</Text>
              <Button title="فرستادن دستور ntfy به سیستم" onPress={() => Share.share({ message: ntfyCommand })} />
            </>
          )}
        </Section>

        <Section title={`۵. پیام‌های رسیده‌ی FCM (${entries.length})`}>
          <View style={styles.row}>
            <Button title="بررسی سینی اعلان‌ها" onPress={scanTray} />
            <Button title="اشتراک نتایج" onPress={() => Share.share({ message: toCsv(entries, label) })} />
            <Button title="پاک کردن" onPress={clearLog} />
          </View>
          {entries.length === 0 && <Text style={styles.muted}>هنوز پیامی نرسیده است.</Text>}
          {entries.map((e) => (
            <View key={e.id} style={styles.entry}>
              <Text style={styles.text}>
                #{e.id} · {e.via} · {e.label || '—'}
              </Text>
              <Text style={styles.muted}>
                ارسال {formatTime(e.sentAtMs)} ← رسید {formatTime(e.receivedAtMs)} ({e.source}) · تأخیر{' '}
                {((e.receivedAtMs - e.sentAtMs) / 1000).toFixed(1)} ثانیه
              </Text>
            </View>
          ))}
        </Section>
      </ScrollView>
    </View>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.section}>
      <Text style={styles.h2}>{title}</Text>
      {children}
    </View>
  );
}

function Button({ title, onPress }: { title: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.button, pressed && { opacity: 0.7 }]}>
      <Text style={styles.buttonText}>{title}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#faf6ef' },
  // اندروید جدید صفحه را تا لبه می‌کشد؛ فاصله‌ی پایین تا آخرین ردیف زیر نوار ناوبری نرود
  content: { padding: 16, paddingTop: 48, paddingBottom: 96, gap: 12 },
  h1: { fontSize: 22, fontWeight: '700', color: '#5a3e1b', textAlign: 'right', writingDirection: 'rtl' },
  h2: { fontSize: 16, fontWeight: '700', color: '#5a3e1b', textAlign: 'right', writingDirection: 'rtl', marginBottom: 8 },
  text: { fontSize: 14, color: '#2b2118', textAlign: 'right', writingDirection: 'rtl' },
  muted: { fontSize: 12, color: '#7a6a58', textAlign: 'right', writingDirection: 'rtl', marginTop: 4 },
  mono: { fontFamily: 'monospace', fontSize: 11, color: '#2b2118', backgroundColor: '#efe6d6', padding: 8, marginVertical: 6 },
  section: { backgroundColor: '#fff', borderRadius: 12, padding: 12, borderWidth: 1, borderColor: '#eadcc4' },
  row: { flexDirection: 'row-reverse', flexWrap: 'wrap', gap: 8 },
  chip: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 16, borderWidth: 1, borderColor: '#c9a86a' },
  chipOn: { backgroundColor: '#8a6a2f', borderColor: '#8a6a2f' },
  chipTextOn: { fontSize: 14, color: '#fff' },
  button: { backgroundColor: '#8a6a2f', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 8, alignSelf: 'flex-end', marginTop: 4 },
  buttonText: { color: '#fff', fontSize: 14 },
  input: { borderWidth: 1, borderColor: '#c9a86a', borderRadius: 8, padding: 8, marginVertical: 6, fontFamily: 'monospace', fontSize: 13, color: '#2b2118', textAlign: 'left' },
  entry: { borderTopWidth: 1, borderTopColor: '#f0e6d6', paddingVertical: 6 },
});
