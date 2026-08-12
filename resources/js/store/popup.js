import { defineStore } from 'pinia'

export const usePopUpStore = defineStore('popup', {
    state: () => ({
        activePopUp: {
            isVisible: false,
            title: '',
            subTitle: null,
            size: 'md',
            component: null,
            props: {},
            events: {},
            onClose: null,
        },
    }),
    actions: {
        /**
         * Open a dynamic PopUpPage side-panel from anywhere
         * @param {Object} options
         * @param {string} options.title
         * @param {string} [options.subTitle]
         * @param {string} [options.size='md'] - 'sm' | 'md' | 'lg' | 'xl' | '2xl'
         * @param {Object|Function} options.component - Vue component
         * @param {Object} [options.props] - Props passed to component
         * @param {Object} [options.events] - Event handlers passed to component
         * @param {Function} [options.onClose] - Callback when side-panel is closed
         */
        open(options = {}) {
            this.activePopUp = {
                isVisible: true,
                title: options.title || '',
                subTitle: options.subTitle || null,
                size: options.size || 'md',
                component: options.component || null,
                props: options.props || {},
                events: options.events || {},
                onClose: options.onClose || null,
            }
        },

        /**
         * Close active PopUpPage side-panel
         */
        close() {
            if (typeof this.activePopUp.onClose === 'function') {
                this.activePopUp.onClose()
            }
            this.activePopUp.isVisible = false
            this.activePopUp.component = null
        },
    },
})
