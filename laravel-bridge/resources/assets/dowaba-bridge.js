/*!
 * Dowaba Laravel Bridge — istemci-yardımcısı
 *
 * Yazılımcının kendi sayfasından Dowaba API'sini çağırırken:
 *   - CSRF token <meta name="csrf-token"> tag'inden alınır
 *   - Base URL <meta name="dowaba-base-url"> veya window.DOWABA_BASE_URL'den
 *   - Tüm istekler JSON + Accept: application/json
 *
 * Bridge backend (DowabaClient) Bearer auth + scope guard kendisi yapıyor, bu helper
 * sadece yazılımcının frontend'inden backend'ine giden çağrıları kolaylaştırır.
 *
 * Kullanım:
 *   Dowaba.fetch('/my/contacts', { method: 'POST', body: { name: 'Ali' } })
 *     .then(r => console.log(r))
 *     .catch(e => alert(e.message));
 */
(function (global) {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function getBaseUrl() {
        if (global.DOWABA_BASE_URL) return global.DOWABA_BASE_URL;
        const meta = document.querySelector('meta[name="dowaba-base-url"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function buildUrl(path) {
        if (/^https?:\/\//i.test(path)) return path;
        const base = getBaseUrl().replace(/\/+$/, '');
        return base + (path.startsWith('/') ? path : '/' + path);
    }

    async function request(path, opts) {
        opts = opts || {};
        const method = (opts.method || 'GET').toUpperCase();

        const headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, opts.headers || {});

        if (['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1) {
            const csrf = getCsrfToken();
            if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        }

        let body = opts.body;
        if (body && typeof body === 'object' && !(body instanceof FormData)) {
            headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            body = JSON.stringify(body);
        }

        const res = await fetch(buildUrl(path), {
            method: method,
            headers: headers,
            body: body,
            credentials: opts.credentials || 'same-origin',
        });

        const contentType = res.headers.get('content-type') || '';
        const data = contentType.indexOf('application/json') !== -1 ? await res.json() : await res.text();

        if (!res.ok) {
            const err = new Error('Dowaba ' + method + ' ' + path + ' başarısız (' + res.status + ')');
            err.status = res.status;
            err.response = data;
            throw err;
        }

        return data;
    }

    global.Dowaba = global.Dowaba || {};
    global.Dowaba.fetch = request;
    global.Dowaba.get = (path, opts) => request(path, Object.assign({}, opts, { method: 'GET' }));
    global.Dowaba.post = (path, body, opts) => request(path, Object.assign({}, opts, { method: 'POST', body: body }));
    global.Dowaba.put = (path, body, opts) => request(path, Object.assign({}, opts, { method: 'PUT', body: body }));
    global.Dowaba.patch = (path, body, opts) => request(path, Object.assign({}, opts, { method: 'PATCH', body: body }));
    global.Dowaba.delete = (path, opts) => request(path, Object.assign({}, opts, { method: 'DELETE' }));

    global.Dowaba.version = '0.2.0-dev';
})(typeof window !== 'undefined' ? window : globalThis);
