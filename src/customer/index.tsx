import { Redirect } from 'expo-router';

import { useCustomerSalon } from '@/lib/customer/salon';
import { useSession } from '@/lib/session';

export default function Index() {
  const { status, salonInactive } = useSession();
  const { salon } = useCustomerSalon();

  if (status === 'signedOut') {
    return <Redirect href={salon ? '/login' : '/welcome'} />;
  }
  if (salonInactive) {
    return <Redirect href="/salon-inactive" />;
  }
  return <Redirect href="/home" />;
}
