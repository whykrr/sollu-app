import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToastStore } from '@/store/toast';

let lastVisit = null;
let isRetrying = false;
let lastActiveTime = Date.now();
const INACTIVE_THRESHOLD_MS = 5 * 60 * 1000; // 5 minutes

/**
 * Check if the given URL or path belongs to a guest authentication page
 */
function isGuestAuthPath(pathOrUrl) {
    if (!pathOrUrl) return false;
    const path = typeof pathOrUrl === 'string'
        ? (pathOrUrl.startsWith('http') ? new URL(pathOrUrl).pathname : pathOrUrl)
        : (pathOrUrl.pathname || '');

    return (
        path.includes('/login') ||
        path.includes('/register') ||
        path.includes('/forgot') ||
        path.includes('/reset-password')
    );
}

/**
 * Update CSRF token in DOM, Axios defaults, and Laravel Echo
 */
export function updateCsrfToken(token) {
    if (!token) return;

    // 1. Update <meta name="csrf-token"> and document.cookie
    if (typeof document !== 'undefined') {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', token);
        }
        document.cookie = `XSRF-TOKEN=${encodeURIComponent(token)}; path=/; SameSite=Lax`;
    }

    // 2. Update Axios default header
    if (axios && axios.defaults && axios.defaults.headers) {
        if (!axios.defaults.headers.common) {
            axios.defaults.headers.common = {};
        }
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
    }

    // 3. Update Laravel Echo connector if active
    if (typeof window !== 'undefined' && window.Echo) {
        if (window.Echo.options) {
            window.Echo.options.csrfToken = token;
        }
        if (window.Echo.connector && window.Echo.connector.options) {
            window.Echo.connector.options.csrfToken = token;
            if (window.Echo.connector.options.auth && window.Echo.connector.options.auth.headers) {
                window.Echo.connector.options.auth.headers['X-CSRF-TOKEN'] = token;
                window.Echo.connector.options.auth.headers['X-XSRF-TOKEN'] = token;
            }
        }
    }
}

/**
 * Initialize CSRF Manager:
 * - Tracks mutating visits (POST, PUT, PATCH, DELETE)
 * - Intercepts HTTP 419 (TokenMismatchException)
 * - Prevents raw error modal from showing
 * - Silently refreshes CSRF token and retries visit if authenticated
 * - Redirects to login cleanly if unauthenticated
 * - Refreshes token when tab becomes visible again after inactive period
 */
export function initCsrfManager() {
    // 1. Track latest visit data before sending
    router.on('before', (event) => {
        const visit = event.detail.visit;
        if (visit && visit.method && visit.method.toLowerCase() !== 'get') {
            lastVisit = {
                url: visit.url,
                method: visit.method.toLowerCase(),
                data: visit.data,
                replace: visit.replace,
                preserveScroll: visit.preserveScroll ?? true,
                preserveState: visit.preserveState ?? true,
                headers: { ...(visit.headers || {}) },
                errorBag: visit.errorBag,
                forceFormData: visit.forceFormData,
            };
        }
        lastActiveTime = Date.now();
    });

    // 2. Intercept invalid responses (specifically HTTP 419 Page Expired)
    router.on('invalid', async (event) => {
        const response = event.detail.response;
        if (!response || response.status !== 419) {
            return;
        }

        // PREVENT the default raw HTML iframe modal from ever showing
        event.preventDefault();

        // Prevent infinite retry loop
        if (isRetrying) {
            isRetrying = false;
            return;
        }

        isRetrying = true;

        try {
            let token = response.data?.csrf_token;
            let isAuthenticated = response.data?.authenticated;

            // If token or auth status was not provided in the 419 JSON response, fetch it
            if (!token || typeof isAuthenticated === 'undefined') {
                const refreshRes = await axios.get('/csrf-token');
                token = refreshRes.data?.csrf_token;
                isAuthenticated = refreshRes.data?.authenticated;
            }

            // Sync updated token to DOM, cookies, and headers
            updateCsrfToken(token);

            const isGuestAction =
                isGuestAuthPath(lastVisit?.url) ||
                isGuestAuthPath(typeof window !== 'undefined' ? window.location.pathname : '');

            // If user is authenticated OR performing a guest action (like login, register, forgot-password), auto-retry!
            if ((isAuthenticated || isGuestAction) && lastVisit) {
                const visitToRetry = { ...lastVisit };
                lastVisit = null; // Clear to prevent double retry

                if (!isGuestAction) {
                    try {
                        const toast = useToastStore();
                        toast.info('Sesi formulir diperbarui. Mengirim ulang data...', { duration: 2500 });
                    } catch {
                        // Ignore toast store errors
                    }
                }

                router.visit(visitToRetry.url, {
                    method: visitToRetry.method,
                    data: visitToRetry.data,
                    replace: visitToRetry.replace,
                    preserveScroll: visitToRetry.preserveScroll,
                    preserveState: visitToRetry.preserveState,
                    headers: {
                        ...visitToRetry.headers,
                        'X-CSRF-TOKEN': token,
                        'X-XSRF-TOKEN': token,
                    },
                    errorBag: visitToRetry.errorBag,
                    forceFormData: visitToRetry.forceFormData,
                    onFinish: () => {
                        isRetrying = false;
                    },
                });
                return;
            }

            // Only redirect to login if user was on an authenticated internal page and session expired
            isRetrying = false;
            const isCockpit =
                typeof window !== 'undefined' &&
                (window.location.hostname.startsWith('cockpit.') ||
                window.location.pathname.startsWith('/cockpit'));

            const loginUrl = typeof route === 'function'
                ? (isCockpit ? route('cockpit.login') : route('login'))
                : '/login';

            if (typeof window !== 'undefined' && !window.location.pathname.endsWith('/login')) {
                window.location.href = loginUrl;
            }
        } catch (error) {
            isRetrying = false;
            console.error('Failed to refresh CSRF token:', error);
        }
    });

    // 3. Tab visibility / focus listener: refresh token when user returns after being away > 5 minutes
    const handleVisibilityOrFocus = async () => {
        const now = Date.now();
        if (now - lastActiveTime > INACTIVE_THRESHOLD_MS) {
            try {
                const res = await axios.get('/csrf-token');
                if (res.data?.csrf_token) {
                    updateCsrfToken(res.data.csrf_token);
                }
            } catch {
                // Silently ignore ping errors
            }
        }
        lastActiveTime = Date.now();
    };

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                handleVisibilityOrFocus();
            }
        });
        window.addEventListener('focus', handleVisibilityOrFocus);
    }
}
