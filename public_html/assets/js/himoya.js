(function () {
    var meta = document.querySelector('meta[name="vp-rol"]');
    var rol = meta ? meta.content : 'guest';

    var himoyaMeta = document.querySelector('meta[name="vp-himoya"]');
    var himoyaYoq = himoyaMeta && himoyaMeta.content === 'off';

    if (rol === 'developer' || rol === 'admin' || himoyaYoq) {
        return;
    }

    var QAYD_URL = '/api/xavfsizlik_qayd.php';
    var qayd_yuborildi = {};

    function qaydEt(tur, tafsilot) {
        if (qayd_yuborildi[tur]) return;
        qayd_yuborildi[tur] = true;

        try {
            var fd = new FormData();
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) fd.append('csrf_token', csrf.content);
            fd.append('tur', tur);
            fd.append('manzil', location.pathname);
            if (tafsilot) fd.append('tafsilot', JSON.stringify(tafsilot));

            if (navigator.sendBeacon) {
                navigator.sendBeacon(QAYD_URL, fd);
            } else {
                fetch(QAYD_URL, { method: 'POST', body: fd, credentials: 'same-origin' });
            }
        } catch (e) {}
    }

    var style = document.createElement('style');
    style.textContent =
        'body[data-himoya] { -webkit-user-select: none !important; -moz-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; }' +
        'body[data-himoya] input, body[data-himoya] textarea, body[data-himoya] [contenteditable] { -webkit-user-select: text !important; user-select: text !important; }' +
        'body[data-himoya] img { -webkit-user-drag: none !important; pointer-events: none !important; }' +
        'body[data-himoya] a img { pointer-events: auto !important; }';
    document.head.appendChild(style);

    if (document.body) document.body.setAttribute('data-himoya', '1');
    else document.addEventListener('DOMContentLoaded', function () {
        document.body.setAttribute('data-himoya', '1');
    });

    document.addEventListener('contextmenu', function (e) {
        var target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return;
        e.preventDefault();
        qaydEt('right_click');
        ogohlantir();
    });

    document.addEventListener('copy', function (e) {
        var target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return;
        e.preventDefault();
        qaydEt('copy_attempt');
    });
    document.addEventListener('cut', function (e) {
        var target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return;
        e.preventDefault();
    });

    document.addEventListener('selectstart', function (e) {
        var target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) return;
        e.preventDefault();
    });

    document.addEventListener('dragstart', function (e) {
        if (e.target.tagName === 'IMG') e.preventDefault();
    });

    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();

        if (e.key === 'F12') {
            e.preventDefault();
            qaydEt('f12_press');
            ogohlantir();
            return;
        }

        if ((e.ctrlKey || e.metaKey) && (k === 'u' || k === 's')) {
            e.preventDefault();
            qaydEt('view_source_attempt', { kombinatsiya: 'Ctrl+' + k.toUpperCase() });
            ogohlantir();
            return;
        }

        if ((e.ctrlKey || e.metaKey) && k === 'p') {
            e.preventDefault();
            qaydEt('print_attempt');
            return;
        }

        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) {
            e.preventDefault();
            qaydEt('devtools_shortcut', { kombinatsiya: 'Ctrl+Shift+' + e.key });
            ogohlantir();
            return;
        }

        var target = e.target;
        var inputDa = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
        if (!inputDa && (e.ctrlKey || e.metaKey) && (k === 'c' || k === 'a' || k === 'x')) {
            if (k !== 'a') e.preventDefault();
        }
    });

    var ogohlantirildi = false;
    function ogohlantir() {
        if (ogohlantirildi) return;
        ogohlantirildi = true;

        var div = document.createElement('div');
        div.style.cssText =
            'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;' +
            'background:rgba(239,68,68,0.95);color:white;padding:14px 22px;border-radius:12px;' +
            'font-family:system-ui;font-weight:600;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.5);' +
            'backdrop-filter:blur(10px);max-width:90%;text-align:center;';
        div.textContent = '⚠️ Bu amal taqiqlangan. Urinishlar qayd qilinadi.';
        document.body.appendChild(div);

        setTimeout(function () {
            div.style.transition = 'opacity 0.3s';
            div.style.opacity = '0';
            setTimeout(function () { div.remove(); ogohlantirildi = false; }, 300);
        }, 2500);
    }

    var devtools_ochilgan = false;
    var devtools_threshold = 160;

    function devtoolsTekshir() {
        var w = window.outerWidth - window.innerWidth;
        var h = window.outerHeight - window.innerHeight;
        var ochiq = (w > devtools_threshold || h > devtools_threshold);

        if (ochiq && !devtools_ochilgan) {
            devtools_ochilgan = true;
            qaydEt('devtools_opened', { width_diff: w, height_diff: h });
            ogohlantir();
        } else if (!ochiq) {
            devtools_ochilgan = false;
        }
    }

    setInterval(devtoolsTekshir, 1500);
    devtoolsTekshir();

    var konsol_metodi = function () {};
    try {
        var nazar = /./;
        nazar.toString = function () {
            qaydEt('console_inspect');
            ogohlantir();
            return '';
        };

        if (typeof console !== 'undefined' && console.log) {
            setInterval(function () {
                console.log('%c', nazar);
                console.clear();
            }, 3000);
        }
    } catch (e) {}
})();
