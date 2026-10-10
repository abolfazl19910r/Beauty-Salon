/**
 * کلاینت /api/v1 — قالب بسته‌ی ۱ لاراول:
 *   موفق {success: true, data, meta?}، خطا {success: false, error: {code, message, fields?}, meta?}
 * هر خطا ApiError با همان code ماشینی است؛ پیام فارسی سرور مستقیم نمایش داده می‌شود.
 * خطای شبکه/زمان (سرور در دسترس نیست، قطعی) code «network_error» دارد.
 */

export type ApiMeta = Record<string, unknown>;

export class ApiError extends Error {
  constructor(
    public readonly code: string,
    message: string,
    public readonly status: number,
    public readonly fields: Record<string, string[]> | null = null,
    public readonly meta: ApiMeta = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }

  /** اولین پیام خطای یک فیلد (برای نمایش زیر ورودی) */
  field(name: string): string | undefined {
    return this.fields?.[name]?.[0];
  }
}

export const NETWORK_ERROR_MESSAGE = 'اتصال به سرور برقرار نشد. اینترنت یا آدرس سرور را بررسی کنید.';

type Query = Record<string, string | number | boolean | null | undefined>;

export type RequestOptions = { body?: unknown; query?: Query; auth?: boolean; timeoutMs?: number };

export type ClientConfig = {
  baseUrl: () => string;
  token: () => string | null;
  appVersion: string;
  /** پاسخ ۴۰۱ روی درخواست واردشده: نشست را پاک کن و به ورود برگرد */
  onUnauthorized?: () => void;
  fetchImpl?: typeof fetch;
};

export function buildUrl(baseUrl: string, path: string, query?: Query): string {
  const base = baseUrl.replace(/\/+$/, '');
  const qs = Object.entries(query ?? {})
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(typeof v === 'boolean' ? (v ? '1' : '0') : String(v))}`)
    .join('&');

  return `${base}/api/v1${path.startsWith('/') ? path : `/${path}`}${qs ? `?${qs}` : ''}`;
}

export function createClient(config: ClientConfig) {
  const doFetch = config.fetchImpl ?? fetch;

  async function request<T>(method: string, path: string, options: RequestOptions = {}): Promise<{ data: T; meta: ApiMeta }> {
    const { body, query, auth = true, timeoutMs = 20000 } = options;
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'X-App-Version': config.appVersion,
    };
    if (body !== undefined) {
      headers['Content-Type'] = 'application/json';
    }
    const token = auth ? config.token() : null;
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    let response: Response;
    try {
      response = await doFetch(buildUrl(config.baseUrl(), path, query), {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
        signal: controller.signal,
      });
    } catch {
      throw new ApiError('network_error', NETWORK_ERROR_MESSAGE, 0);
    } finally {
      clearTimeout(timer);
    }

    let payload: unknown;
    try {
      payload = await response.json();
    } catch {
      throw new ApiError('server_error', 'پاسخ سرور قابل خواندن نیست.', response.status);
    }

    const envelope = payload as {
      success?: boolean;
      data?: T;
      meta?: ApiMeta;
      error?: { code?: string; message?: string; fields?: Record<string, string[]> };
    };

    if (response.ok && envelope?.success === true) {
      return { data: envelope.data as T, meta: envelope.meta ?? {} };
    }

    const error = new ApiError(
      envelope?.error?.code ?? `http_${response.status}`,
      envelope?.error?.message ?? 'خطای ناشناخته از سرور.',
      response.status,
      envelope?.error?.fields ?? null,
      envelope?.meta ?? {},
    );

    if (response.status === 401 && token && config.onUnauthorized) {
      config.onUnauthorized();
    }

    throw error;
  }

  return {
    request,
    get: <T>(path: string, query?: Query) => request<T>('GET', path, { query }),
    post: <T>(path: string, body?: unknown, auth = true) => request<T>('POST', path, { body: body ?? {}, auth }),
    put: <T>(path: string, body?: unknown) => request<T>('PUT', path, { body: body ?? {} }),
    del: <T>(path: string) => request<T>('DELETE', path),
  };
}

export type ApiClient = ReturnType<typeof createClient>;
