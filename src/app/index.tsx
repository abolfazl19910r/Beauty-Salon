import { Redirect } from 'expo-router';

import { useSession } from '@/lib/session';

export default function Index() {
  const { status, salonInactive } = useSession();

  if (status === 'signedOut') {
    return <Redirect href="/login" />;
  }
  if (salonInactive) {
    return <Redirect href="/salon-inactive" />;
  }
  return <Redirect href="/today" />;
}
