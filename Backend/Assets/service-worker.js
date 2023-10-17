self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
});

self.addEventListener('push', (event) => {
    if (!(self.Notification && self.Notification.permission === "granted")) {
        return;
    }
    const timestamp = Math.floor(Date.now());

    data = event.data?.json() ?? {};
    const title = data.title || 'Alert'
    const body = data.body || 'Push message has no payload'
    const icon = data.icon || 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAAsTAAALEwEAmpwYAAABVElEQVQ4jX3RTUtVURTG8d+53oQG4iCKRgqCA7EgCIpu3WH4MmogCDnyGyTRJ2gWQTTsEzQMahQaDVRCmzbSWQ4uJL7QC2Ldqw3OPrHu7uiCzdrr2c/6733OKvTHIl6m/e8n960+m9Pa6rgYPN/wDkvQzADTGKqKnQMPul2KgtPTf54hPEIbrUYGuB2LX8f0ehRl2cu8N/E0AsYwGh3NgfL2FGvpguVgWYiAOyn/xKb/40/S54J2OQLupbyLjzWA6i0nQTuJgLsp7+NDDQAm8TrUh9UULuF62newofxpA8HcxpcM+KZ6wUQQv+K7ct4xBrN6C48rwK1w8D7llayhiz1s4xVu4Kj6hHYwzitnPJ4BPmEKR1GsAK2gPVQfx3kzNHANV85oinGhTmzqf/5nvFDOvMBzXD2P2sRsqN/qn/MCZtJ+uA7QwAh+pJXPeT2cdeoAfwEFgEOY8ono4wAAAABJRU5ErkJggg=='
    const vibrate = data.vibrate || [50, 50, 50]
    const url = data.url || false
    const actions = data.actions || []
    const options = {
        body,
        icon,
        badge: icon,
        vibrate,
        tag: 'awp-push-notice',
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
            url,
        },
        actions,
        timestamp
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    console.log('Clicked')
    if (event.action !== 'open_url') return;
    const url = event.notification.data.url
    event.notification.close(); // Android needs explicit close.
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((windowClients) => {
            // Check if there is already a window/tab open with the target URL
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                // If so, just focus it.
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, then open the target URL in a new window/tab.
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});