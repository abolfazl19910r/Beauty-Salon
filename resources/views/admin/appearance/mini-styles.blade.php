{{-- ⭐ طرح کوچک قالب‌ها (admin/appearance/mini) — «ظاهر سالن» مدیر و «قالب‌ها»ی ابرمدیر؛ بسته‌ی ۵الف: طرح‌های ۴ قالب ترکیبی --}}
@once
<style>
.mm{height:92px;border-radius:10px;overflow:hidden;background:rgb(var(--mm-bg));color:rgb(var(--mm-tx));border:1px solid rgb(var(--mm-mu) / .2);font-size:9px;line-height:1.4;position:relative}
.mm b{font-size:11px;display:block}
.mm small{font-size:7px;color:rgb(var(--mm-mu))}
.mm .mm-bar{display:block;height:4px;border-radius:3px;background:rgb(var(--mm-mu) / .45)}
.mm .mm-pill{display:block;height:9px;width:44px;border-radius:999px;background:var(--mm-btn)}
.mm .mm-blk{display:block;background:rgb(var(--mm-bg3));border-radius:6px;min-height:20px}
.mm .mm-art{display:block;background:linear-gradient(135deg,rgb(var(--mm-ac)),rgb(var(--mm-ac2)))}
.mm .mm-s{flex:1;height:18px}
.mm .mm-row{display:flex;gap:4px}
.mm .mm-line{display:block;height:1px;background:rgb(var(--mm-mu) / .35)}
.mm .mm-serif{font-family:"MahruNaskh","Mahru",serif;font-weight:500}
.mm-m{display:flex;flex-direction:column;align-items:center;gap:5px;padding:8px}
.mm-m .mm-row{width:100%;margin-top:4px}
/* شاهکار: قاب نازک، تیتر نسخ و سه عدد، فرم رزرو با روزها و ساعت‌ها */
.mm-sg{display:grid;grid-template-columns:1.2fr 1fr;gap:6px;padding:9px;height:100%;background:radial-gradient(70% 90% at 80% 0%,rgb(var(--mm-ac) / .25),transparent 70%)}
.mm-sg::before{content:"";position:absolute;inset:4px;border:1px solid rgb(var(--mm-ac) / .4);pointer-events:none}
.mm-sg>div:first-child{display:flex;flex-direction:column;gap:4px;justify-content:center}
.mm-sg .mm-serif{font-size:13px;line-height:1.3}
.mm-sg .mm-dots{display:flex;gap:5px}
.mm-sg .mm-dots i{width:9px;height:6px;border-radius:2px;background:rgb(var(--mm-ac))}
.mm-sg .mm-f{background:rgb(var(--mm-bg2));border:1px solid rgb(var(--mm-ac) / .3);padding:4px;display:flex;flex-direction:column;gap:3px}
.mm-sg .mm-f .mm-row i{flex:1;height:7px;border-radius:3px;border:1px solid rgb(var(--mm-ac) / .35)}
.mm-sg .mm-f .mm-row i:first-child{background:rgb(var(--mm-ac));border-color:transparent}
/* روزنامه‌ی سالن: خط تاریخ، نام نسخ و دکمه‌ی چهارگوش بین خط دوتایی و خط زیرین، کاور و کارت تیره‌ی پیشنهاد */
.mm-ed{display:flex;flex-direction:column;gap:3px;padding:6px 8px;height:100%}
.mm-ed .mm-dbl{display:block;height:3px;border-top:1px solid rgb(var(--mm-tx));border-bottom:1px solid rgb(var(--mm-tx))}
.mm-ed .mm-mast{display:flex;justify-content:space-between;align-items:center}
.mm-ed .mm-serif{font-size:13px;font-weight:700}
.mm-ed .mm-sq{width:22px;height:8px;background:rgb(var(--mm-ac))}
.mm-ed .mm-cols{display:grid;grid-template-columns:1.3fr 1fr;gap:5px;flex:1}
.mm-ed .mm-dark{background:rgb(var(--mm-tx))}
/* نمایشگاه: متن (نام کوفی، کارت نوبت، خدمات) و کنارش قاب ثابت نمونه‌کار با بندانگشتی‌ها */
.mm-sc{display:grid;grid-template-columns:1fr 1fr;height:100%}
.mm-sc .mm-art{position:relative}
.mm-sc .mm-thumbs{position:absolute;bottom:5px;right:5px;left:5px;display:flex;gap:3px}
.mm-sc .mm-thumbs i{flex:1;height:10px;border-radius:2px;background:rgb(255 255 255 / .55)}
.mm-sc>div:first-child{display:flex;flex-direction:column;gap:4px;padding:7px}
.mm-sc .mm-art{margin:5px;border-radius:9px}
.mm .mm-kufi{font-family:"MahruKufi","Mahru",sans-serif;font-weight:800}
.mm-sc .mm-slot{border:1px solid rgb(var(--mm-ac) / .45);border-radius:5px;padding:3px;color:rgb(var(--mm-ac))}
/* اپ: کارت سالن با حلقه و دکمه، نوار تیره‌ی ساعت‌ها و کاشی‌ها */
.mm-ap{display:grid;grid-template-columns:.9fr 1.5fr;gap:4px;padding:5px;height:100%}
.mm-ap .mm-cardx{background:rgb(var(--mm-bg2));border-radius:9px;display:flex;flex-direction:column;align-items:center;gap:3px;padding:5px 3px}
.mm-ap .mm-ring{width:15px;height:15px;border-radius:50%;box-shadow:0 0 0 1px rgb(var(--mm-bg2)),0 0 0 2px rgb(var(--mm-ac))}
.mm-ap .mm-grid{display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:auto 1fr 1fr;gap:3px}
.mm-ap .mm-band{grid-column:span 3;height:16px;border-radius:7px;background:rgb(var(--mm-tx));display:flex;gap:2px;padding:3px}
.mm-ap .mm-band i{flex:1;border-radius:3px;background:rgb(var(--mm-bg) / .3)}
.mm-ap .mm-band i:first-child{background:rgb(var(--mm-bg))}
.mm-ap .mm-grid>span{border-radius:7px;background:rgb(var(--mm-bg2))}
.mm-ap .mm-grid>span.mm-a2{background:rgb(var(--mm-ac2))}
</style>
@endonce
