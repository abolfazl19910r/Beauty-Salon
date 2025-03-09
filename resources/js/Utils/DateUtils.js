// resources/js/Utils/DateUtils.js

export const toGregorian = (persianDateStr) => {
    if (!persianDateStr || !isValidPersianDate(persianDateStr)) {
        return '';
    }

    const [year, month, day] = persianDateStr.split('/').map(Number);

    let jd = persian_to_jd(year, month, day);

    let date = jd_to_gregorian(jd);

    return date.map(num => String(num).padStart(2, '0')).join('-');
};

export const toPersian = (gregorianDateStr) => {
    if (!gregorianDateStr) return '';

    const [year, month, day] = gregorianDateStr.split('-').map(Number);
    const jd = gregorian_to_jd(year, month - 1, day);
    const persian = jd_to_persian(jd);

    return persian.map(num => String(num).padStart(2, '0')).join('/');
};

export const isValidPersianDate = (dateStr) => {
    if (!dateStr || !/^\d{4}\/\d{2}\/\d{2}$/.test(dateStr)) {
        return false;
    }

    const [year, month, day] = dateStr.split('/').map(Number);

    if (month < 1 || month > 12) return false;
    if (day < 1) return false;

    const maxDays = getMonthDays(year, month);
    if (day > maxDays) return false;

    return true;
};

export const comparePersianDates = (date1, date2) => {
    if (!date1 || !date2) return 0;

    const [y1, m1, d1] = date1.split('/').map(Number);
    const [y2, m2, d2] = date2.split('/').map(Number);

    if (y1 !== y2) return y1 - y2;
    if (m1 !== m2) return m1 - m2;
    return d1 - d2;
};

export const getMonthDays = (year, month) => {
    if (month < 1 || month > 12) return 0;
    if (month <= 6) return 31;
    if (month <= 11) return 30;
    return isLeapYear(year) ? 30 : 29;
};

export const isLeapYear = (year) => {
    return ((((((year - 474) % 2820) + 474) + 38) * 682) % 2816) < 682;
};
