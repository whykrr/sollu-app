import { ref, onMounted, onBeforeUnmount } from 'vue';

export function useDropdown() {
    const isOpen = ref(false);
    const dropdownRef = ref(null);

    const toggle = () => {
        isOpen.value = !isOpen.value;
    };

    const close = () => {
        isOpen.value = false;
    };

    const handleClickOutside = (event) => {
        if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
            isOpen.value = false;
        }
    };

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    return {
        isOpen,
        dropdownRef,
        toggle,
        close,
    };
}
