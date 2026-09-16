import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const isTls =
    (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https' ||
    (typeof window !== 'undefined' && window.location.protocol === 'https:')
const csrfToken =
    typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : null

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:
        import.meta.env.VITE_REVERB_HOST ||
        (typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'),
    wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 80,
    wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
    forceTLS: isTls,
    enabledTransports: ['ws', 'wss'],
    csrfToken: csrfToken,
})

export default window.Echo
