/// <reference types="jest" />
import { ApiError, buildUrl, createClient } from '../api';

const json = (status: number, body: unknown) =>
  Promise.resolve({ ok: status >= 200 && status < 300, status, json: () => Promise.resolve(body) } as Response);

function client(fetchImpl: jest.Mock, token: string | null = 'T0K', onUnauthorized = jest.fn()) {
  return { api: createClient({ baseUrl: () => 'http://192.168.1.5:8000/', token: () => token, appVersion: '0.1.0', fetchImpl, onUnauthorized }), onUnauthorized };
}

describe('buildUrl', () => {
  it('joins base, /api/v1 and drops empty query values', () => {
    expect(buildUrl('http://x:8000/', '/staff/bookings', { status: null, from: '2026-10-11', page: 2, unread: true, to: '' }))
      .toBe('http://x:8000/api/v1/staff/bookings?from=2026-10-11&page=2&unread=1');
  });
});

describe('createClient', () => {
  it('unwraps the success envelope and sends bearer + version headers', async () => {
    const fetchImpl = jest.fn(() => json(200, { success: true, data: { a: 1 }, meta: { total: 3 } }));
    const { api } = client(fetchImpl);

    await expect(api.get('/me')).resolves.toEqual({ data: { a: 1 }, meta: { total: 3 } });
    const [, init] = fetchImpl.mock.calls[0] as unknown as [string, RequestInit];
    expect((init.headers as Record<string, string>).Authorization).toBe('Bearer T0K');
    expect((init.headers as Record<string, string>)['X-App-Version']).toBe('0.1.0');
  });

  it('turns the error envelope into ApiError with code, fields and meta', async () => {
    const fetchImpl = jest.fn(() => json(422, {
      success: false,
      error: { code: 'invalid_code', message: 'کد وارد شده نامعتبر است.', fields: { code: ['x'] } },
      meta: { attempts_left: 3 },
    }));
    const { api } = client(fetchImpl);

    const error = await api.post('/staff/login/verify', {}, false).catch((e) => e);
    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe('invalid_code');
    expect(error.status).toBe(422);
    expect(error.field('code')).toBe('x');
    expect(error.meta.attempts_left).toBe(3);
  });

  it('does not send the token on public calls', async () => {
    const fetchImpl = jest.fn(() => json(200, { success: true, data: {} }));
    const { api } = client(fetchImpl);

    await api.post('/staff/login', { phone: '09120000000' }, false);
    const [, init] = fetchImpl.mock.calls[0] as unknown as [string, RequestInit];
    expect((init.headers as Record<string, string>).Authorization).toBeUndefined();
  });

  it('calls onUnauthorized only for an authenticated 401', async () => {
    const fetchImpl = jest.fn(() => json(401, { success: false, error: { code: 'unauthenticated', message: 'ابتدا وارد شوید.' } }));
    const authed = client(fetchImpl);
    await expect(authed.api.get('/me')).rejects.toMatchObject({ code: 'unauthenticated' });
    expect(authed.onUnauthorized).toHaveBeenCalledTimes(1);

    const anon = client(fetchImpl, null);
    await expect(anon.api.get('/me')).rejects.toMatchObject({ code: 'unauthenticated' });
    expect(anon.onUnauthorized).not.toHaveBeenCalled();
  });

  it('maps transport failures and non-JSON bodies', async () => {
    const offline = client(jest.fn(() => Promise.reject(new TypeError('Network request failed'))));
    await expect(offline.api.get('/status')).rejects.toMatchObject({ code: 'network_error', status: 0 });

    const html = client(jest.fn(() => Promise.resolve({ ok: false, status: 502, json: () => Promise.reject(new SyntaxError('<')) } as Response)));
    await expect(html.api.get('/status')).rejects.toMatchObject({ code: 'server_error', status: 502 });
  });
});
