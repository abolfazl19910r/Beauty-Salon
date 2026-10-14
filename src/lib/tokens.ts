import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { useApi } from './session';
import type { DeviceToken } from './types';

/** دستگاه‌های واردشده (GET/DELETE /api/v1/tokens) — مشترک «ماهرو همکار» و «ماهرو» (بسته‌ی ۱) */
const TOKENS_KEY = ['tokens'] as const;

export function useTokens() {
  const api = useApi();
  return useQuery({ queryKey: TOKENS_KEY, queryFn: async () => (await api.get<DeviceToken[]>('/tokens')).data });
}

export function useTokenMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => void qc.invalidateQueries({ queryKey: TOKENS_KEY });
  return {
    revoke: useMutation({ mutationFn: async (id: number) => api.del(`/tokens/${id}`), onSuccess: refresh }),
    revokeOthers: useMutation({ mutationFn: async () => api.post<{ revoked: number }>('/tokens/revoke-others'), onSuccess: refresh }),
  };
}
