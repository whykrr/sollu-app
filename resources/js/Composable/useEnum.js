import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Composable untuk mengakses PHP Enum yang dibagikan secara global via Inertia Shared Props.
 * Digunakan untuk validasi kondisi status/tipe tanpa hardcoded string literals (magic strings).
 */
export function useEnum() {
    const page = usePage()
    const enums = computed(() => page.props.enums || {})

    /**
     * Mengambil array opsi siap pakai untuk DropdownField atau SelectionGroupField.
     * @param {string} enumName - Nama Enum PHP (e.g. 'AdjustmentStatus', 'PromoStatus')
     * @returns {Array<{value: string|number, label: string}>}
     */
    const getOptions = enumName => {
        return enums.value[enumName]?._options || []
    }

    /**
     * Mengambil objek metadata untuk nilai enum tertentu (label, color, dll).
     * @param {string} enumName
     * @param {string|number} value
     * @returns {{label?: string, color?: string}}
     */
    const getMeta = (enumName, value) => {
        return enums.value[enumName]?._meta?.[value] || {}
    }

    /**
     * Mengambil teks label dari suatu nilai enum.
     * @param {string} enumName
     * @param {string|number} value
     * @returns {string}
     */
    const getLabel = (enumName, value) => {
        return getMeta(enumName, value).label || String(value ?? '')
    }

    /**
     * Mengambil utility class badge/warna dari suatu nilai enum.
     * @param {string} enumName
     * @param {string|number} value
     * @returns {string}
     */
    const getColor = (enumName, value) => {
        return getMeta(enumName, value).color || ''
    }

    /**
     * Mengambil array opsi grup kategori dari suatu enum (jika didukung).
     * @param {string} enumName
     * @returns {Array<any>}
     */
    const getGrouped = enumName => {
        return enums.value[enumName]?._grouped || []
    }

    return {
        enums,
        getOptions,
        getGrouped,
        getMeta,
        getLabel,
        getColor,
    }
}
