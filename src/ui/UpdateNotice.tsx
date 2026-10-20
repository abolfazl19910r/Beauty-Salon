import Constants from 'expo-constants';
import { Linking, Modal, View } from 'react-native';

import { useAppUpdate } from '@/lib/appUpdate';
import { faDigits } from '@/lib/format';
import { Button, Card, T } from './kit';
import { space, usePalette } from './theme';

const APP_NAME = Constants.expoConfig?.name ?? 'ماهرو';

function sizeLabel(bytes: number): string {
  return `${faDigits((bytes / 1048576).toFixed(bytes < 10 * 1048576 ? 1 : 0))} مگابایت`;
}

/**
 * «نسخه‌ی تازه آمده» (بسته‌ی ۵) — کارت روی صفحه‌ی خانه. نسخه‌ی اجباری را UpdateGate (ریشه‌ی اپ) نشان می‌دهد.
 */
export function UpdateNotice() {
  const update = useAppUpdate();
  const latest = update.data?.latest;
  if (!update.data?.update_available || !latest || update.data.update_required) {
    return null;
  }

  return (
    <Card>
      <T weight="bold">نسخه‌ی تازه‌ی {APP_NAME} آمده ({faDigits(latest.version_name)})</T>
      {latest.notes ? <T muted size={13}>{latest.notes}</T> : null}
      <Button small title={`دانلود و نصب (${sizeLabel(latest.size_bytes)})`} onPress={() => void Linking.openURL(latest.download_url)} />
    </Card>
  );
}

/**
 * نسخه‌ی اجباری (بسته‌ی ۵): تا به‌روز نشود اپ ادامه نمی‌دهد (برای تغییر ناسازگار سرور). روی همه‌ی صفحه‌ها، در ریشه‌ی اپ.
 */
export function UpdateGate() {
  const update = useAppUpdate();
  const p = usePalette();
  const latest = update.data?.latest;
  if (!update.data?.update_required || !latest) {
    return null;
  }

  return (
    <Modal visible transparent={false} animationType="fade" onRequestClose={() => {}}>
      <View style={{ flex: 1, backgroundColor: p.background, justifyContent: 'center', padding: space(6), gap: space(4) }}>
        <T weight="bold" size={20} center>به‌روزرسانی {APP_NAME} لازم است</T>
        <T muted center>
          نسخه‌ی {faDigits(latest.version_name)} منتشر شده و این نسخه دیگر کار نمی‌کند. فایل تازه را دانلود و نصب کنید؛ اطلاعات و ورودتان
          سر جایش می‌ماند.
        </T>
        {latest.notes ? <T center size={13}>{latest.notes}</T> : null}
        <Button title={`دانلود نسخه‌ی تازه (${sizeLabel(latest.size_bytes)})`} onPress={() => void Linking.openURL(latest.download_url)} />
        <Button kind="ghost" title="صفحه‌ی دانلود و راهنمای نصب" onPress={() => void Linking.openURL(latest.page_url)} />
      </View>
    </Modal>
  );
}
