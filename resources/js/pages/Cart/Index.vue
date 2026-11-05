<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { QuantityInput } from '@/components/form';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type { CartPageProps } from '@/types/storefront';
import { useCart } from '@/composables/useCart';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { show as checkoutShow } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as productsIndex } from '@/routes/products';

defineOptions({ layout: StorefrontLayout });

const props = defineProps<CartPageProps>();

const cart = useCart();
const isNavigatingToCheckout = ref(false);

onMounted(() => {
    cart.initFromServer(props.items);
});

const freeShippingThreshold = computed(() => {
    return props.summary.free_shipping_threshold > 0
        ? props.summary.free_shipping_threshold
        : 50;
});

const freeShippingProgress = computed(() => {
    return cart.freeShippingProgress(freeShippingThreshold.value);
});

const qualifiesForFreeShipping = computed(() => {
    return cart.qualifiesForFreeShipping(freeShippingThreshold.value);
});

const amountUntilFreeShipping = computed(() => {
    return cart.amountUntilFreeShipping(freeShippingThreshold.value);
});

const computedDeliveryOptions = computed(() => {
    return props.deliveryOptions.map((option) => {
        const isFree = Boolean(
            option.free_threshold &&
            cart.subtotal.value >= option.free_threshold,
        );
        return {
            ...option,
            is_free: isFree,
        };
    });
});

function dismissWarnings() {
    cart.syncWarnings.value = [];
}

async function proceedToCheckout() {
    if (cart.isEmpty.value || isNavigatingToCheckout.value) return;
    isNavigatingToCheckout.value = true;
    try {
        await cart.flushSync();
        router.visit(checkoutShow.url());
    } catch {
        isNavigatingToCheckout.value = false;
    }
}
</script>

<template>
    <Head title="Your Shopping Bag | Green Veil" />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
        <!-- If Cart has items -->
        <div
            v-if="!cart.isEmpty.value"
            class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-12"
        >
            <!-- Cart Items List (8 cols) -->
            <div class="space-y-6 lg:col-span-8">
                <!-- Sync Warnings Alert -->
                <div
                    v-if="cart.syncWarnings.value.length > 0"
                    class="space-y-1.5 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs text-amber-900 shadow-xs"
                >
                    <div
                        class="flex items-center justify-between font-semibold text-amber-800"
                    >
                        <span class="flex items-center gap-1.5">
                            <svg
                                class="h-4 w-4 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            Inventory Adjustment Notice
                        </span>
                        <button
                            type="button"
                            @click="dismissWarnings"
                            class="text-base leading-none text-amber-600 hover:text-amber-800"
                            aria-label="Dismiss warnings"
                        >
                            &times;
                        </button>
                    </div>
                    <ul
                        class="list-inside list-disc space-y-0.5 text-amber-800"
                    >
                        <li
                            v-for="(warn, idx) in cart.syncWarnings.value"
                            :key="idx"
                        >
                            {{ warn }}
                        </li>
                    </ul>
                </div>

                <!-- Free Shipping Progress Banner -->
                <div
                    class="space-y-2.5 rounded-2xl border border-cream-200 bg-white p-4 shadow-xs sm:p-5"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span
                            v-if="qualifiesForFreeShipping"
                            class="flex items-center gap-1.5 font-medium text-emerald-800"
                        >
                            <svg
                                class="h-4 w-4 text-emerald-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span
                                >You have unlocked complimentary standard
                                delivery!</span
                            >
                        </span>
                        <span v-else class="text-charcoal-700">
                            Add
                            <span class="font-semibold text-sage-800">{{
                                formatCurrency(amountUntilFreeShipping)
                            }}</span>
                            more for free standard delivery
                        </span>
                        <span class="text-charcoal-400 font-mono text-[11px]"
                            >{{ freeShippingProgress }}%</span
                        >
                    </div>
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-cream-100"
                    >
                        <div
                            class="h-full rounded-full bg-sage-600 transition-all duration-500 ease-out"
                            :style="{ width: `${freeShippingProgress}%` }"
                        />
                    </div>
                </div>

                <!-- Items Card Table -->
                <div
                    class="divide-y overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
                >
                    <div
                        v-for="item in cart.items.value"
                        :key="item.product_id"
                        class="flex flex-col items-start justify-between gap-4 p-4 transition-colors hover:bg-cream-50/50 sm:flex-row sm:items-center sm:p-6"
                    >
                        <!-- Product Image & Meta -->
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <Link
                                :href="productShow.url(item.slug)"
                                prefetch
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-100 sm:h-24 sm:w-24"
                            >
                                <img
                                    v-if="item.main_image"
                                    :src="resolveStorageUrl(item.main_image)"
                                    :alt="item.name"
                                    class="h-full w-full object-cover object-center"
                                    loading="lazy"
                                />
                                <div
                                    v-else
                                    class="text-charcoal-300 flex h-full w-full items-center justify-center"
                                >
                                    <svg
                                        class="h-8 w-8"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </Link>

                            <div class="min-w-0 space-y-1">
                                <span
                                    v-if="item.category"
                                    class="block text-[11px] font-medium tracking-wider text-sage-600 uppercase"
                                >
                                    {{ item.category.name }}
                                </span>
                                <Link
                                    :href="productShow.url(item.slug)"
                                    prefetch
                                    class="block truncate font-serif text-sm text-charcoal-900 transition-colors hover:text-sage-700 sm:text-base"
                                >
                                    {{ item.name }}
                                </Link>
                                <div class="text-charcoal-500 text-xs">
                                    {{ formatCurrency(item.price) }}
                                    each
                                </div>
                                <div
                                    v-if="
                                        item.track_inventory &&
                                        item.stock_quantity <= 5
                                    "
                                    class="text-[11px] text-amber-700"
                                >
                                    Only
                                    {{ item.stock_quantity }} left in stock
                                </div>
                            </div>
                        </div>

                        <!-- Quantity Adjuster & Subtotal -->
                        <div
                            class="flex w-full items-center justify-between gap-6 border-t border-cream-100 pt-2 sm:w-auto sm:justify-end sm:border-t-0 sm:pt-0"
                        >
                            <!-- Quantity controls -->
                            <QuantityInput
                                :model-value="item.quantity"
                                :min="1"
                                :max="
                                    item.track_inventory
                                        ? item.stock_quantity
                                        : 99
                                "
                                @change="
                                    (qty) =>
                                        cart.updateQuantity(
                                            item.product_id,
                                            qty,
                                        )
                                "
                            />

                            <!-- Subtotal for item -->
                            <div
                                class="w-20 text-right text-sm font-medium text-charcoal-900"
                            >
                                {{ formatCurrency(item.price * item.quantity) }}
                            </div>

                            <!-- Remove button -->
                            <button
                                type="button"
                                @click="cart.removeItem(item.product_id)"
                                class="text-charcoal-400 rounded-full p-1.5 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                aria-label="Remove item from bag"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar (4 cols) -->
            <div class="space-y-6 lg:sticky lg:top-32 lg:col-span-4">
                <div
                    class="space-y-5 rounded-2xl border border-cream-200 bg-white p-6 shadow-xs"
                >
                    <h2
                        class="border-b border-cream-200 pb-3 font-serif text-base text-charcoal-900"
                    >
                        Order Summary
                    </h2>

                    <!-- Subtotal -->
                    <div
                        class="flex items-center justify-between text-xs text-charcoal-700 sm:text-sm"
                    >
                        <span>Subtotal ({{ cart.count.value }} items)</span>
                        <span class="font-medium text-charcoal-900">{{
                            formatCurrency(cart.subtotal.value)
                        }}</span>
                    </div>

                    <!-- Delivery Options Preview -->
                    <div
                        class="space-y-2 border-t border-cream-100 pt-3 text-xs"
                    >
                        <span
                            class="block text-[11px] font-semibold tracking-wider text-charcoal-800 uppercase"
                        >
                            Shipping Estimate
                        </span>
                        <div
                            v-for="option in computedDeliveryOptions"
                            :key="option.id"
                            class="text-charcoal-600 flex items-center justify-between py-1"
                        >
                            <span>{{ option.name }}</span>
                            <span
                                v-if="option.is_free"
                                class="text-[10px] font-semibold text-emerald-700 uppercase"
                            >
                                Free
                            </span>
                            <span v-else class="font-medium">
                                {{ formatCurrency(option.price) }}
                            </span>
                        </div>
                    </div>

                    <!-- Total estimate -->
                    <div
                        class="flex items-baseline justify-between border-t border-cream-200 pt-4"
                    >
                        <span class="font-serif text-base text-charcoal-900"
                            >Total</span
                        >
                        <div class="text-right">
                            <span
                                class="text-xl font-semibold text-charcoal-900"
                            >
                                {{ formatCurrency(cart.subtotal.value) }}
                            </span>
                            <span class="text-charcoal-400 block text-[10px]"
                                >+ Shipping calculated at checkout</span
                            >
                        </div>
                    </div>

                    <!-- Sync status indicator -->
                    <div
                        class="text-charcoal-400 flex items-center justify-center gap-1.5 text-[11px]"
                    >
                        <template v-if="cart.isSyncing.value">
                            <svg
                                class="h-3.5 w-3.5 animate-spin text-sage-600"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
                            </svg>
                            <span>Syncing bag with apothecary...</span>
                        </template>
                    </div>

                    <!-- Checkout CTA -->
                    <div class="pt-2">
                        <button
                            type="button"
                            @click="proceedToCheckout"
                            :disabled="
                                isNavigatingToCheckout || cart.isEmpty.value
                            "
                            class="flex w-full items-center justify-center gap-2 rounded-full bg-sage-800 px-6 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                v-if="isNavigatingToCheckout"
                                class="h-4 w-4 animate-spin text-cream-100"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.75"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                />
                            </svg>
                            <span>{{
                                isNavigatingToCheckout
                                    ? 'Securing Bag...'
                                    : 'Proceed to Checkout'
                            }}</span>
                        </button>
                    </div>

                    <div class="text-center">
                        <Link
                            :href="productsIndex.url()"
                            class="inline-block text-xs font-medium text-sage-700 underline decoration-sage-300 underline-offset-4 hover:text-sage-900 hover:decoration-sage-700"
                        >
                            &larr; Continue Exploring Botanicals
                        </Link>
                    </div>
                </div>

                <!-- Trust Signals -->
                <div
                    class="text-charcoal-600 space-y-2 rounded-xl border border-cream-200 bg-cream-100/60 p-4 text-xs"
                >
                    <div class="flex items-center gap-2">
                        <svg
                            class="h-4 w-4 shrink-0 text-sage-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                            />
                        </svg>
                        <span>Encrypted 256-bit secure checkout</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg
                            class="h-4 w-4 shrink-0 text-sage-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                            />
                        </svg>
                        <span>30-day conscious returns & exchanges</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Cart State -->
        <div
            v-else
            class="mx-auto max-w-md rounded-3xl border border-cream-200 bg-white p-8 py-20 text-center shadow-xs sm:p-12"
        >
            <div
                class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-sage-50 text-sage-700"
            >
                <svg
                    class="h-10 w-10"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.25"
                        d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"
                    />
                </svg>
            </div>
            <h2 class="mb-2 font-serif text-2xl text-charcoal-900">
                Your shopping bag is empty
            </h2>
            <p
                class="text-charcoal-600 mb-8 text-xs leading-relaxed font-light sm:text-sm"
            >
                Your bag is waiting for pure botanical cleansers, soothing
                mists, and restorative skincare rituals.
            </p>
            <Link
                :href="productsIndex.url()"
                class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
            >
                Explore Apothecary Collections
            </Link>
        </div>
    </section>
</template>
