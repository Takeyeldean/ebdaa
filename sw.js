// Service Worker for Ebdaa - Performance Optimization
const CACHE_NAME = 'ebdaa-v1.0.0';
const STATIC_CACHE = 'ebdaa-static-v1.0.0';
const DYNAMIC_CACHE = 'ebdaa-dynamic-v1.0.0';

// Critical resources to cache immediately
const CRITICAL_RESOURCES = [
  '/',
  '/index.php',
  '/dashboard.php',
  '/admin.php',
  '/assets/css/optimized.css',
  '/assets/js/optimized.js',
  'https://cdn.tailwindcss.com',
  'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install event - cache critical resources
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Caching critical resources');
        return cache.addAll(CRITICAL_RESOURCES);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // Skip external requests that aren't critical
  if (url.origin !== location.origin && !CRITICAL_RESOURCES.includes(request.url)) return;

  event.respondWith(
    caches.match(request)
      .then(cachedResponse => {
        if (cachedResponse) {
          console.log('Serving from cache:', request.url);
          return cachedResponse;
        }

        console.log('Fetching from network:', request.url);
        return fetch(request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            // Cache dynamic content
            if (url.pathname.includes('.php') || url.pathname.includes('api/')) {
              caches.open(DYNAMIC_CACHE)
                .then(cache => {
                  cache.put(request, responseToCache);
                });
            }

            return response;
          })
          .catch(error => {
            console.log('Fetch failed:', error);
            // Return offline page for navigation requests
            if (request.destination === 'document') {
              return caches.match('/offline.html');
            }
            throw error;
          });
      })
  );
});

// Background sync for form submissions
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

async function doBackgroundSync() {
  try {
    const pendingSubmissions = await getPendingSubmissions();
    for (const submission of pendingSubmissions) {
      await fetch(submission.url, {
        method: 'POST',
        body: submission.data
      });
      await removePendingSubmission(submission.id);
    }
  } catch (error) {
    console.log('Background sync failed:', error);
  }
}

// Cache management
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => caches.delete(cacheName))
        );
      })
    );
  }
});

// Helper functions
async function getPendingSubmissions() {
  // Implementation for getting pending form submissions
  return [];
}

async function removePendingSubmission(id) {
  // Implementation for removing submitted form data
  return true;
}
