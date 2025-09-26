// ULTRA-FAST Service Worker for Maximum Performance
const CACHE_NAME = 'ebdaa-ultra-fast-v1.0.0';
const STATIC_CACHE = 'static-ultra-fast-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-ultra-fast-v1.0.0';

const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/dashboard.php',
    '/profile.php',
    '/student_questions.php',
    '/admin.php',
    '/admin_questions.php',
    '/manage_group.php',
    '/admin_invitations.php',
    '/add_group.php',
    '/assets/css/ultra-fast.css',
    '/assets/js/ultra-fast.js',
    '/assets/css/dark-mode.css',
    '/assets/js/dark-mode.js',
    'https://cdn.tailwindcss.com',
    'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2'
];

// Ultra-fast install event
self.addEventListener('install', event => {
    console.log('⚡ Ultra-Fast SW: Installing...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('⚡ Ultra-Fast SW: Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('⚡ Ultra-Fast SW: Installation complete');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('⚡ Ultra-Fast SW: Installation failed', error);
            })
    );
});

// Ultra-fast activate event
self.addEventListener('activate', event => {
    console.log('⚡ Ultra-Fast SW: Activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('⚡ Ultra-Fast SW: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('⚡ Ultra-Fast SW: Activation complete');
                return self.clients.claim();
            })
    );
});

// Ultra-fast fetch with intelligent caching
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip external requests that don't need caching
    if (url.origin !== location.origin && !url.hostname.includes('cdn') && !url.hostname.includes('fonts')) {
        return;
    }

    event.respondWith(
        caches.match(request)
            .then(cachedResponse => {
                // Return cached version if available
                if (cachedResponse) {
                    console.log('⚡ Ultra-Fast SW: Serving from cache', request.url);
                    return cachedResponse;
                }

                // Otherwise fetch from network with timeout
                console.log('⚡ Ultra-Fast SW: Fetching from network', request.url);
                return fetch(request, {
                    timeout: 5000 // 5 second timeout
                })
                    .then(response => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response for caching
                        const responseToCache = response.clone();

                        // Cache dynamic content
                        if (request.url.includes('.php') || request.url.includes('api/')) {
                            caches.open(DYNAMIC_CACHE)
                                .then(cache => {
                                    cache.put(request, responseToCache);
                                });
                        }

                        return response;
                    })
                    .catch(error => {
                        console.error('⚡ Ultra-Fast SW: Fetch failed', error);
                        // Return offline page for navigation requests
                        if (request.mode === 'navigate') {
                            return caches.match('/index.php');
                        }
                        throw error;
                    });
            })
    );
});

// Ultra-fast background sync
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        console.log('⚡ Ultra-Fast SW: Background sync triggered');
        event.waitUntil(
            handleOfflineSubmissions()
        );
    }
});

// Ultra-fast offline form handling
async function handleOfflineSubmissions() {
    try {
        const pendingSubmissions = await getPendingSubmissions();
        
        for (const submission of pendingSubmissions) {
            try {
                const response = await fetch(submission.url, {
                    method: submission.method,
                    body: submission.data,
                    headers: submission.headers
                });
                
                if (response.ok) {
                    await removePendingSubmission(submission.id);
                    console.log('⚡ Ultra-Fast SW: Offline submission synced', submission.id);
                }
            } catch (error) {
                console.error('⚡ Ultra-Fast SW: Failed to sync submission', error);
            }
        }
    } catch (error) {
        console.error('⚡ Ultra-Fast SW: Background sync failed', error);
    }
}

// Ultra-fast IndexedDB operations
function getPendingSubmissions() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ebdaa-offline', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['submissions'], 'readonly');
            const store = transaction.objectStore('submissions');
            const getAllRequest = store.getAll();
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
    });
}

function removePendingSubmission(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ebdaa-offline', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['submissions'], 'readwrite');
            const store = transaction.objectStore('submissions');
            const deleteRequest = store.delete(id);
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// Ultra-fast message handling
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(STATIC_CACHE)
                .then(cache => cache.addAll(event.data.urls))
        );
    }
});

console.log('⚡ Ultra-Fast Service Worker: Loaded successfully');