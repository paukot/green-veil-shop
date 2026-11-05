import { computed, ref } from 'vue';

export type FavouriteSyncResponse = {
    favourites: number[];
    count: number;
};

const STORAGE_KEY = 'green_veil_favourites_v1';
const DEBOUNCE_DELAY_MS = 500;

// Module-level reactive singleton
const productIds = ref<number[]>(loadFromStorage());
const isSyncing = ref(false);
const syncError = ref<string | null>(null);
const isInitialized = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let activeSyncPromise: Promise<number[] | null> | null = null;

function getXsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function loadFromStorage(): number[] {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [];
    }
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed)
            ? parsed.map((id) => Number(id)).filter((id) => id > 0)
            : [];
    } catch {
        return [];
    }
}

function saveToStorage(ids: number[]) {
    if (typeof window === 'undefined' || !window.localStorage) return;
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    } catch {
        // Storage full or unavailable
    }
}

// Multi-tab synchronization
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY) {
            productIds.value = loadFromStorage();
        }
    });
}

/**
 * Perform network sync to backend /favourites/sync
 */
async function executeSync(): Promise<number[] | null> {
    if (typeof window === 'undefined') return null;

    isSyncing.value = true;
    syncError.value = null;

    const payload = {
        product_ids: productIds.value,
    };

    try {
        const response = await fetch('/favourites/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error(
                `Favourites sync failed with status ${response.status}`,
            );
        }

        const data: FavouriteSyncResponse = await response.json();

        // Reconcile with verified active product IDs from backend
        productIds.value = data.favourites;
        saveToStorage(data.favourites);

        return data.favourites;
    } catch (err: unknown) {
        syncError.value =
            err instanceof Error ? err.message : 'Favourites sync failed';
        return null;
    } finally {
        isSyncing.value = false;
        activeSyncPromise = null;
    }
}

function scheduleSync() {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
        debounceTimer = null;
        activeSyncPromise = executeSync();
    }, DEBOUNCE_DELAY_MS);
}

export function useFavourites() {
    const count = computed(() => productIds.value.length);
    const isEmpty = computed(() => productIds.value.length === 0);

    function isFavourited(productId: number): boolean {
        return productIds.value.includes(productId);
    }

    /**
     * Toggles a favourite optimistically and schedules debounced server sync.
     * Returns true if newly added, false if removed.
     */
    function toggle(productId: number): boolean {
        const idx = productIds.value.indexOf(productId);
        let added = false;

        if (idx > -1) {
            productIds.value.splice(idx, 1);
            added = false;
        } else {
            productIds.value.push(productId);
            added = true;
        }

        saveToStorage(productIds.value);
        scheduleSync();
        return added;
    }

    function add(productId: number) {
        if (!productIds.value.includes(productId)) {
            productIds.value.push(productId);
            saveToStorage(productIds.value);
            scheduleSync();
        }
    }

    function remove(productId: number) {
        const idx = productIds.value.indexOf(productId);
        if (idx > -1) {
            productIds.value.splice(idx, 1);
            saveToStorage(productIds.value);
            scheduleSync();
        }
    }

    function clear() {
        productIds.value = [];
        saveToStorage([]);
        scheduleSync();
    }

    async function flushSync(): Promise<number[] | null> {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (activeSyncPromise) {
            return await activeSyncPromise;
        }
        return await executeSync();
    }

    /**
     * Hydrate from server props (e.g. on layout or page load)
     */
    function initFromServer(serverIds: number[]) {
        if (
            productIds.value.length === 0 &&
            serverIds &&
            serverIds.length > 0
        ) {
            const sanitized = serverIds
                .map((id) => Number(id))
                .filter((id) => id > 0);
            productIds.value = sanitized;
            saveToStorage(sanitized);
        } else if (productIds.value.length > 0 && !isInitialized.value) {
            scheduleSync();
        }

        isInitialized.value = true;
    }

    return {
        productIds,
        count,
        isEmpty,
        isSyncing,
        syncError,
        isFavourited,
        toggle,
        add,
        remove,
        clear,
        flushSync,
        initFromServer,
    };
}
