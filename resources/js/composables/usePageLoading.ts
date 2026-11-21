import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Visit } from '@inertiajs/core';

/** Visits currently shown as loading; start and finish receive the same visit object. */
const pendingVisits = new WeakSet<object>();
const activeCount = ref(0);
const isLoading = computed(() => activeCount.value > 0);

let isInitialized = false;

/**
 * Only a visit that reloads the current page's list (filtering, sorting, paging) should
 * swap the page for its skeleton. Hover prefetches, cached visits, polling, lazy partial
 * reloads of other props and navigations elsewhere must not: they would flash it while
 * the current content is still valid.
 */
function showsPageLoading(visit: Visit & { url: URL }): boolean {
    return (
        visit.method === 'get' &&
        !visit.prefetch &&
        !visit.cached &&
        !visit.async &&
        // A partial reload counts only when it replaces the list the skeleton stands in for.
        (visit.only.length === 0 ||
            visit.only.includes('products') ||
            visit.only.includes('orders')) &&
        visit.url.pathname === window.location.pathname
    );
}

function initGlobalListeners() {
    if (isInitialized || typeof window === 'undefined') return;
    isInitialized = true;

    router.on('start', (event) => {
        const visit = event.detail.visit;
        if (!showsPageLoading(visit)) return;

        pendingVisits.add(visit);
        activeCount.value++;
    });

    router.on('finish', (event) => {
        const visit = event.detail.visit;
        if (!pendingVisits.has(visit)) return;

        pendingVisits.delete(visit);
        activeCount.value = Math.max(0, activeCount.value - 1);
    });
}

export function usePageLoading() {
    initGlobalListeners();

    return {
        isLoading,
    };
}
