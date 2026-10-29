import { usePage } from '@inertiajs/vue3'
import { useModalStore } from '@/store/notification'
import FeatureLockedModal from '@/Components/Modals/FeatureLockedModal.vue'
import { evaluateFeatureState } from '@/Composable/usePlanFeature'

const checkPermission = (permissions, required) => {
    if (permissions.includes('*')) {
        return true
    }
    return permissions.some(perm => {
        if (perm.includes('*')) {
            const regex = new RegExp('^' + perm.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$')
            return regex.test(required)
        }
        return perm === required
    })
}

const handleCan = (el, binding) => {
    const { props } = usePage()
    const permissions = props.auth?.permissions || []

    if (!binding.value || (Array.isArray(binding.value) && binding.value.length === 0)) {
        if (el._canHidden) {
            el.style.display = el._origDisplayCan || ''
            el.removeAttribute('aria-hidden')
            el._canHidden = false
        }
        return
    }

    let isPermitted = true
    if (typeof binding.value === 'string') {
        isPermitted = checkPermission(permissions, binding.value)
    } else if (Array.isArray(binding.value)) {
        if (binding.modifiers.all) {
            isPermitted = binding.value.every(v => checkPermission(permissions, v))
        } else {
            isPermitted = binding.value.some(v => checkPermission(permissions, v))
        }
    }

    if (!isPermitted) {
        if (!el._canHidden) {
            el._origDisplayCan = el.style.display
            el.style.display = 'none'
            el.setAttribute('aria-hidden', 'true')
            el._canHidden = true
        }
    } else if (el._canHidden) {
        el.style.display = el._origDisplayCan || ''
        el.removeAttribute('aria-hidden')
        el._canHidden = false
    }
}

const LOCK_CLASSES = ['cursor-not-allowed', 'select-none']

const openFeatureModal = featureName => {
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
}

const removeCaptureTrap = el => {
    if (el._featureTrap) {
        el.removeEventListener('click', el._featureTrap, true)
        delete el._featureTrap
    }
    if (el._featureKeydownTrap) {
        el.removeEventListener('keydown', el._featureKeydownTrap, true)
        delete el._featureKeydownTrap
    }
}

const renderLockOverlay = (el, featureName, isSubscribed) => {
    // 1. Intercept interactions at capture phase on the container
    if (!el._featureTrap || el._lockedFeature !== featureName) {
        removeCaptureTrap(el)

        const featureTrap = e => {
            e.preventDefault()
            e.stopPropagation()
            e.stopImmediatePropagation()
            openFeatureModal(featureName)
        }

        const featureKeydownTrap = e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault()
                e.stopPropagation()
                e.stopImmediatePropagation()
                openFeatureModal(featureName)
            }
        }

        el._featureTrap = featureTrap
        el._featureKeydownTrap = featureKeydownTrap
        el.addEventListener('click', featureTrap, true)
        el.addEventListener('keydown', featureKeydownTrap, true)
    }

    // 2. Ensure relative positioning
    if (
        !el.classList.contains('relative') &&
        !el.classList.contains('absolute') &&
        !el.classList.contains('fixed')
    ) {
        el.classList.add('relative')
        el._addedRelative = true
    }

    LOCK_CLASSES.forEach(cls => el.classList.add(cls))
    el.setAttribute('aria-disabled', 'true')

    // 3. Render visual overlay badge
    const labelText = isSubscribed ? 'Tingkatkan Paket' : 'Langganan'
    let overlay = el._featureOverlay || el.querySelector(':scope > .sollu-feature-lock-overlay')

    if (!overlay) {
        overlay = document.createElement('div')
        overlay.className =
            'sollu-feature-lock-overlay absolute inset-0 z-20 pointer-events-auto rounded-[inherit] bg-white/60 backdrop-blur-[0.5px] flex items-start justify-end p-3 cursor-pointer select-none'

        const button = document.createElement('button')
        button.type = 'button'
        button.className =
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-md bg-gradient-to-r from-main to-secondary hover:from-main-dark hover:to-secondary-dark text-white transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer whitespace-nowrap opacity-100 shrink-0'
        button.innerHTML = `
            <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 448 512" aria-hidden="true">
                <path d="M144 144v48h160v-48c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64v192c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/>
            </svg>
            <span class="sollu-lock-label">${labelText}</span>
        `

        const handleOverlayClick = e => {
            e.preventDefault()
            e.stopPropagation()
            e.stopImmediatePropagation()
            openFeatureModal(featureName)
        }

        button.addEventListener('click', handleOverlayClick)
        overlay.addEventListener('click', handleOverlayClick)

        overlay.appendChild(button)
        el.appendChild(overlay)
        el._featureOverlay = overlay
    } else {
        const labelEl = overlay.querySelector('.sollu-lock-label')
        if (labelEl && labelEl.textContent !== labelText) {
            labelEl.textContent = labelText
        }
    }

    el._lockedFeature = featureName
    el._isSubscribed = isSubscribed
}

const removeLockOverlay = el => {
    removeCaptureTrap(el)

    if (el._addedRelative) {
        el.classList.remove('relative')
        delete el._addedRelative
    }

    LOCK_CLASSES.forEach(cls => el.classList.remove(cls))
    el.removeAttribute('aria-disabled')

    if (el._featureOverlay) {
        el._featureOverlay.remove()
        delete el._featureOverlay
    }

    const orphanOverlay = el.querySelector(':scope > .sollu-feature-lock-overlay')
    if (orphanOverlay) {
        orphanOverlay.remove()
    }

    delete el._lockedFeature
    delete el._isSubscribed
}

const resolveTargetState = (auth, val, modifiers) => {
    if (!val || (Array.isArray(val) && val.length === 0)) {
        return 'active'
    }

    const activeFeatures = auth?.features || []

    // 1. Modifier .hide: strictly check if active. If not active, IMMEDIATELY return 'hidden' (no masking)
    if (modifiers.hide) {
        if (typeof val === 'string') {
            return activeFeatures.includes(val) ? 'active' : 'hidden'
        }
        if (Array.isArray(val)) {
            if (modifiers.all) {
                return val.every(f => activeFeatures.includes(f)) ? 'active' : 'hidden'
            }
            return val.some(f => activeFeatures.includes(f)) ? 'active' : 'hidden'
        }
        return 'hidden'
    }

    // 2. Modifier .mask: if not active, ALWAYS return 'masked' (never hidden, e.g. for Settings pages)
    if (modifiers.mask) {
        if (typeof val === 'string') {
            return activeFeatures.includes(val) ? 'active' : 'masked'
        }
        if (Array.isArray(val)) {
            if (modifiers.all) {
                return val.every(f => activeFeatures.includes(f)) ? 'active' : 'masked'
            }
            return val.some(f => activeFeatures.includes(f)) ? 'active' : 'masked'
        }
        return 'masked'
    }

    // 3. Standard 3-tier matrix evaluation
    if (typeof val === 'string') {
        return evaluateFeatureState(auth, val)
    }

    if (Array.isArray(val)) {
        if (modifiers.all) {
            const states = val.map(f => evaluateFeatureState(auth, f))
            if (states.every(s => s === 'active')) return 'active'
            if (states.some(s => s === 'hidden')) return 'hidden'
            return 'masked'
        }
        // Default OR logic
        const states = val.map(f => evaluateFeatureState(auth, f))
        if (states.some(s => s === 'active')) return 'active'
        if (states.every(s => s === 'hidden')) return 'hidden'
        return 'masked'
    }

    return 'active'
}

const handleFeature = (el, binding) => {
    const { props } = usePage()
    const subscription = props.auth?.subscription
    const isSubscribed = Boolean(subscription && subscription.status === 'active')

    if (!binding.value || (Array.isArray(binding.value) && binding.value.length === 0)) {
        removeLockOverlay(el)
        if (el._featureHidden) {
            el.style.display = el._origDisplayFeature || ''
            el.removeAttribute('aria-hidden')
            el._featureHidden = false
        }
        return
    }

    const state = resolveTargetState(props.auth, binding.value, binding.modifiers)

    if (state === 'hidden') {
        removeLockOverlay(el)
        if (!el._featureHidden) {
            el._origDisplayFeature = el.style.display
            el.style.display = 'none'
            el.setAttribute('aria-hidden', 'true')
            el._featureHidden = true
        }
    } else {
        if (el._featureHidden) {
            el.style.display = el._origDisplayFeature || ''
            el.removeAttribute('aria-hidden')
            el._featureHidden = false
        }

        if (state === 'masked') {
            const featureName = Array.isArray(binding.value) ? binding.value[0] : binding.value
            renderLockOverlay(el, featureName, isSubscribed)
        } else {
            removeLockOverlay(el)
        }
    }
}

const cleanupFeature = el => {
    removeLockOverlay(el)
    if (el._canHidden) {
        el.style.display = el._origDisplayCan || ''
        el.removeAttribute('aria-hidden')
        delete el._origDisplayCan
        delete el._canHidden
    }
    if (el._featureHidden) {
        el.style.display = el._origDisplayFeature || ''
        el.removeAttribute('aria-hidden')
        delete el._origDisplayFeature
        delete el._featureHidden
    }
}

export default {
    install(app) {
        Object.defineProperty(app.config.globalProperties, '$enums', {
            get() {
                return this.$page?.props?.enums || {}
            },
        })

        Object.defineProperty(app.config.globalProperties, '$can', {
            get() {
                const permissions = this.$page?.props?.auth?.permissions || []
                return (permission, requireAll = false) => {
                    if (!permission) return false
                    if (typeof permission === 'string') {
                        return checkPermission(permissions, permission)
                    }
                    if (Array.isArray(permission)) {
                        return requireAll
                            ? permission.every(p => checkPermission(permissions, p))
                            : permission.some(p => checkPermission(permissions, p))
                    }
                    return false
                }
            },
        })

        Object.defineProperty(app.config.globalProperties, '$feature', {
            get() {
                const auth = this.$page?.props?.auth || {}
                return featureName => evaluateFeatureState(auth, featureName)
            },
        })

        Object.defineProperty(app.config.globalProperties, '$hasFeature', {
            get() {
                const activeFeatures = this.$page?.props?.auth?.features || []
                return (featureName, requireAll = false) => {
                    if (!featureName) return true
                    if (typeof featureName === 'string') {
                        return activeFeatures.includes(featureName)
                    }
                    if (Array.isArray(featureName)) {
                        return requireAll
                            ? featureName.every(f => activeFeatures.includes(f))
                            : featureName.some(f => activeFeatures.includes(f))
                    }
                    return false
                }
            },
        })

        app.directive('can', {
            mounted: handleCan,
            updated: handleCan,
            unmounted: cleanupFeature,
        })

        app.directive('feature', {
            mounted: handleFeature,
            updated: handleFeature,
            unmounted: cleanupFeature,
        })
    },
}
