// Service Worker — network-only (no offline caching, data is cloud-based)
const VERSION = 'v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

// Always fetch from network — no caching
self.addEventListener('fetch', (e) => {
    e.respondWith(fetch(e.request));
});
