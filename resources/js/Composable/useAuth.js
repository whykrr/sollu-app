import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAuth() {
    const page = usePage()
    const auth = computed(() => page.props.auth ?? {})
    const user = computed(() => auth.value)
    const business = computed(() => auth.value.business ?? null)
    const subscription = computed(() => auth.value.subscription ?? null)
    const outlets = computed(() => auth.value.outlets ?? [])
    const selectedOutlet = computed(() => auth.value.selected_outlet ?? null)
    const roles = computed(() => (auth.value.role ?? []).map((role) => role.name))
    const permissions = computed(() => auth.value.permissions ?? [])

    const hasRole = (role) => {
        return roles.value.includes(role)
    }

    const hasAnyRole = (roleList = []) => {
        return roleList.some((role) =>
            roles.value.includes(role),
        )
    }

    const can = (permission) => {
        const perms = permissions.value

        if (perms.includes(permission)) {
            return true
        }

        const segments = permission.split('.')

        if (segments.length > 1) {
            const wildcard = `${segments[0]}.*`

            if (perms.includes(wildcard)) {
                return true
            }
        }

        return false
    }

    const canAny = (permissionList = []) => {
        return permissionList.some((permission) =>
            can(permission),
        )
    }

    const canAll = (permissionList = []) => {
        return permissionList.every((permission) =>
            can(permission),
        )
    }

    const isOwner = computed(() =>
        hasRole('owner'),
    )

    return {
        auth,

        user,
        business,
        subscription,
        outlets,
        selectedOutlet,

        roles,
        permissions,

        isOwner,

        can,
        canAny,
        canAll,

        hasRole,
        hasAnyRole,
    }
}
