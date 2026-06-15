(function () {
    var html = document.documentElement;
    var saved = localStorage.getItem('vp_theme');
    var system = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    html.setAttribute('data-theme', saved || system);
})();

window.vpTheme = {
    toggle: function () {
        var html = document.documentElement;
        var current = html.getAttribute('data-theme') || 'dark';
        var next = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('vp_theme', next);
        document.dispatchEvent(new CustomEvent('vp:theme-change', { detail: next }));
    },
    get: function () {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }
};

(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    window.csrfToken = meta ? meta.content : '';

    window.apiPost = async function (url, data) {
        data = data || {};
        var fd = new FormData();
        fd.append('csrf_token', window.csrfToken);
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        try {
            var r = await fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            return await r.json();
        } catch (e) {
            return { ok: false, xato: 'Tarmoq xatosi' };
        }
    };
})();

if (document.body && document.body.classList.contains('test-page')) {
    document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F12') e.preventDefault();
        if ((e.ctrlKey || e.metaKey) && ['u', 's', 'p'].indexOf(e.key.toLowerCase()) !== -1) e.preventDefault();
        if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].indexOf(e.key) !== -1) e.preventDefault();
    });
}

window.vpToast = function (matn, tur) {
    tur = tur || 'info';
    var div = document.createElement('div');
    var rang = tur === 'success' ? 'border-success/40 text-success' :
               tur === 'error'   ? 'border-danger/40 text-danger'   :
                                   'border-violet/40 text-violet';
    div.className = 'fixed top-5 right-5 z-50 max-w-sm glass-strong p-4 ' + rang;
    div.style.animation = 'fadeIn 0.3s ease both';
    div.textContent = matn;
    document.body.appendChild(div);
    setTimeout(function () {
        div.style.opacity = '0';
        div.style.transition = 'opacity 0.3s';
        setTimeout(function () { div.remove(); }, 300);
    }, 4000);
};
