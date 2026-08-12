import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),
    actions: {
        /**
         * Add a new toast notification.
         * @param {Object} toast
         * @param {string} [toast.type='info'] - 'success' | 'info' | 'warning' | 'danger' | 'error'
         * @param {string} [toast.title] - Title header for the toast
         * @param {string} toast.message - Main body text or detail
         * @param {number} [toast.duration=4000] - Duration in ms before auto-dismiss (0 for persistent)
         * @param {Object} [toast.action] - Optional action object { text: string, onClick: function }
         * @param {boolean} [toast.dismissible=true] - Whether manual dismiss button is visible
         * @param {Object|string} [toast.icon] - Custom FontAwesome icon
         * @returns {number} toast id
         */
        addToast(toast) {
            const id = Date.now() + Math.random()
            const type = toast.type === 'error' ? 'danger' : (toast.type || 'info')
            
            const newToast = {
                id,
                type,
                title: toast.title || this.getDefaultTitle(type),
                message: typeof toast === 'string' ? toast : (toast.message || ''),
                duration: toast.duration !== undefined ? toast.duration : 4000,
                action: toast.action || null,
                dismissible: toast.dismissible !== undefined ? toast.dismissible : true,
                icon: toast.icon || null,
            }

            this.toasts.push(newToast)

            if (newToast.duration > 0) {
                setTimeout(() => {
                    this.removeToast(id)
                }, newToast.duration)
            }

            return id
        },

        /**
         * Remove toast by ID
         * @param {number|string} id 
         */
        removeToast(id) {
            const index = this.toasts.findIndex((t) => t.id === id)
            if (index !== -1) {
                this.toasts.splice(index, 1)
            }
        },

        /**
         * Clear all toasts
         */
        clearToasts() {
            this.toasts = []
        },

        /**
         * Shortcut for success toast
         */
        success(message, options = {}) {
            return this.addToast({
                type: 'success',
                message,
                ...options,
            })
        },

        /**
         * Shortcut for info toast
         */
        info(message, options = {}) {
            return this.addToast({
                type: 'info',
                message,
                ...options,
            })
        },

        /**
         * Shortcut for warning toast
         */
        warning(message, options = {}) {
            return this.addToast({
                type: 'warning',
                message,
                ...options,
            })
        },

        /**
         * Shortcut for danger / error toast
         */
        danger(message, options = {}) {
            return this.addToast({
                type: 'danger',
                message,
                ...options,
            })
        },

        /**
         * Alias shortcut for error toast
         */
        error(message, options = {}) {
            return this.danger(message, options)
        },

        /**
         * Helper to provide default titles per type if omitted
         */
        getDefaultTitle(type) {
            switch (type) {
                case 'success':
                    return 'Berhasil!'
                case 'danger':
                    return 'Gagal!'
                case 'warning':
                    return 'Peringatan!'
                case 'info':
                default:
                    return 'Informasi'
            }
        },
    },
})
