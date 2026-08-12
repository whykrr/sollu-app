import i18n from '@/i18n'
import { defineStore } from 'pinia'
import { useToastStore } from './toast'

export { useToastStore }

const HEADER_DELETE = 'Hapus Data'
const HEADER_SOFT_DELETE = 'Arsipkan'
const MESSAGE_DELETE = 'Apakah anda yakin akan menghapus data ini?'
const MESSAGE_SOFT_DELETE = 'Data ini akan dipindahkan ke arsip, data dapat dikembalikan jika diperlukan kembali.'

export const useModalStore = defineStore('modal', {
    state: () => ({
        // Legacy delete modal state for backward compatibility
        delete: {
            isVisible: false,
            url: null,
            header: null,
            msg: null,
        },
        // Generic modal state for any component
        activeModal: {
            isVisible: false,
            type: 'confirm', // 'confirm' | 'alert' | 'info' | 'success' | 'warning' | 'danger'
            title: '',
            message: '',
            confirmText: 'Ya',
            cancelText: 'Batal',
            confirmClass: 'btn-main',
            showCancel: true,
            size: 'max-w-lg',
            onConfirm: null,
            onCancel: null,
        },
    }),
    actions: {
        /**
         * Open a generic modal from anywhere
         * @param {Object} options
         */
        open(options = {}) {
            this.activeModal = {
                isVisible: true,
                type: options.type || 'info',
                title: options.title || 'Konfirmasi',
                message: options.message || options.msg || '',
                confirmText: options.confirmText || 'Ya',
                cancelText: options.cancelText || 'Batal',
                confirmClass: options.confirmClass || (options.type === 'danger' ? 'btn-danger' : 'btn-main'),
                showCancel: options.showCancel !== undefined ? options.showCancel : true,
                size: options.size || 'max-w-lg',
                onConfirm: options.onConfirm || null,
                onCancel: options.onCancel || null,
            }
        },

        /**
         * Shortcut for a confirmation dialog
         */
        confirm({ title = 'Konfirmasi Tindakan', message, type = 'warning', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', confirmClass, onConfirm, onCancel }) {
            this.open({
                type,
                title,
                message,
                confirmText,
                cancelText,
                confirmClass: confirmClass || (type === 'danger' ? 'btn-danger bg-rose-600 hover:bg-rose-700 text-white' : 'btn-main'),
                showCancel: true,
                onConfirm,
                onCancel,
            })
        },

        /**
         * Shortcut for an alert modal dialog
         */
        alert({ title = 'Pemberitahuan', message, type = 'info', confirmText = 'Mengerti', onConfirm }) {
            this.open({
                type,
                title,
                message,
                confirmText,
                showCancel: false,
                onConfirm,
            })
        },

        /**
         * Close active generic modal
         */
        close() {
            this.activeModal.isVisible = false
        },

        // Legacy delete methods for backward compatibility
        openModalDelete(url) {
            this.delete.isVisible = true
            this.delete.url = url
            this.delete.header = HEADER_DELETE
            this.delete.msg = MESSAGE_DELETE
        },

        openModalSoftDelete(url) {
            this.delete.isVisible = true
            this.delete.url = url
            this.delete.header = HEADER_SOFT_DELETE
            this.delete.msg = MESSAGE_SOFT_DELETE
        },

        closeModalDelete() {
            this.delete.isVisible = false
            this.delete.url = null
            this.delete.header = null
            this.delete.msg = null
        },
    },
})
