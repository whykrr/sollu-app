import i18n from '@/i18n';
import { defineStore } from 'pinia';

const HEADER_DELETE = 'Delete data'
const HEADER_SOFT_DELETE = 'Move to trash'
const MESSAGE_DELETE = 'Are you sure you want to delete this data?'
const MESSAGE_SOFT_DELETE = 'This item will be archived on trash. You can restore it later if needed.'


export const useModalStore = defineStore('modal', {
    state: () => ({
        delete: {
            isVisible: false,
            url: null,
            header: null,
            msg: null
        },
    }),
    actions: {
        openModalDelete(url) {
            this.delete.isVisible = true;
            this.delete.url = url;
            this.delete.header = i18n.global.t('modal.deleteHeader')
            this.delete.msg = i18n.global.t('modal.deleteMsg')
        },
        openModalSoftDelete(url) {
            this.delete.isVisible = true;
            this.delete.url = url;
            this.delete.header = HEADER_SOFT_DELETE
            this.delete.msg = MESSAGE_SOFT_DELETE
        },
        closeModalDelete() {
            this.delete.isVisible = false;
            this.delete.url = null;
            this.delete.header = null;
            this.delete.msg = null;
        },
    },
});
