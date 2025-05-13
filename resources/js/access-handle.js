import { usePage } from '@inertiajs/vue3';

export default {
    install(app) {
        app.directive('access', {
            mounted(el, binding) {
                const { props } = usePage();
                const role = props.auth.role;

                console.log(role);

                if (!binding.value.includes(role)) {
                    el.parentNode && el.parentNode.removeChild(el);
                }
            },
        });
    },
};
