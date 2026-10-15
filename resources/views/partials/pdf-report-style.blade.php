{{--
    ⭐ ظاهر مشترک گزارش‌های PDF (مدیر و متخصص، ۲۰۲۶-۱۰-۱۵) — برای چاپ و صفحه‌ی سفید، با mPDF.
    mPDF متغیر CSS نمی‌فهمد، پس رنگ‌ها ثابت‌اند. پالت: جوهر گرم تیره + یک رنگ برنجی/طلایی ماهرو (همان خانواده‌ی
    برند، ولی تیره‌تر تا روی کاغذ خوانا و قابل‌چاپ سیاه‌وسفید بماند)، خط‌های گرم خیلی روشن و رنگ‌های وضعیت ملایم با متن پررنگ.
    فونت Vazirmatn از App\Support\Pdf\ReportPdf (نام «vazir»)؛ پررنگ فقط با font-weight.

      جوهر        #1F1A14      متن اصلی، نوار سربرگ
      طلایی       #A67F2E      خط تأکید، عددهای مهم
      طلایی روشن  #F5EEDD      زمینه‌ی کاشی برجسته
      خط          #E6DFD2      حاشیه‌ها
      زمینه‌ی ردیف #FAF7F1      ردیف‌های یک‌درمیان
      کم‌رنگ      #6E6355      برچسب‌ها
      وضعیت‌ها    سبز #2E7650/#E7F2EB · زرد #94600F/#FBF0DC · قرمز #AD4430/#FAE8E3 · آبی #2D6787/#E5EFF5
--}}
<style>
    body { font-family: vazir; font-size: 9.5pt; color: #1F1A14; line-height: 1.75; direction: rtl; }
    b, strong, th { font-weight: bold; }

    /* سربرگ: نوار جوهری با نام سالن، خط طلایی زیرش */
    table.masthead { width: 100%; border-collapse: collapse; }
    table.masthead td { background-color: #1F1A14; color: #FFFFFF; padding: 14px 18px; vertical-align: middle; }
    td.ms-main .salon, .ms-salon { font-size: 15pt; font-weight: bold; color: #FFFFFF; }
    .ms-kicker { font-size: 8pt; color: #D9C08A; letter-spacing: 1px; }
    td.ms-doc { text-align: left; font-size: 8.5pt; color: #CFC6B6; }
    .ms-title { font-size: 11.5pt; font-weight: bold; color: #E7CF95; }
    .masthead-rule { height: 3px; background-color: #A67F2E; margin-bottom: 14px; }

    /* نوار اطلاعات (بازه، نام متخصص، …) */
    table.meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.meta td { padding: 7px 12px; background-color: #FAF7F1; border: 1px solid #E6DFD2; font-size: 8.5pt; color: #6E6355; }
    table.meta td b { color: #1F1A14; }

    /* کاشی‌های عدد */
    table.kpis { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 16px; }
    table.kpis td { width: 25%; padding: 11px 12px; border: 1px solid #E6DFD2; border-top: 3px solid #E6DFD2; background-color: #FFFFFF; vertical-align: top; }
    table.kpis td.lead { background-color: #F5EEDD; border-color: #E2CF9F; border-top-color: #A67F2E; }
    table.kpis td.good { border-top-color: #2E7650; }
    table.kpis td.bad { border-top-color: #AD4430; }
    table.kpis td.info { border-top-color: #2D6787; }
    .kpi-label { font-size: 7.5pt; color: #6E6355; }
    .kpi-value { font-size: 15pt; font-weight: bold; color: #1F1A14; line-height: 1.3; }
    td.lead .kpi-value { color: #7A5A1A; }
    .kpi-unit { font-size: 7pt; color: #8E8473; }

    /* عنوان بخش */
    .section { margin: 16px 0 7px; padding-right: 8px; border-right: 3px solid #A67F2E; font-size: 10.5pt; font-weight: bold; color: #1F1A14; }

    /* جدول داده */
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.data th { background-color: #F3EEE4; color: #4A4135; font-size: 8pt; padding: 7px 9px; text-align: right; border-bottom: 1.5px solid #A67F2E; }
    table.data td { padding: 6px 9px; font-size: 8.8pt; border-bottom: 1px solid #ECE6DA; text-align: right; }
    table.data tr.alt td { background-color: #FAF7F1; }
    table.data td.num, table.data th.num { text-align: left; direction: ltr; }
    table.data td.strong { font-weight: bold; }
    table.data tr.total td { background-color: #F5EEDD; font-weight: bold; border-top: 1.5px solid #A67F2E; border-bottom: 0; }
    table.data.compact th { font-size: 6.8pt; padding: 4px 4px; }
    table.data.compact td { font-size: 6.8pt; padding: 3px 4px; }
    table.bar { width: 100%; border-collapse: collapse; }
    table.bar td { height: 6px; padding: 0; border: 0; font-size: 1pt; line-height: 1pt; }
    table.bar td.fill { background-color: #A67F2E; }
    table.bar td.rest { background-color: #ECE6DA; }

    /* برچسب وضعیت */
    .pill { padding: 1px 7px; font-size: 7.5pt; font-weight: bold; border-radius: 8px; }
    .pill-good { color: #2E7650; background-color: #E7F2EB; }
    .pill-warn { color: #94600F; background-color: #FBF0DC; }
    .pill-bad { color: #AD4430; background-color: #FAE8E3; }
    .pill-info { color: #2D6787; background-color: #E5EFF5; }
    .pill-mute { color: #6E6355; background-color: #F1ECE3; }
    td.cell-good { color: #2E7650; background-color: #E7F2EB; }
    td.cell-warn { color: #94600F; background-color: #FBF0DC; }
    td.cell-bad { color: #AD4430; background-color: #FAE8E3; }

    .muted { color: #8E8473; }
    .empty { padding: 18px; text-align: center; color: #8E8473; border: 1px dashed #E6DFD2; }

    /* پاورقی هر صفحه */
    table.foot { width: 100%; border-top: 1px solid #E6DFD2; font-size: 7.5pt; color: #8E8473; }
    table.foot td { padding-top: 4px; }
</style>
