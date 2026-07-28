/* WCC CMMS — client i18n. head.php injects WCC_LOCALE, WCC_I18N, WCC_I18N_FALLBACK. */
'use strict';

function t(key, vars) {
    if (!key) return '';
    var dict = (typeof window.WCC_I18N === 'object' && window.WCC_I18N) ? window.WCC_I18N : {};
    var fb = (typeof window.WCC_I18N_FALLBACK === 'object' && window.WCC_I18N_FALLBACK) ? window.WCC_I18N_FALLBACK : {};
    var str = dict[key];
    if (str == null || str === '') str = fb[key];
    if (str == null || str === '') str = String(key);
    if (vars && typeof vars === 'object') {
        Object.keys(vars).forEach(function (name) {
            str = String(str).split(':' + name).join(String(vars[name]));
        });
    }
    return str;
}

window.t = t;
