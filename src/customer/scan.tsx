import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRef, useState } from 'react';
import { View } from 'react-native';

import { parseSalonInput } from '@/lib/customer/salonLink';
import { useOpenSalon } from '@/lib/customer/useOpenSalon';
import { Button, ErrorBox, Loading, Notice, Screen, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

/** اسکن QR کارت «آدرس اختصاصی سالن» پنل مدیر (لینک /s/{slug} یا ساب‌دامین). */
export default function Scan() {
  const p = usePalette();
  const [permission, requestPermission] = useCameraPermissions();
  const { open, busy, error } = useOpenSalon();
  const [wrongCode, setWrongCode] = useState(false);
  const handled = useRef<string | null>(null);

  if (!permission) {
    return <Screen><Loading /></Screen>;
  }

  if (!permission.granted) {
    return (
      <Screen>
        <T>برای اسکن QR سالن، اجازه‌ی دوربین لازم است. اپ از دوربین فقط برای خواندن همین QR استفاده می‌کند.</T>
        <Button title="اجازه‌ی دوربین" onPress={() => requestPermission()} />
        {!permission.canAskAgain ? <Notice tone="warning" text="اجازه قبلاً رد شده است؛ از تنظیمات گوشی برای «ماهرو» اجازه‌ی دوربین بدهید، یا لینک/کد سالن را دستی وارد کنید." /> : null}
      </Screen>
    );
  }

  const onScanned = async ({ data }: { data: string }) => {
    if (busy || handled.current === data) {
      return;
    }
    handled.current = data;
    const parsed = parseSalonInput(data);
    if (!parsed) {
      setWrongCode(true);
      return;
    }
    setWrongCode(false);
    const salon = await open(parsed);
    if (!salon) {
      // اجازه‌ی اسکن دوباره‌ی همان کد بعد از خطا (مثلاً شبکه)
      handled.current = null;
    }
  };

  return (
    <Screen scroll={false}>
      <View style={{ flex: 1, borderRadius: radius.lg, overflow: 'hidden', backgroundColor: '#000' }}>
        <CameraView style={{ flex: 1 }} facing="back" barcodeScannerSettings={{ barcodeTypes: ['qr'] }} onBarcodeScanned={onScanned} />
        <View pointerEvents="none" style={{ position: 'absolute', top: '25%', left: '15%', right: '15%', aspectRatio: 1, borderColor: p.gold, borderWidth: 3, borderRadius: radius.lg }} />
      </View>
      <View style={{ gap: space(2) }}>
        {busy ? <Loading label="در حال پیدا کردن سالن…" /> : <T muted center>QR سالن را داخل کادر بگیرید.</T>}
        {wrongCode ? <Notice tone="warning" text="این QR مال یک سالن ماهرو نیست." /> : null}
        {error ? <ErrorBox error={error} /> : null}
      </View>
    </Screen>
  );
}
