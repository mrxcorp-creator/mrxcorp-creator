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

if ('serviceWorker' in navigator && location.protocol === 'https:') {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
}

window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    window.vpInstallPrompt = e;

    var prompt_yashirilgan = localStorage.getItem('vp_pwa_yashir');
    if (prompt_yashirilgan && (Date.now() - parseInt(prompt_yashirilgan, 10)) < 7 * 24 * 60 * 60 * 1000) {
        return;
    }

    setTimeout(function () {
        if (!window.vpInstallPrompt) return;
        var div = document.createElement('div');
        div.className = 'glass-strong p-4 fixed bottom-4 left-4 right-4 sm:left-auto sm:right-5 sm:max-w-sm z-40 flex items-center gap-3';
        div.style.animation = 'fadeUp 0.4s ease both';
        div.innerHTML =
            '<div class="text-3xl">📱</div>' +
            '<div class="flex-1 min-w-0">' +
                '<div class="font-display font-bold text-sm">Saytni o\'rnatish</div>' +
                '<div class="text-xs text-muted">Telefoningizga ilova kabi</div>' +
            '</div>' +
            '<button class="btn btn-primary text-xs py-2 px-3" id="vpInstallBtn">O\'rnatish</button>' +
            '<button class="text-muted hover:text-text text-xl px-2" id="vpInstallClose" aria-label="Yopish">×</button>';
        document.body.appendChild(div);

        document.getElementById('vpInstallBtn').addEventListener('click', function () {
            if (window.vpInstallPrompt) {
                window.vpInstallPrompt.prompt();
                window.vpInstallPrompt.userChoice.then(function () {
                    window.vpInstallPrompt = null;
                    div.remove();
                });
            }
        });
        document.getElementById('vpInstallClose').addEventListener('click', function () {
            localStorage.setItem('vp_pwa_yashir', String(Date.now()));
            div.remove();
        });
    }, 5000);
});

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
