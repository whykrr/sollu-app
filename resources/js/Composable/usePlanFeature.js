import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useModalStore } from '@/store/notification'
import FeatureLockedModal from '@/Components/Modals/FeatureLockedModal.vue'

export function usePlanFeature() {
    const page = usePage()
    const features = computed(() => page.props.auth?.features || [])
    const subscription = computed(() => page.props.auth?.subscription || null)
    const business = computed(() => page.props.auth?.business || null)

    /**
     * Check if the current business has a specific feature.
     * @param {string} featureName - The name of the feature to check.
     * @returns {boolean}
     */
    const hasFeature = featureName => {
        if (!featureName) return true
        return features.value.includes(featureName)
    }

    /**
     * Check if the current business has at least one of the specified features (OR condition).
     * @param {string[]} featureList - Array of feature names.
     * @returns {boolean}
     */
    const hasAnyFeature = (featureList = []) => {
        if (!featureList || featureList.length === 0) return true
        return featureList.some(feat => hasFeature(feat))
    }

    /**
     * Check if the current business has all of the specified features (AND condition).
     * @param {string[]} featureList - Array of feature names.
     * @returns {boolean}
     */
    const hasAllFeatures = (featureList = []) => {
        if (!featureList || featureList.length === 0) return true
        return featureList.every(feat => hasFeature(feat))
    }

    /**
     * Check if feature exists, if not, open the locked modal.
     * @param {string} featureName - The name of the feature to check.
     * @returns {boolean} - True if feature exists, False if locked (and opens modal).
     */
    const requireFeature = featureName => {
        if (hasFeature(featureName)) {
            return true
        }

        const modalStore = useModalStore()
        modalStore.open({
            component: FeatureLockedModal,
            props: {
                feature: featureName,
            },
            showFooter: false,
            title: 'Fitur Terkunci',
            size: 'max-w-md',
        })

        return false
    }

    return {
        features,
        subscription,
        business,
        hasFeature,
        hasAnyFeature,
        hasAllFeatures,
        requireFeature,
    }
}
