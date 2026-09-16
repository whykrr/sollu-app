import { usePage } from '@inertiajs/vue3'
import { useModalStore } from '@/store/notification'
import FeatureLockedModal from '@/Components/Modals/FeatureLockedModal.vue'

const checkPermission = (permissions, required) => {
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
        el.parentNode && el.parentNode.removeChild(el)
    }
}

const LOCK_CLASSES = ['cursor-not-allowed', 'select-none']

import FeatureLock from '@/Components/UI/FeatureLock.vue'
import FeatureLockOverlay from '@/Components/UI/FeatureLockOverlay.vue'

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

const renderLockOverlay = (el, featureName, isSubscribed) => {
    // Check idempotency: avoid touching DOM on every re-render if already configured
    if (
        el._featureOverlay &&
        el._lockedFeature === featureName &&
        el._isSubscribed === isSubscribed
    ) {
        return
    }

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

const handleFeature = (el, binding) => {
    const { props } = usePage()
    const features = props.auth?.features || []
    const subscription = props.auth?.subscription
    const isSubscribed = Boolean(subscription && subscription.status === 'active')

    if (!binding.value || (Array.isArray(binding.value) && binding.value.length === 0)) {
        return
    }

    let isAllowed = true
    if (typeof binding.value === 'string') {
        isAllowed = features.includes(binding.value)
    } else if (Array.isArray(binding.value)) {
        if (binding.modifiers.all) {
            isAllowed = binding.value.every(v => features.includes(v))
        } else {
            isAllowed = binding.value.some(v => features.includes(v))
        }
    }

    const isLockMode = binding.modifiers.lock || binding.modifiers.modal

    if (!isAllowed) {
        if (isLockMode) {
            const featureName = Array.isArray(binding.value) ? binding.value[0] : binding.value
            renderLockOverlay(el, featureName, isSubscribed)
        } else {
            el.parentNode && el.parentNode.removeChild(el)
        }
    } else {
        if (isLockMode) {
            removeLockOverlay(el)
        }
    }
}

const cleanupFeature = el => {
    removeLockOverlay(el)
}

export { FeatureLock, FeatureLockOverlay }

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
                return permission => {
                    if (!permission) return false
                    if (typeof permission === 'string') {
                        return checkPermission(permissions, permission)
                    }
                    if (Array.isArray(permission)) {
                        return permission.some(p => checkPermission(permissions, p))
                    }
                    return false
                }
            },
        })

        app.component('FeatureLock', FeatureLock)
        app.component('FeatureLockOverlay', FeatureLockOverlay)

        app.directive('can', {
            mounted: handleCan,
            updated: handleCan,
        })

        app.directive('feature', {
            mounted: handleFeature,
            updated: handleFeature,
            unmounted: cleanupFeature,
        })
    },
}
