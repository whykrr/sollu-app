import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useModalStore } from '@/store/notification'
import FeatureLockedModal from '@/Components/Modals/FeatureLockedModal.vue'

/**
 * Pure evaluation function of a feature's state based on the 3-tier matrix.
 *
 * @param {Object} auth - page.props.auth
 * @param {string} featureName - FeatureEnum value
 * @returns {'active' | 'masked' | 'hidden'}
 */
export function evaluateFeatureState(auth, featureName) {
    if (!featureName) return 'active'

    const activeFeatures = auth?.features || []
    const matrix = auth?.feature_matrix || {}
    const isPersonalized = Boolean(matrix.is_personalized)
    const typeFeatures = matrix.type_features

    // 1. Can access (Feature is in active pool: BusinessType ∩ Plan ∩ Personalization)
    if (activeFeatures.includes(featureName)) {
        return 'active'
    }

    // 2. Hide principle:
    // a. Outside BusinessType settings
    if (Array.isArray(typeFeatures) && !typeFeatures.includes(featureName)) {
        return 'hidden'
    }

    // b. Personalized by merchant: all inactive features are hidden from content pages
    if (isPersonalized) {
        return 'hidden'
    }

    // 3. Masking principle:
    // Unpersonalized, valid for BusinessType, but current plan does not include it
    return 'masked'
}

export function usePlanFeature() {
    const page = usePage()
    const features = computed(() => page.props.auth?.features || [])
    const featureMatrix = computed(() => page.props.auth?.feature_matrix || {})
    const subscription = computed(() => page.props.auth?.subscription || null)
    const business = computed(() => page.props.auth?.business || null)

    const isPersonalized = computed(() => Boolean(featureMatrix.value?.is_personalized))
    const typeFeatures = computed(() => featureMatrix.value?.type_features || null)
    const planFeatures = computed(() => featureMatrix.value?.plan_features || [])

    /**
     * Check if the current business has a specific feature active.
     * @param {string} featureName - The name of the feature to check.
     * @returns {boolean}
     */
    const hasFeature = featureName => {
        if (!featureName) return true
        return features.value.includes(featureName)
    }

    const canAccess = hasFeature

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
     * Check if a feature should be hidden according to the matrix rules.
     * @param {string|string[]} feature
     * @returns {boolean}
     */
    const shouldHide = feature => {
        if (!feature) return false

        if (Array.isArray(feature)) {
            if (feature.length === 0) return false
            // If all features in list should be hidden
            return feature.every(f => shouldHide(f))
        }

        const state = evaluateFeatureState(page.props.auth, feature)
        return state === 'hidden'
    }

    /**
     * Check if a feature should be masked with upgradeable lock according to the matrix rules.
     * @param {string|string[]} feature
     * @returns {boolean}
     */
    const shouldMask = feature => {
        if (!feature) return false

        if (Array.isArray(feature)) {
            if (feature.length === 0) return false
            return feature.some(f => shouldMask(f))
        }

        const state = evaluateFeatureState(page.props.auth, feature)
        return state === 'masked'
    }

    /**
     * Get the resolved state ('active' | 'masked' | 'hidden') for a feature.
     * @param {string} featureName
     * @returns {'active' | 'masked' | 'hidden'}
     */
    const getFeatureState = featureName => {
        return evaluateFeatureState(page.props.auth, featureName)
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
        featureMatrix,
        subscription,
        business,
        isPersonalized,
        typeFeatures,
        planFeatures,
        hasFeature,
        canAccess,
        hasAnyFeature,
        hasAllFeatures,
        shouldHide,
        shouldMask,
        getFeatureState,
        requireFeature,
    }
}
