import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const activeRequests = ref(0);
const isLoading = ref(false);

let isInitialized = false;

function initGlobalListeners() {
    if (isInitialized) return;
    isInitialized = true;

    router.on('start', () => {
        activeRequests.value++;
        isLoading.value = true;
    });

    router.on('finish', () => {
        activeRequests.value = Math.max(0, activeRequests.value - 1);
        if (activeRequests.value === 0) {
            isLoading.value = false;
        }
    });
}

export function usePageLoading() {
    initGlobalListeners();
    return {
        isLoading,
    };
}
