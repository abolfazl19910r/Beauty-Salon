import { router } from 'expo-router';
import { View } from 'react-native';

import { useSession } from '@/lib/session';
import { Button, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

/** 403 salon_inactive: سالن معلق یا اشتراکش تمام — توکن باطل نمی‌شود؛ بعد از فعال شدن سالن «دوباره امتحان کن» */
export default function SalonInactive() {
  const { account, recheckSalon, signOut } = useSession();

  return (
    <Screen>
      <View style={{ marginTop: space(16), gap: space(4) }}>
        <T weight="bold" size={20} center>سالن فعلاً غیرفعال است</T>
        <T muted center>اشتراک «{account?.salon.name}» پایان یافته یا غیرفعال شده است. با مدیر سالن تماس بگیرید.</T>
        <Button title="دوباره امتحان کن" onPress={() => { recheckSalon(); router.replace('/'); }} />
        <Button kind="ghost" title="خروج از حساب" onPress={() => signOut()} />
      </View>
    </Screen>
  );
}
