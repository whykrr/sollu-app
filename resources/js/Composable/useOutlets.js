import { ref } from 'vue';
import axios from 'axios';

// Global state outside the composable to ensure singleton behavior
const sharedOutlets = ref([]);
const isSharedLoading = ref(false);
const hasLoaded = ref(false);
let fetchPromise = null;

export function useOutlets() {
    /**
     * Fetch outlets from internal API. 
     * Uses a singleton promise to prevent redundant parallel requests.
     * 
     * @param {boolean} force - Set to true to force refresh data
     */
    const fetchOutlets = async (force = false) => {
        // Return existing data if already loaded and not forcing a refresh
        if (!force && hasLoaded.value) {
            return sharedOutlets.value;
        }

        // Return ongoing promise if already fetching
        if (!force && fetchPromise) {
            return fetchPromise;
        }

        isSharedLoading.value = true;
        fetchPromise = axios.get(route('api.internal.outlets.index'))
            .then(response => {
                let data = [];
                if (Array.isArray(response.data)) {
                    data = response.data;
                } else if (response.data.data && Array.isArray(response.data.data)) {
                    data = response.data.data;
                }
                
                sharedOutlets.value = data;
                hasLoaded.value = true;
                isSharedLoading.value = false;
                fetchPromise = null;
                
                return data;
            })
            .catch(error => {
                isSharedLoading.value = false;
                fetchPromise = null;
                throw error;
            });

        return fetchPromise;
    };

    return {
        outlets: sharedOutlets,
        isLoading: isSharedLoading,
        hasLoaded,
        fetchOutlets,
    };
}
