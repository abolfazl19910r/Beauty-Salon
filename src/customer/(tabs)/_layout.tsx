import Ionicons from '@expo/vector-icons/Ionicons';
import { Redirect } from 'expo-router';
import { Tabs } from 'expo-router/js-tabs';

import { useSession } from '@/lib/session';
import { fonts, usePalette } from '@/ui/theme';

type IconName = React.ComponentProps<typeof Ionicons>['name'];

/** ترتیب زبانه‌ها (تصمیم ۲۰۲۶-۱۰-۱۴): خانه · رزرو · نوبت‌ها · باشگاه · حساب (کیف پول داخل «حساب») */
const TABS: { name: string; title: string; icon: IconName }[] = [
  { name: 'home', title: 'خانه', icon: 'home-outline' },
  { name: 'book', title: 'رزرو', icon: 'add-circle-outline' },
  { name: 'bookings', title: 'نوبت‌ها', icon: 'calendar-outline' },
  { name: 'club', title: 'باشگاه', icon: 'gift-outline' },
  { name: 'account', title: 'حساب', icon: 'person-circle-outline' },
];

export default function TabsLayout() {
  const { status, salonInactive } = useSession();
  const p = usePalette();

  if (status !== 'signedIn') {
    return <Redirect href="/" />;
  }
  if (salonInactive) {
    return <Redirect href="/salon-inactive" />;
  }

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: p.gold,
        tabBarInactiveTintColor: p.textMuted,
        tabBarStyle: { backgroundColor: p.surface, borderTopColor: p.border },
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
          options={{ title: tab.title, tabBarIcon: ({ color, size }) => <Ionicons name={tab.icon} color={color} size={size} /> }}
        />
      ))}
    </Tabs>
  );
}
