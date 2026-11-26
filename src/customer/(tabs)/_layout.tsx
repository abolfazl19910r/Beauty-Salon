import Ionicons from '@expo/vector-icons/Ionicons';
import { Redirect } from 'expo-router';
import { Tabs } from 'expo-router/js-tabs';
import { View } from 'react-native';

import { useBookingsTab } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { useSession } from '@/lib/session';
import { fonts, usePalette } from '@/ui/theme';

type IconName = React.ComponentProps<typeof Ionicons>['name'];

/**
 * زبانه‌ها — بسته‌ی ۵د (تصمیم ۱۱-۲۶، جای تصمیم ۱۰-۱۴): مثل منوی پایین وب «خانه / نوبت‌ها / [+ رزرو] / کیف پول / حساب»؛
 * دکمه‌ی گرد وسط برای رزرو، نشان شمار نوبت‌های پیش رو. باشگاه از کارت خانه و «حساب» باز می‌شود.
 */
const TABS: { name: string; title: string; icon: IconName }[] = [
  { name: 'home', title: 'خانه', icon: 'home-outline' },
  { name: 'bookings', title: 'نوبت‌ها', icon: 'calendar-outline' },
  { name: 'book', title: 'رزرو', icon: 'add' },
  { name: 'wallet', title: 'کیف پول', icon: 'wallet-outline' },
  { name: 'account', title: 'حساب', icon: 'person-circle-outline' },
];

export default function TabsLayout() {
  const { status, salonInactive } = useSession();
  const p = usePalette();
  const upcoming = (useBookingsTab('upcoming', status === 'signedIn' && !salonInactive).data?.pages[0]?.meta as { counts?: { upcoming?: number } } | undefined)?.counts?.upcoming ?? 0;

  if (status !== 'signedIn') {
    return <Redirect href="/" />;
  }
  if (salonInactive) {
    return <Redirect href="/salon-inactive" />;
  }

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: p.text,
        tabBarInactiveTintColor: p.textMuted,
        tabBarStyle: { backgroundColor: p.surface, borderTopColor: p.border, height: 64 },
        tabBarLabelStyle: { fontFamily: fonts.medium, fontSize: 11 },
        headerTitleStyle: { fontFamily: fonts.bold, fontSize: 17 },
        headerTitleAlign: 'center',
        headerStyle: { backgroundColor: p.surface },
        headerTintColor: p.text,
      }}
    >
      {TABS.map((tab) => (
        <Tabs.Screen
          key={tab.name}
          name={tab.name}
          options={{
            title: tab.title,
            headerShown: tab.name !== 'home',
            tabBarBadge: tab.name === 'bookings' && upcoming > 0 ? faDigits(upcoming) : undefined,
            tabBarBadgeStyle: { backgroundColor: p.gold, color: p.background, fontFamily: fonts.bold, fontSize: 10 },
            tabBarIcon: tab.name === 'book'
              ? () => (
                <View testID="tab-book" style={{ width: 52, height: 52, marginTop: -22, borderRadius: 26, backgroundColor: p.button, alignItems: 'center', justifyContent: 'center', elevation: 4 }}>
                  <Ionicons name="add" size={28} color={p.onGold} />
                </View>
              )
              : ({ color, size }) => <Ionicons name={tab.icon} color={color} size={size} />,
          }}
        />
      ))}
    </Tabs>
  );
}
