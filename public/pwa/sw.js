// Tenant Trust PWA Service Worker
// Scope: served per-panel via Service-Worker-Allowed header.
// Strategy: cache static assets (cache-first), everything else network-only.
const CACHE = 'tenant-trust-pwa-v1';
const PRECACHE = [
  '/pwa/icons/icon-192.png',
  '/pwa/icons/icon-512.png',
  '/pwa/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Static assets: cache-first, fall back to network, populate cache.
  if (/\.(css|js|woff2?|png|svg|ico|jpg|jpeg|webp|gif)(\?.*)?$/.test(url.pathname)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
          return response;
        });
      })
    );
    return;
  }

  // Everything else (HTML, Livewire, API): network-only.
  // Never cache authenticated pages or POST data.
  event.respondWith(fetch(request));
});

// ---------- Push notifications ----------

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) { /* malformed payload — show fallback */ }

  const title = data.title || 'Tenant Trust';
  const options = {
    body: data.body || '',
    icon: data.icon || '/pwa/icons/icon-192.png',
    badge: data.badge || '/pwa/icons/icon-192.png',
    data: { url: data.url || '/' },
    vibrate: [100, 50, 100],
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if ('focus' in client) {
          client.focus();
          if ('navigate' in client) {
            try { client.navigate(url); } catch (e) { /* cross-scope; ignore */ }
          }
          return;
        }
      }
      return clients.openWindow(url);
    })
  );
});
