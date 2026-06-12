/**
 * VatanParvar Yaypan — Service Worker
 *
 * Vazifalar:
 *  - Statik resurslarni keshlash (oflayn rejim)
 *  - Sahifa ochilganda bildirishnomalarni tekshirish
 */
const KESH_NOMI = 'vatanparvar-v1';
const STATIK_FAYLLAR = [
  '/manifest.json',
];

// ----- Install -----
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(KESH_NOMI).then((cache) => cache.addAll(STATIK_FAYLLAR))
  );
  self.skipWaiting();
});

// ----- Activate -----
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((kalitlar) =>
      Promise.all(kalitlar.filter((k) => k !== KESH_NOMI).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// ----- Fetch (network-first dynamic, cache-first static) -----
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET') return;

  // API so'rovlarini keshlamaslik (har doim yangi)
  if (url.pathname.startsWith('/api/')) return;

  // Statik fayllar — cache-first
  if (/\.(woff2?|ttf|svg|png|jpe?g|gif|webp|ico|css|js)$/i.test(url.pathname)) {
    event.respondWith(
      caches.match(event.request).then((kesh) => {
        return kesh || fetch(event.request).then((javob) => {
          if (javob.ok) {
            const klon = javob.clone();
            caches.open(KESH_NOMI).then((cache) => cache.put(event.request, klon));
          }
          return javob;
        }).catch(() => kesh);
      })
    );
  }
});

// ----- Push notification (kelajakda Web Push uchun tayyor) -----
self.addEventListener('push', (event) => {
  if (!event.data) return;
  let data = {};
  try { data = event.data.json(); } catch (e) { data = { title: event.data.text() }; }

  event.waitUntil(
    self.registration.showNotification(data.title || 'VatanParvar', {
      body: data.body || '',
      icon: '/manifest.json',
      badge: '/manifest.json',
      data: { url: data.url || '/' },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';
  event.waitUntil(self.clients.openWindow(url));
});
