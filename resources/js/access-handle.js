import { usePage } from '@inertiajs/vue3';

export default {
    install(app) {
        app.directive('can', {
            mounted(el, binding) {
                const { props } = usePage();
                const permissions = props.auth.permissions;

                // console.log(permissions, binding)

                if (!binding.value || (Array.isArray(binding.value) && binding.value.length === 0)) {
                    return;
                }

                const hasPermission = (required) => {
                    return permissions.some(perm => {
                        if (perm.includes('*')) {
                            const regex = new RegExp('^' + perm.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$');
                            return regex.test(required);
                        }
                        return perm === required;
                    });
                };

                if (typeof binding.value === 'string') {
                    if (!hasPermission(binding.value)) {
                        el.parentNode && el.parentNode.removeChild(el);
                    }
                } else if (Array.isArray(binding.value)) {
                    if (!binding.value.some(v => hasPermission(v))) {
                        el.parentNode && el.parentNode.removeChild(el);
                    }
                }
            },
        });
    },
};
