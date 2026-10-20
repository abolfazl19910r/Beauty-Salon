import QRCode from 'qrcode';

/**
 * QR آدرس سالن یا متخصص (بسته‌ی ۳ اپلیکیشن، ۲۰۲۶-۱۰-۱۱؛ بسته‌ی ۵): کارت «آدرس اختصاصی سالن» داشبورد و صفحه‌ی «QR و پوستر سالن».
 * مشتری با دوربین گوشی یا با اسکنر اپ «ماهرو» همین را می‌خواند. کاملاً در مرورگر ساخته می‌شود (بدون سرویس بیرونی).
 * دانلود PNG و پوستر اینستاگرام: salon-qr-poster.js؛ چاپ: PDF همان صفحه.
 */
export const QR_OPTIONS = { errorCorrectionLevel: 'M', margin: 1, color: { dark: '#1A1410', light: '#FFFFFF' } };

document.querySelectorAll('[data-salon-qr]').forEach(async (box) => {
    box.innerHTML = await QRCode.toString(box.dataset.salonQr, { ...QR_OPTIONS, type: 'svg' });
});
