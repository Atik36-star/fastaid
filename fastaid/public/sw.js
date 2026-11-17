self.addEventListener('install', e => e.waitUntil(caches.open('fastaid-v2').then(c => c.addAll(['assets/css/style.css','assets/js/main.js','assets/js/api.js']))));

self.addEventListener('activate', e => e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('fastaid-') && key !== 'fastaid-v2').map(key => caches.delete(key))))));

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (url.pathname.endsWith('.html')) {
    e.respondWith(fetch(e.request));
    return;
  }
  if (url.pathname.endsWith('/api.php') || url.pathname.endsWith('api.php')) {
    e.respondWith(fetch(e.request));
    return;
  }
  e.respondWith(caches.match(e.request).then(r => r || fetch(e.request)));
});
