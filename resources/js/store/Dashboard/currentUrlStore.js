import { defineStore } from 'pinia';

export const useCurrentUrlStore = defineStore('history', {
    state: () => ({
        url: window.location.href,
    }),
    actions: {
        setUrl(newUrl) {
            this.url = newUrl
        },
    },
});
