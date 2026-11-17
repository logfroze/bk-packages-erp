const CACHE_NAME = 'bk-packages-erp-v5';

const STATIC_ASSET_URLS = [
  '/manifest.json',
  '/icons/icon.svg',
  '/js/fast-nav.js',
  '/js/offline-sync.js',
  '/js/ai-copilot.js'
];

// Install: Cache essential static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(
        STATIC_ASSET_URLS.map((url) =>
          fetch(url).then((res) => {
            if (res.ok) return cache.put(url, res);
          }).catch(() => null)
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate: Immediately purge all older stale caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch Strategy:
// 1. Static Assets (JS, CSS, Icons, Fonts) -> Cache-first with background revalidation
// 2. HTML Navigation & Data Pages -> Network-First (Fresh data when online, cached fallback only when offline)
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Never cache API sync or dynamic API replay endpoints
  if (url.pathname.startsWith('/offline-sync') || url.pathname.includes('/logout') || url.pathname.includes('/login')) {
    return;
  }

  // 1. Static Assets -> Cache First with revalidate
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

  // 2. HTML Navigation Requests -> Network First (Never serve stale dashboard on other routes)
  if (req.mode === 'navigate' || req.headers.get('Accept')?.includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            const copy = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return networkResponse;
        })
        .catch(async () => {
          // Network failed: offline mode. Look up cached version of THIS exact request
          const cached = await caches.match(req);
          if (cached) return cached;

          // If this specific page wasn't cached, return a clean offline fallback notice
          return new Response(
            `<!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="utf-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <title>Offline Mode — BK Packages ERP</title>
              <script src="https://cdn.tailwindcss.com"></script>
            </head>
            <body class="bg-slate-50 min-h-screen flex items-center justify-center p-6 text-slate-800 font-sans">
              <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm text-center">
                <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center mx-auto mb-4 font-bold text-xl">
                  ⚡
                </div>
                <h2 class="text-xl font-bold text-slate-900 mb-2">You are currently offline</h2>
                <p class="text-sm text-slate-500 mb-6">This page has not been cached yet. Reconnect to the local network or internet to load fresh data.</p>
                <div class="flex gap-3 justify-center">
                  <a href="/" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Go to Dashboard</a>
                  <button onclick="window.location.reload()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50">Retry Connection</button>
                </div>
              </div>
            </body>
            </html>`,
            { headers: { 'Content-Type': 'text/html' } }
          );
        })
    );
  }
});
