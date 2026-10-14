import { LOGO_PLAQUE, logoAppearance } from '../logo';

const base = { logo_url: 'http://x/a.png' };

describe('logoAppearance (بسته‌ی د)', () => {
  it('بدون لوگو null است (نشان ماهرو)', () => {
    expect(logoAppearance({ logo_url: null }, 'dark')).toBeNull();
  });

  it('لوگوی روشن فقط روی تم روشن پلاک تیره می‌گیرد', () => {
    const s = { ...base, logo_tone: 'light' as const };
    expect(logoAppearance(s, 'light')).toEqual({ uri: base.logo_url, plaque: LOGO_PLAQUE.light });
    expect(logoAppearance(s, 'dark')).toEqual({ uri: base.logo_url, plaque: null });
  });

  it('لوگوی تیره روی تم تیره پلاک روشن می‌گیرد مگر لوگوی دوم باشد', () => {
    const s = { ...base, logo_tone: 'dark' as const };
    expect(logoAppearance(s, 'dark')).toEqual({ uri: base.logo_url, plaque: LOGO_PLAQUE.dark });
    expect(logoAppearance(s, 'light')?.plaque).toBeNull();
    expect(logoAppearance({ ...s, logo_dark_url: 'http://x/b.png', logo_dark_tone: 'light' }, 'dark')).toEqual({ uri: 'http://x/b.png', plaque: null });
    expect(logoAppearance({ ...s, logo_dark_url: 'http://x/b.png', logo_dark_tone: 'dark' }, 'dark')?.plaque).toBe(LOGO_PLAQUE.dark);
  });

  it('لحن نامعلوم یا mid بی‌پلاک است (سرور قدیمی)', () => {
    expect(logoAppearance(base, 'light')?.plaque).toBeNull();
    expect(logoAppearance({ ...base, logo_tone: 'mid' }, 'dark')?.plaque).toBeNull();
  });
});
