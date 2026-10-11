import QRCode from 'qrcode';

/**
 * QR آدرس سالن در کارت «آدرس اختصاصی سالن» پنل مدیر (بسته‌ی ۳ اپلیکیشن، ۲۰۲۶-۱۰-۱۱). مشتری با دوربین گوشی یا با
 * اسکنر اپ «ماهرو» همین را می‌خواند. کاملاً در مرورگر ساخته می‌شود (بدون سرویس بیرونی).
 */
const options = { errorCorrectionLevel: 'M', margin: 1, color: { dark: '#1A1410', light: '#FFFFFF' } };

document.querySelectorAll('[data-salon-qr]').forEach(async (box) => {
    const url = box.dataset.salonQr;
    box.innerHTML = await QRCode.toString(url, { ...options, type: 'svg' });

    const card = box.closest('[data-salon-qr-card]');
    card?.querySelector('[data-salon-qr-download]')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const link = document.createElement('a');
        link.href = await QRCode.toDataURL(url, { ...options, width: 1024 });
        link.download = `${box.dataset.salonQrName || 'salon'}-qr.png`;
        link.click();
    });
    card?.querySelector('[data-salon-qr-print]')?.addEventListener('click', (e) => {
        e.preventDefault();
        const win = window.open('', '_blank');
        win.document.write(`<html dir="rtl"><head><title>QR</title></head><body style="text-align:center;font-family:Tahoma;padding:40px">
            <div style="width:320px;margin:auto">${box.innerHTML}</div>
            <h2>${box.dataset.salonQrTitle || ''}</h2><p dir="ltr">${url}</p></body></html>`);
        win.document.close();
        win.focus();
        win.print();
    });
});
