import { createI18n } from 'vue-i18n'
import id from '@/Lang/id'
import en from '@/Lang/en'

// Create an I18n instance
const i18n = createI18n({
    legacy: false,
    locale: 'en', // Default locale
    fallbackLocale: 'en', // Fallback locale
    messages: {
        en: en,
        id: id,
    }, // Translation messages
})

export default i18n
