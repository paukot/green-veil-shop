import { computed, ref } from 'vue';
import {
    applyPromotion as cartApplyPromotion,
    removePromotion as cartRemovePromotion,
    sync as cartSync,
} from '@/actions/App/Http/Controllers/CartController';
import { deleteJson, postJson } from '@/lib/api';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
    CartItemData,
} from '@/types/storefront';

export type LocalCartItem = {
    id: number | string;
    product_id: number;
    quantity: number;
    name: string;
    slug: string;
    sku?: string | null;
    price: number;
    compare_at_price?: number | null;
    discount_percentage?: number | null;
    main_image?: string | null;
    volume?: number | null;
    unit?: string | null;
    formatted_unit_price?: string | null;
    track_inventory: boolean;
    stock_quantity: number;
    is_in_stock: boolean;
    brand?: { id: number; name: string; slug: string } | null;
    category?: { id: number; name: string; slug: string } | null;
    added_at: number;
};

export type CartSyncResponse = {
    items: CartItemData[];
    summary: {
        count: number;
        subtotal: number;
        free_shipping_threshold: number;
        amount_until_free_shipping: number;
        qualifies_for_free_shipping: boolean;
        discount_amount?: number;
        total?: number;
    };
    applied_promotion?: AppliedPromotionData | null;
    available_promotions?: AvailablePromotionData[];
    delivery_options: Array<{
        id: number;
        code: string;
        name: string;
        price: number;
        free_threshold?: number | null;
        is_free: boolean;
        delivery_label?: string | null;
        description?: string | null;
    }>;
    warnings: string[];
};

const STORAGE_KEY = 'green_veil_cart_v1';
const SAVED_STORAGE_KEY = 'green_veil_saved_items_v1';
const DEBOUNCE_DELAY_MS = 600;

// Module-level reactive singleton
const items = ref<LocalCartItem[]>(loadFromStorage());
const savedItems = ref<LocalCartItem[]>(loadSavedFromStorage());
const appliedPromotion = ref<AppliedPromotionData | null>(null);
const availablePromotions = ref<AvailablePromotionData[]>([]);
const isSyncing = ref(false);
const syncError = ref<string | null>(null);
const syncWarnings = ref<string[]>([]);
const isInitialized = ref(false);
const isDrawerOpen = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let activeSyncPromise: Promise<CartSyncResponse | null> | null = null;

function loadFromStorage(): LocalCartItem[] {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [];
    }
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function saveToStorage(cartItems: LocalCartItem[]) {
    if (typeof window === 'undefined' || !window.localStorage) return;
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(cartItems));
    } catch {
        // Storage quota full or disabled
    }
}

function loadSavedFromStorage(): LocalCartItem[] {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [];
    }
    try {
        const raw = window.localStorage.getItem(SAVED_STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function saveSavedToStorage(savedList: LocalCartItem[]) {
    if (typeof window === 'undefined' || !window.localStorage) return;
    try {
        window.localStorage.setItem(
            SAVED_STORAGE_KEY,
            JSON.stringify(savedList),
        );
    } catch {
        // Storage quota full or disabled
    }
}

// Multi-tab synchronization
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY) {
            items.value = loadFromStorage();
        }
    });
}

/**
 * Execute network sync to backend via Wayfinder cartSync endpoint
 */
async function executeSync(): Promise<CartSyncResponse | null> {
    if (typeof window === 'undefined') return null;

    isSyncing.value = true;
    syncError.value = null;

    const payload = {
        items: items.value.map((i) => ({
            product_id: i.product_id,
            quantity: i.quantity,
        })),
    };

    try {
        const data = await postJson<CartSyncResponse>(cartSync.url(), payload);

        if (data.warnings && data.warnings.length > 0) {
            syncWarnings.value = data.warnings;
        }

        if (data.applied_promotion !== undefined) {
            appliedPromotion.value = data.applied_promotion;
        }
        if (data.available_promotions !== undefined) {
            availablePromotions.value = data.available_promotions;
        }

        reconcileWithServerItems(data.items);
        return data;
    } catch (err: unknown) {
        syncError.value =
            err instanceof Error ? err.message : 'Cart sync failed';
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

function reconcileWithServerItems(serverItems: CartItemData[]) {
    const serverMap = new Map(serverItems.map((si) => [si.product.id, si]));

    // Remove items that server says no longer exist
    const updated = items.value
        .filter((local) => serverMap.has(local.product_id))
        .map((local) => {
            const server = serverMap.get(local.product_id)!;
            return {
                ...local,
                id: server.id,
                quantity: server.quantity,
                price: Number(server.product.price),
                compare_at_price: server.product.compare_at_price
                    ? Number(server.product.compare_at_price)
                    : null,
                stock_quantity: server.product.stock_quantity,
                track_inventory: server.product.track_inventory,
                is_in_stock: server.product.is_in_stock,
            };
        });

    items.value = updated;
    saveToStorage(updated);
}

export function useCart() {
    const count = computed(() => {
        return items.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    const subtotal = computed(() => {
        const sum = items.value.reduce(
            (total, item) => total + item.price * item.quantity,
            0,
        );
        return Math.round(sum * 100) / 100;
    });

    const isEmpty = computed(() => items.value.length === 0);

    function qualifiesForFreeShipping(threshold: number): boolean {
        return subtotal.value >= threshold;
    }

    function amountUntilFreeShipping(threshold: number): number {
        return Math.max(
            0,
            Math.round((threshold - subtotal.value) * 100) / 100,
        );
    }

    function freeShippingProgress(threshold: number): number {
        if (threshold <= 0) return 100;
        return Math.min(100, Math.round((subtotal.value / threshold) * 100));
    }

    /**
     * Add product to cart optimistically and trigger debounced sync
     */
    function addItem(
        product: {
            id: number;
            name: string;
            slug: string;
            sku?: string | null;
            price: number;
            compare_at_price?: number | null;
            discount_percentage?: number | null;
            main_image?: string | null;
            volume?: number | null;
            unit?: string | null;
            formatted_unit_price?: string | null;
            track_inventory?: boolean;
            stock_quantity?: number;
            is_in_stock?: boolean;
            brand?: { id: number; name: string; slug: string } | null;
            category?: { id: number; name: string; slug: string } | null;
        },
        quantity = 1,
        openDrawer = false,
    ) {
        const existing = items.value.find((i) => i.product_id === product.id);
        const maxStock = product.track_inventory
            ? (product.stock_quantity ?? 99)
            : 99;

        if (existing) {
            const targetQty = existing.quantity + quantity;
            existing.quantity = Math.min(targetQty, maxStock);
        } else {
            const newItem: LocalCartItem = {
                id: product.id,
                product_id: product.id,
                quantity: Math.min(quantity, maxStock),
                name: product.name,
                slug: product.slug,
                sku: product.sku,
                price: Number(product.price),
                compare_at_price: product.compare_at_price
                    ? Number(product.compare_at_price)
                    : null,
                discount_percentage: product.discount_percentage,
                main_image: product.main_image,
                volume: product.volume,
                unit: product.unit,
                formatted_unit_price: product.formatted_unit_price,
                track_inventory: Boolean(product.track_inventory),
                stock_quantity: product.stock_quantity ?? 99,
                is_in_stock: product.is_in_stock ?? true,
                brand: product.brand,
                category: product.category,
                added_at: Date.now(),
            };
            items.value.push(newItem);
        }

        saveToStorage(items.value);
        scheduleSync();

        if (openDrawer) {
            isDrawerOpen.value = true;
        }
    }

    /**
     * Update quantity optimistically and trigger debounced sync
     */
    function updateQuantity(productId: number, newQuantity: number) {
        if (newQuantity <= 0) {
            removeItem(productId);
            return;
        }

        const item = items.value.find((i) => i.product_id === productId);
        if (!item) return;

        const maxStock = item.track_inventory ? item.stock_quantity : 99;
        item.quantity = Math.min(newQuantity, maxStock);

        saveToStorage(items.value);
        scheduleSync();
    }

    /**
     * Remove item optimistically and trigger debounced sync
     */
    function removeItem(productId: number) {
        const idx = items.value.findIndex((i) => i.product_id === productId);
        if (idx === -1) return;

        items.value.splice(idx, 1);
        saveToStorage(items.value);
        scheduleSync();
    }

    /**
     * Clear all items
     */
    function clearCart() {
        items.value = [];
        appliedPromotion.value = null;
        saveToStorage([]);
        scheduleSync();
    }

    /**
     * Immediately flush any pending debounced sync
     */
    async function flushSync(): Promise<CartSyncResponse | null> {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (activeSyncPromise) {
            return await activeSyncPromise;
        }
        return await executeSync();
    }

    const discountAmount = computed(() => {
        return appliedPromotion.value
            ? appliedPromotion.value.discount_amount
            : 0;
    });

    const totalAfterDiscount = computed(() => {
        return Math.max(
            0,
            Math.round((subtotal.value - discountAmount.value) * 100) / 100,
        );
    });

    /**
     * Apply a promotion by code or ID
     */
    async function applyPromotion(params: {
        code?: string;
        promotion_id?: number;
    }): Promise<{ success: boolean; message?: string; error?: string }> {
        isSyncing.value = true;
        syncError.value = null;
        try {
            const data = await postJson<{
                applied_promotion?: AppliedPromotionData | null;
                available_promotions?: AvailablePromotionData[];
                message?: string;
            }>(cartApplyPromotion.url(), params);

            appliedPromotion.value = data.applied_promotion ?? null;
            if (data.available_promotions) {
                availablePromotions.value = data.available_promotions;
            }
            return { success: true, message: data.message };
        } catch (err: unknown) {
            const message =
                err instanceof Error
                    ? err.message
                    : 'Network error while applying promotion';
            return { success: false, error: message };
        } finally {
            isSyncing.value = false;
        }
    }

    /**
     * Remove the active promotion
     */
    async function removePromotion(): Promise<{
        success: boolean;
        message?: string;
    }> {
        isSyncing.value = true;
        try {
            const data = await deleteJson<{
                available_promotions?: AvailablePromotionData[];
                message?: string;
            }>(cartRemovePromotion.url());

            appliedPromotion.value = null;
            if (data.available_promotions) {
                availablePromotions.value = data.available_promotions;
            }
            return { success: true, message: data.message };
        } catch {
            return { success: false };
        } finally {
            isSyncing.value = false;
        }
    }

    /**
     * Initialize/hydrate from server payload on page load or cart page visit
     */
    function initFromServer(
        serverItems: CartItemData[],
        serverAppliedPromotion?: AppliedPromotionData | null,
        serverAvailablePromotions?: AvailablePromotionData[],
    ) {
        if (serverAppliedPromotion !== undefined) {
            appliedPromotion.value = serverAppliedPromotion;
        }
        if (serverAvailablePromotions !== undefined) {
            availablePromotions.value = serverAvailablePromotions;
        }

        if (items.value.length === 0 && serverItems && serverItems.length > 0) {
            const mapped: LocalCartItem[] = serverItems.map((si) => ({
                id: si.id,
                product_id: si.product.id,
                quantity: si.quantity,
                name: si.product.name,
                slug: si.product.slug,
                sku: si.product.sku,
                price: Number(si.product.price),
                compare_at_price: si.product.compare_at_price
                    ? Number(si.product.compare_at_price)
                    : null,
                discount_percentage: si.product.discount_percentage,
                main_image: si.product.main_image,
                track_inventory: Boolean(si.product.track_inventory),
                stock_quantity: si.product.stock_quantity,
                is_in_stock: si.product.is_in_stock,
                category: si.product.category,
                added_at: Date.now(),
            }));

            items.value = mapped;
            saveToStorage(mapped);
        } else if (items.value.length > 0 && !isInitialized.value) {
            scheduleSync();
        }

        isInitialized.value = true;
    }

    function openDrawer() {
        isDrawerOpen.value = true;
    }

    function closeDrawer() {
        isDrawerOpen.value = false;
    }

    function saveForLater(productId: number | string) {
        const pId = Number(productId);
        const item = items.value.find((i) => i.product_id === pId);
        if (!item) return;

        removeItem(pId);

        const exists = savedItems.value.some((i) => i.product_id === pId);
        if (!exists) {
            savedItems.value = [item, ...savedItems.value];
            saveSavedToStorage(savedItems.value);
        }
    }

    function moveToCart(productId: number | string) {
        const pId = Number(productId);
        const item = savedItems.value.find((i) => i.product_id === pId);
        if (!item) return;

        savedItems.value = savedItems.value.filter((i) => i.product_id !== pId);
        saveSavedToStorage(savedItems.value);

        addItem(
            {
                ...item,
                id: item.product_id,
            },
            item.quantity,
            true,
        );
    }

    function removeSavedItem(productId: number | string) {
        const pId = Number(productId);
        savedItems.value = savedItems.value.filter((i) => i.product_id !== pId);
        saveSavedToStorage(savedItems.value);
    }

    return {
        items,
        count,
        subtotal,
        discountAmount,
        totalAfterDiscount,
        appliedPromotion,
        availablePromotions,
        isEmpty,
        isInitialized,
        isSyncing,
        syncError,
        syncWarnings,
        isDrawerOpen,
        openDrawer,
        closeDrawer,
        savedItems,
        saveForLater,
        moveToCart,
        removeSavedItem,
        addItem,
        updateQuantity,
        removeItem,
        clearCart,
        flushSync,
        initFromServer,
        applyPromotion,
        removePromotion,
        qualifiesForFreeShipping,
        amountUntilFreeShipping,
        freeShippingProgress,
    };
}
