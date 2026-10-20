import { parseSalonInput, salonRoute } from './salonLink';

/**
 * لینک https سالن که اندروید با App Links به اپ داده است (بسته‌ی ۵، /.well-known/assetlinks.json روی سرور): «https://rasta.mahru.ir/»
 * یا «…/specialists/12?src=qr» → صفحه‌ی salon/[slug] اپ. اپ intent-filter را فقط برای صفحه‌ی اصلی و صفحه‌ی متخصص سالن می‌گیرد
 * (app.config.ts)؛ هر لینک https دیگری که به اپ برسد، به ریشه‌ی اپ می‌رود. لینک‌های mahru:// همان مسیر خودشان می‌مانند.
 */
export function systemPathFor(path: string): string {
  if (!/^https?:\/\//i.test(path)) {
    return path;
  }
  const parsed = parseSalonInput(path);
  return parsed ? salonRoute(parsed) : '/';
}
