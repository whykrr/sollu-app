import { defineStore } from "pinia";

export const useAppStore = defineStore("app", {
    state: () => ({
        sidebar: {
            minimize: false,
            show: false,
            active: null,
        },

    }),
    actions: {
        getActive(route) {
            this.sidebar.active = route;
        },
        minimize() {
            this.sidebar.minimize = true;
            this.sidebar.show = false;
        },
        maximize() {
            this.sidebar.show = false;
            this.sidebar.minimize = false;
        },
        switch() {
            this.sidebar.show = !this.sidebar.show;
        },
        show() {
            this.sidebar.show = true;
        },
        hide() {
            this.sidebar.show = false;
        },
    },
})
