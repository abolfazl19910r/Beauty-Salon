import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';

import type { Account } from './types';

const TOKEN_KEY = 'mahru.token';
const ACCOUNT_KEY = 'mahru.account';
const SERVER_KEY = 'mahru.server';

/** توکن فقط در SecureStore (Keystore اندروید)، نه AsyncStorage */
export async function saveSession(token: string, account: Account): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  await AsyncStorage.setItem(ACCOUNT_KEY, JSON.stringify(account));
}

export async function loadSession(): Promise<{ token: string; account: Account } | null> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  const raw = await AsyncStorage.getItem(ACCOUNT_KEY);
  if (!token || !raw) {
    return null;
  }
  try {
    return { token, account: JSON.parse(raw) as Account };
  } catch {
    return null;
  }
}

export async function clearSession(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  await AsyncStorage.removeItem(ACCOUNT_KEY);
}

export async function loadServerOverride(): Promise<string | null> {
  return AsyncStorage.getItem(SERVER_KEY);
}

export async function saveServerOverride(url: string | null): Promise<void> {
  if (url) {
    await AsyncStorage.setItem(SERVER_KEY, url);
  } else {
    await AsyncStorage.removeItem(SERVER_KEY);
  }
}
