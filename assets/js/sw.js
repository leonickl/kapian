const CACHE_NAME = "site-cache-v2"; // bump on every release

const PRECACHE = ["/offline.html", "/manifest.json", "/icon.svg"];

self.addEventListener("install", (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE)),
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener("fetch", (event) => {
    const req = event.request;
    if (req.method !== "GET" || new URL(req.url).origin !== self.location.origin) return;

    // HTML pages and JS/CSS: network-first, cache only as offline fallback
    if (req.mode === "navigate" || /\.(js|css)$/.test(new URL(req.url).pathname)) {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(req, copy));
                    return res;
                })
                .catch(() =>
                    caches.match(req).then(
                        (r) => r || (req.mode === "navigate" ? caches.match("/offline.html") : Response.error()),
                    ),
                ),
        );
        return;
    }

    // Icons, manifest, images: cache-first is fine
    event.respondWith(caches.match(req).then((r) => r || fetch(req)));
});