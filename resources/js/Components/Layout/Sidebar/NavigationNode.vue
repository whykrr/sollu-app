<template>
    <div v-if="shouldRender">
        <NavigationSection
            v-if="item.type === 'section'"
            :label="item.label"
            :separator="item.separator"
        />

        <NavigationItem
            v-else-if="item.type === 'item'"
            :to="item.url"
            :icon="item.icon"
            :label="item.label"
            :active="isActive(item)"
        />

        <NavigationDropdown
            v-else-if="item.type === 'dropdown'"
            to="#"
            :icon="item.icon"
            :label="item.label"
            :active="isActive(item)"
        >
            <Link
                v-for="(submenu, subIndex) in visibleItems"
                :key="submenu.url || submenu.label || subIndex"
                :href="submenu.url"
                class="nav-dropdown-item"
                :class="{ active: isActive(submenu) }"
            >
                {{ submenu.label }}
            </Link>
        </NavigationDropdown>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import NavigationSection from './NavigationSection.vue'
import NavigationItem from './NavigationItem.vue'
import NavigationDropdown from './NavigationDropdown.vue'

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    isActive: {
        type: Function,
        required: true,
    },
})

const page = usePage()

const checkPerm = (permissions, required) => {
    if (!required || (Array.isArray(required) && required.length === 0)) return true
    if (permissions.includes('*')) return true
    const checkSingle = req => {
        return permissions.some(perm => {
            if (perm.includes('*')) {
                const regex = new RegExp(
                    '^' + perm.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$'
                )
                return regex.test(req)
            }
            return perm === req
        })
    }
    if (typeof required === 'string') return checkSingle(required)
    if (Array.isArray(required)) return required.some(p => checkSingle(p))
    return true
}

const checkFeat = (features, required) => {
    if (!required) return true
    if (typeof required === 'string') return features.includes(required)
    if (Array.isArray(required)) {
        if (required.length === 0) return true
        return required.some(f => features.includes(f))
    }
    return true
}

const isVisible = targetItem => {
    const userPerms = page.props.auth?.permissions || []
    const userFeats = page.props.auth?.features || []

    if (!checkPerm(userPerms, targetItem.permissions)) return false
    if (!checkFeat(userFeats, targetItem.feature || targetItem.features)) return false

    return true
}

const visibleItems = computed(() => {
    if (props.item.type !== 'dropdown' || !Array.isArray(props.item.items)) {
        return []
    }
    return props.item.items.filter(child => isVisible(child))
})

const shouldRender = computed(() => {
    if (!isVisible(props.item)) return false
    if (props.item.type === 'dropdown') {
        return visibleItems.value.length > 0
    }
    return true
})
</script>
