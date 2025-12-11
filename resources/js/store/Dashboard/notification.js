import i18n from '@/i18n'
import { defineStore } from 'pinia'

const HEADER_DELETE = 'Hapus Data'
const HEADER_SOFT_DELETE = 'Pindahkan ke sampah'
const HEADER_ARCHIVE = 'Pindahkan ke arsip'
const MESSAGE_DELETE = 'Apakah anda yakin akan menghapus data ini?'
const MESSAGE_SOFT_DELETE = 'Data ini akan dipindahkan ke sampah, data dapat dikembalikan jika diperlukan kembali.'
const MESSAGE_ARCHIVE = 'Data ini akan dipindahkan ke arsip, data dapat dikembalikan jika diperlukan kembali.'


export const useModalStore = defineStore('modal', {
    state: () => ({
        delete: {
            isVisible: false,
            url: null,
            header: null,
            msg: null,
        },
    }),
    actions: {
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
        openModalArchive(url) {
            this.delete.isVisible = true
            this.delete.url = url
            this.delete.header = HEADER_ARCHIVE
            this.delete.msg = MESSAGE_ARCHIVE
        },
        closeModalDelete() {
            this.delete.isVisible = false
            this.delete.url = null
            this.delete.header = null
            this.delete.msg = null
        },
    },
})
