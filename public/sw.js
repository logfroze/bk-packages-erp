const CACHE_NAME = 'bk-packages-erp-v4';

const APP_SHELL_URLS = [
  '/',
  '/production',
  '/production/conversion',
  '/inventory',
  '/gate-register',
  '/khata',
  '/attendance',
  '/attendance/weekly',
  '/petty-cash',
  '/settings/numbering',
  '/reports',
  '/manifest.json',
  '/icons/icon.svg',
  '/js/fast-nav.js',
  '/js/offline-sync.js',
  '/js/ai-copilot.js'
];

// Install: Pre-cache core app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(
        APP_SHELL_URLS.map((url) =>
          fetch(url).then((res) => {
            if (res.ok) return cache.put(url, res);
          }).catch(() => null)
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate: Clean up older cache versions immediately
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch Strategy:
// 1. Static CDNs & Assets -> Cache-first with background revalidation
// 2. Application HTML Pages -> Stale-While-Revalidate (Instant 0ms display + background fresh data fetch)
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Skip offline-sync ping & replay from caching
  if (url.pathname.startsWith('/offline-sync')) return;

  // Static CDN & Asset requests -> Cache-first
  if (
    url.origin !== self.location.origin ||
    url.pathname.startsWith('/js/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname === '/manifest.json'
  ) {
    event.respondWith(
      caches.match(req).then((cached) => {
        const fetchPromise = fetch(req).then((networkRes) => {
          if (networkRes && networkRes.ok) {
            const copy = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return networkRes;
        }).catch(() => cached);

        return cached || fetchPromise;
      })
    );
    return;
  }

  // Application Pages -> Stale-While-Revalidate (0ms instantaneous cached load + silent background refresh)
  event.respondWith(
    caches.match(req).then((cachedResponse) => {
      const fetchPromise = fetch(req)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            const copy = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return networkResponse;
        })
        .catch(() => {
          // If offline and request wasn't cached, try fallback to dashboard
          if (cachedResponse) return cachedResponse;
          return caches.match('/');
        });

      // If cached response exists, return it immediately for 0ms load!
      // The fetchPromise runs in the background to update the cache for next view.
      return cachedResponse || fetchPromise;
    })
  );
});
