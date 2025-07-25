import { defineStore } from 'pinia';

export const usePageStore = defineStore('page', {
    state: () => ({
        title: '',
    }),
});
