import QRCode from 'qrcode';

/**
 * تصویر QR برای اینستاگرام (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰) — در مرورگر با canvas، چون متن فارسی را مرورگر درست می‌چیند (GD سمت سرور نه).
 * قالب‌ها: story ‏۱۰۸۰×۱۹۲۰، post ‏۱۰۸۰×۱۰۸۰، plain (فقط QR روی سفید). داده از ویژگی‌های [data-qr-poster]:
 * url، title، subtitle، code، brand، file، logo (data URI؛ تا canvas آلوده نشود و toBlob کار کند).
 */
const DARK = '#1A1410';
const GOLD = '#C9A24B';
const GOLD_LIGHT = '#E6CD8A';
const CREAM = '#F8F3E9';
const FONT = '"Mahru", "Vazirmatn", Tahoma, sans-serif';

const SIZES = { story: [1080, 1920], post: [1080, 1080], plain: [1024, 1024] };

async function loadFonts() {
    if (!document.fonts?.load) return;
    try {
        await Promise.all(['400', '700', '800'].map((w) => document.fonts.load(`${w} 40px "Mahru"`, 'سالن')));
    } catch (e) {
        /* بدون فونت هم با Tahoma کشیده می‌شود */
    }
}

function loadImage(src) {
    return new Promise((resolve) => {
        if (!src) return resolve(null);
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

/** متن وسط‌چین راست‌به‌چپ که اگر جا نشد، کوچک می‌شود */
function fitText(ctx, text, x, y, maxWidth, size, weight, color) {
    let s = size;
    do {
        ctx.font = `${weight} ${s}px ${FONT}`;
        s -= 2;
    } while (ctx.measureText(text).width > maxWidth && s > 18);
    ctx.fillStyle = color;
    ctx.fillText(text, x, y);
}

async function qrCanvas(url, size) {
    const canvas = document.createElement('canvas');
    await QRCode.toCanvas(canvas, url, { errorCorrectionLevel: 'M', margin: 0, width: size, color: { dark: DARK, light: '#FFFFFF' } });
    return canvas;
}

export async function drawPoster(data, format) {
    const [W, H] = SIZES[format];
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.direction = 'rtl';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    if (format === 'plain') {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, W, H);
        ctx.drawImage(await qrCanvas(data.url, W - 80), 40, 40);
        return canvas;
    }

    await loadFonts();
    const story = format === 'story';

    // زمینه‌ی اسپرسو با درخشش طلایی بالا
    ctx.fillStyle = DARK;
    ctx.fillRect(0, 0, W, H);
    const glow = ctx.createRadialGradient(W / 2, 0, 40, W / 2, 0, H * 0.7);
    glow.addColorStop(0, 'rgba(201,162,75,0.35)');
    glow.addColorStop(1, 'rgba(201,162,75,0)');
    ctx.fillStyle = glow;
    ctx.fillRect(0, 0, W, H);
    ctx.strokeStyle = 'rgba(201,162,75,0.55)';
    ctx.lineWidth = 3;
    roundRect(ctx, 36, 36, W - 72, H - 72, 40);
    ctx.stroke();

    // اندازه‌ها برای هر قالب؛ بی‌لوگو، بلوک محتوا پایین‌تر شروع می‌شود تا وسط قاب بماند
    const logo = await loadImage(data.logo);
    const L = story
        ? { top: logo ? 200 : 380, logoBox: 200, afterLogo: 110, title: 84, afterTitle: 90, sub: 44, afterSub: 90, qr: 680, pad: 44, afterQr: 90, scan: 46, afterScan: 80, code: 38, footer: true }
        : { top: logo ? 80 : 170, logoBox: 96, afterLogo: 74, title: 60, afterTitle: 62, sub: 34, afterSub: 44, qr: logo ? 424 : 500, pad: 28, afterQr: 52, scan: 34, afterScan: 48, code: 28, footer: false };

    let y = L.top;
    if (logo) {
        const box = L.logoBox;
        const scale = Math.min(box / logo.width, box / logo.height);
        const lw = logo.width * scale;
        const lh = logo.height * scale;
        ctx.fillStyle = CREAM;
        roundRect(ctx, (W - box) / 2 - 20, y - 20, box + 40, box + 40, story ? 32 : 22);
        ctx.fill();
        ctx.drawImage(logo, (W - lw) / 2, y + (box - lh) / 2, lw, lh);
        y += box + L.afterLogo;
    }

    fitText(ctx, data.title, W / 2, y, W - 160, L.title, 800, GOLD_LIGHT);
    y += L.afterTitle;
    fitText(ctx, data.subtitle, W / 2, y, W - 200, L.sub, 400, CREAM);
    y += L.afterSub;

    ctx.fillStyle = '#FFFFFF';
    roundRect(ctx, (W - L.qr) / 2 - L.pad, y, L.qr + L.pad * 2, L.qr + L.pad * 2, story ? 36 : 26);
    ctx.fill();
    ctx.drawImage(await qrCanvas(data.url, L.qr), (W - L.qr) / 2, y + L.pad);
    y += L.qr + L.pad * 2 + L.afterQr;

    fitText(ctx, 'با دوربین گوشی اسکن کنید و نوبت بگیرید', W / 2, y, W - 160, L.scan, 700, CREAM);
    y += L.afterScan;
    fitText(ctx, `کد سالن در اپ ${data.brand}: ${data.code}`, W / 2, y, W - 160, L.code, 400, GOLD);

    if (L.footer) {
        fitText(ctx, `نوبت‌دهی آنلاین با ${data.brand}`, W / 2, H - 110, W - 160, 30, 400, 'rgba(248,243,233,0.55)');
    }

    return canvas;
}

function download(canvas, name) {
    canvas.toBlob((blob) => {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 4000);
    }, 'image/png');
}

document.querySelectorAll('[data-qr-poster]').forEach((box) => {
    box.querySelectorAll('[data-qr-poster-format]').forEach((button) => {
        button.addEventListener('click', async () => {
            const format = button.dataset.qrPosterFormat;
            button.disabled = true;
            try {
                const canvas = await drawPoster(box.dataset, format);
                download(canvas, `${box.dataset.file || 'salon-qr'}-${format}.png`);
            } finally {
                button.disabled = false;
            }
        });
    });
});
