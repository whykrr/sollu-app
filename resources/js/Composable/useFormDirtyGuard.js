import { computed, inject, watch, onUnmounted, isRef } from 'vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'

/**
 * Composable untuk menangani lifecycle form, deteksi perubahan belum disimpan (dirty state),
 * dan konfirmasi pembatalan pada Drawer PopUpPage maupun Modal Dialog.
 *
 * @param {Object} options
 * @param {Object} [options.form] - Instance Inertia useForm()
 * @param {import('vue').Ref<boolean>|import('vue').ComputedRef<boolean>|Function} [options.isDirty] - Custom dirty state jika tidak menggunakan useForm
 * @param {Function} [options.onDiscard] - Callback opsional saat pengguna memilih 'Ya, Buang Perubahan'
 * @param {Function} [options.onClose] - Custom close handler (default: popUpStore.close())
 * @param {string} [options.title] - Judul konfirmasi (default: 'Perubahan Belum Disimpan')
 * @param {string} [options.message] - Pesan konfirmasi (default standard UX Wording)
 * @param {string} [options.confirmText] - Label tombol konfirmasi (default: 'Ya, Buang Perubahan')
 * @param {string} [options.cancelText] - Label tombol batal/kembali (default: 'Lanjut Mengisi')
 *
 * @returns {{
 *   isDirty: import('vue').ComputedRef<boolean>,
 *   handleCancel: (force?: boolean) => void,
 *   forceClose: () => void,
 *   confirmDiscard: (force?: boolean) => void
 * }}
 *
 * @example
 * ```javascript
 * import { useForm } from '@inertiajs/vue3'
 * import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
 *
 * const form = useForm({ name: '', uom_id: '' })
 * const { handleCancel, forceClose, isDirty } = useFormDirtyGuard({ form })
 *
 * const submit = () => {
 *     form.post(route('items.store'), {
 *         onSuccess: () => forceClose(),
 *     })
 * }
 * ```
 */
export function useFormDirtyGuard(options = {}) {
    const modalStore = useModalStore()
    const popUpStore = usePopUpStore()

    const setPopUpDirty = inject('setPopUpDirty', null)

    const computedIsDirty = computed(() => {
        if (options.form && typeof options.form.isDirty !== 'undefined') {
            return Boolean(options.form.isDirty)
        }
        if (isRef(options.isDirty)) {
            return Boolean(options.isDirty.value)
        }
        if (typeof options.isDirty === 'function') {
            return Boolean(options.isDirty())
        }
        if (typeof options.isDirty === 'boolean') {
            return options.isDirty
        }
        return false
    })

    // Sinkronisasi status dirty ke PopUpPage (jika dirender di dalam PopUpPage)
    if (setPopUpDirty) {
        watch(
            computedIsDirty,
            newVal => {
                setPopUpDirty(Boolean(newVal))
            },
            { immediate: true }
        )

        onUnmounted(() => {
            setPopUpDirty(false)
        })
    }

    const closeTarget = () => {
        if (setPopUpDirty) {
            setPopUpDirty(false)
        }
        if (typeof options.onClose === 'function') {
            options.onClose()
        } else {
            popUpStore.close()
        }
    }

    /**
     * Menutup drawer / form secara paksa (misal saat submit berhasil / onSuccess)
     */
    const forceClose = () => {
        closeTarget()
    }

    /**
     * Menangani aksi klik tombol Batal atau penutupan form
     * @param {boolean} [force=false] - Jika true, bypass dialog konfirmasi
     */
    const handleCancel = (force = false) => {
        if (force === true || !computedIsDirty.value) {
            if (options.form && typeof options.form.clearErrors === 'function') {
                options.form.clearErrors()
            }
            closeTarget()
            return
        }

        modalStore.confirm({
            title: options.title || 'Perubahan Belum Disimpan',
            message:
                options.message ||
                'Kamu memiliki perubahan data yang belum disimpan. Yakin mau membatalkan dan keluar dari formulir ini?',
            type: 'warning',
            confirmText: options.confirmText || 'Ya, Buang Perubahan',
            cancelText: options.cancelText || 'Lanjut Mengisi',
            confirmClass: 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
            onConfirm: () => {
                if (typeof options.onDiscard === 'function') {
                    options.onDiscard()
                } else if (options.form) {
                    if (typeof options.form.reset === 'function') {
                        options.form.reset()
                    }
                    if (typeof options.form.clearErrors === 'function') {
                        options.form.clearErrors()
                    }
                }
                closeTarget()
            },
            onCancel: () => {
                // Tetap di form, data tetap aman
            },
        })
    }

    return {
        isDirty: computedIsDirty,
        handleCancel,
        forceClose,
        confirmDiscard: handleCancel,
    }
}
