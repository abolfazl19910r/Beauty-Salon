/** صفحه‌های حقوقی پلتفرم روی دامنه‌ی مرکزی (بسته‌ی مجوز 5د؛ لاراول: legal.terms / legal.privacy / legal.contact) */
export type LegalPage = 'terms' | 'privacy' | 'contact';

export function legalUrl(serverUrl: string, page: LegalPage): string {
  return `${serverUrl.replace(/\/+$/, '')}/${page}`;
}
