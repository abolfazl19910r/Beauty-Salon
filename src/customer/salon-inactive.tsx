import { router } from 'expo-router';
import { View } from 'react-native';

import { useSession } from '@/lib/session';
import { Button, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

/** 403 salon_inactive: سالن معلق یا اشتراکش تمام — توکن باطل نمی‌شود؛ بعد از فعال شدن «دوباره امتحان کن» */
export default function SalonInactive() {
  const { account, recheckSalon, signOut } = useSession();

  return (
    <Screen>
      <View style={{ marginTop: space(16), gap: space(4) }}>
        <T weight="bold" size={20} center>نوبت‌دهی آنلاین فعلاً بسته است</T>
        <T muted center>«{account?.salon.name}» فعلاً نوبت آنلاین نمی‌پذیرد. برای نوبت با خود سالن تماس بگیرید.</T>
        <Button title="دوباره امتحان کن" onPress={() => { recheckSalon(); router.replace('/'); }} />
        <Button kind="ghost" title="خروج از حساب" onPress={() => signOut()} />
      </View>
    </Screen>
  );
}
