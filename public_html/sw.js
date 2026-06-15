const VERSIYA = 'vp-v1';
const KESH_NOMI = `vatanparvar-${VERSIYA}`;

const ASOSIY_FAYLLAR = [
    '/',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/alpine.min.js',
    '/assets/img/logo-mark.svg',
    '/assets/img/logo.svg',
    '/manifest.webmanifest',
    '/offline.html',
];

const KESHDAN_FOYDALANISH = [
    '/assets/',
    '/uploads/savollar/',
    '/uploads/dizayn/',
];

const FAQAT_TARMOQ = [
    '/api/',
    '/admin/',
    '/auth/',
    '/install.php',
    '/migrate.php',
    '/check.php',
    '/bot.php',
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(KESH_NOMI).then((kesh) => {
            return kesh.addAll(ASOSIY_FAYLLAR).catch((err) => {
                console.warn('SW: ba\'zi fayllar keshlanmadi', err);
            });
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((nomlar) => {
            return Promise.all(
                nomlar.filter((nom) => nom.startsWith('vatanparvar-') && nom !== KESH_NOMI)
                       .map((nom) => caches.delete(nom))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);

    if (url.origin !== location.origin) return;

    if (e.request.method !== 'GET') return;

    if (FAQAT_TARMOQ.some((p) => url.pathname.startsWith(p))) {
        return;
    }

    const keshdan_aslida = KESHDAN_FOYDALANISH.some((p) => url.pathname.startsWith(p));

    if (keshdan_aslida) {
        e.respondWith(
            caches.match(e.request).then((javob) => {
                if (javob) {
                    fetch(e.request).then((yangi) => {
                        if (yangi.ok) {
                            caches.open(KESH_NOMI).then((kesh) => kesh.put(e.request, yangi));
                        }
                    }).catch(() => {});
                    return javob;
                }
                return fetch(e.request).then((yangi) => {
                    if (yangi.ok) {
                        const klon = yangi.clone();
                        caches.open(KESH_NOMI).then((kesh) => kesh.put(e.request, klon));
                    }
                    return yangi;
                });
            })
        );
        return;
    }

    e.respondWith(
        fetch(e.request).then((javob) => {
            if (javob.ok && javob.headers.get('content-type')?.includes('text/html')) {
                const klon = javob.clone();
                caches.open(KESH_NOMI).then((kesh) => kesh.put(e.request, klon));
            }
            return javob;
        }).catch(() => {
            return caches.match(e.request).then((keshlangan) => {
                if (keshlangan) return keshlangan;
                if (e.request.destination === 'document') {
                    return caches.match('/offline.html');
                }
                return new Response('Offline', { status: 503 });
            });
        })
    );
});

self.addEventListener('message', (e) => {
    if (e.data === 'kesh_tozala') {
        caches.delete(KESH_NOMI);
    }
});
