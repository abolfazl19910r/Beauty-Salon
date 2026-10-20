/// <reference types="jest" />
import { parseSalonInput, salonRoute } from '../salonLink';

describe('parseSalonInput', () => {
  it.each([
    ['rasta', { slug: 'rasta', origin: null }],
    ['  golbahar_2  ', { slug: 'golbahar_2', origin: null }],
    ['سالن-مهتاب', { slug: 'سالن-مهتاب', origin: null }],
    ['http://192.168.1.5:8000/s/rasta', { slug: 'rasta', origin: 'http://192.168.1.5:8000' }],
    ['https://mahru.ir/s/rasta/booking?x=1', { slug: 'rasta', origin: 'https://mahru.ir' }],
    ['http://۱۹۲.۱۶۸.۱.۵:۸۰۰۰/s/rasta', { slug: 'rasta', origin: 'http://192.168.1.5:8000' }],
    ['https://Rasta.mahru.ir/', { slug: 'rasta', origin: 'https://mahru.ir' }],
    ['rasta.mahru.ir', { slug: 'rasta', origin: 'https://mahru.ir' }],
    ['https://mahru.ir/s/%D9%85%D9%87%D8%AA%D8%A7%D8%A8', { slug: 'مهتاب', origin: 'https://mahru.ir' }],
    ['mahru://salon/rasta', { slug: 'rasta', origin: null }],
    ['mahru://salon/rasta?from=web', { slug: 'rasta', origin: null }],
    // QR پنلی که روی خود کامپیوتر باز شده بود: سالن پیدا می‌شود ولی سرور اپ عوض نمی‌شود (۲۰۲۶-۱۰-۱۷)
    ['http://127.0.0.1:8000/s/rasta', { slug: 'rasta', origin: null }],
    ['http://localhost:8000/s/rasta', { slug: 'rasta', origin: null }],
    ['http://rasta.localhost:8000/', { slug: 'rasta', origin: null }],
    ['http://rasta.rasta-app.test/', { slug: 'rasta', origin: null }],
    // QR متخصص (بسته‌ی ۵): ساب‌دامین و مسیر قدیمی، با ?src=qr
    ['https://rasta.mahru.ir/specialists/12?src=qr', { slug: 'rasta', origin: 'https://mahru.ir', specialistId: 12 }],
    ['http://192.168.1.5:8000/s/rasta/specialists/7', { slug: 'rasta', origin: 'http://192.168.1.5:8000', specialistId: 7 }],
    ['http://rasta.192.168.1.2.nip.io:8000/?src=qr', { slug: 'rasta', origin: 'http://192.168.1.2.nip.io:8000' }],
    ['https://rasta.mahru.ir/specialists/search', { slug: 'rasta', origin: 'https://mahru.ir' }],
  ])('%s', (input, expected) => {
    expect(parseSalonInput(input)).toEqual(expected);
  });

  it.each(['', '   ', 'https://mahru.ir/', 'https://www.mahru.ir/', 'http://192.168.1.5:8000/', 'two words', 'a/b', 'mahru://payment?tx=1', 'https://mahru.ir/s/'])(
    'rejects %p',
    (input) => {
      expect(parseSalonInput(input)).toBeNull();
    },
  );
});

describe('salonRoute', () => {
  it('maps a parsed salon link to the in-app route', () => {
    expect(salonRoute({ slug: 'rasta', origin: null })).toBe('/salon/rasta');
    expect(salonRoute({ slug: 'مهتاب', origin: null, specialistId: 12 })).toBe(`/salon/${encodeURIComponent('مهتاب')}?specialist=12`);
  });
});
