import Ionicons from '@expo/vector-icons/Ionicons';
import { Redirect } from 'expo-router';
import { Tabs } from 'expo-router/js-tabs';

import { useSession } from '@/lib/session';
import { fonts, usePalette } from '@/ui/theme';

type IconName = React.ComponentProps<typeof Ionicons>['name'];

const TABS: { name: string; title: string; icon: IconName }[] = [
  { name: 'today', title: 'امروز', icon: 'sunny-outline' },
  { name: 'bookings', title: 'نوبت‌ها', icon: 'list-outline' },
  { name: 'calendar', title: 'تقویم', icon: 'calendar-outline' },
  { name: 'wallet', title: 'کیف پول', icon: 'wallet-outline' },
  { name: 'more', title: 'بیشتر', icon: 'ellipsis-horizontal-circle-outline' },
];

export default function TabsLayout() {
  const { status, salonInactive } = useSession();
  const p = usePalette();

  if (status !== 'signedIn') {
    return <Redirect href="/login" />;
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
