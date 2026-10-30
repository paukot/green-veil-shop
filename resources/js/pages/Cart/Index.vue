<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CartPageProps, CartItemData } from '@/types/storefront';

const props = defineProps<CartPageProps>();

const updatingItemId = ref<number | string | null>(null);
const removingItemId = ref<number | string | null>(null);

function updateQuantity(item: CartItemData, newQuantity: number) {
    if (newQuantity < 1) {
        removeItem(item);
        return;
    }

    if (
        item.product.track_inventory &&
        newQuantity > item.product.stock_quantity
    ) {
        return;
    }

    updatingItemId.value = item.id;
    router.patch(
        `/cart/items/${item.id}`,
        { quantity: newQuantity },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                updatingItemId.value = null;
            },
        },
    );
}

function removeItem(item: CartItemData) {
    removingItemId.value = item.id;
    router.delete(`/cart/items/${item.id}`, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            removingItemId.value = null;
        },
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-IE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
}

const freeShippingProgress = computed(() => {
    if (props.summary.free_shipping_threshold <= 0) return 100;
    const percentage =
        (props.summary.subtotal / props.summary.free_shipping_threshold) * 100;
    return Math.min(100, Math.round(percentage));
});
</script>

<template>
    <Head title="Your Shopping Bag | Green Veil" />

    <StorefrontLayout>
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
            <h1
                class="mb-8 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl"
            >
                Your Shopping Bag
            </h1>

            <!-- If Cart has items -->
            <div
                v-if="items.length > 0"
                class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-12"
            >
                <!-- Cart Items List (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Free Shipping Progress Banner -->
                    <div
                        class="space-y-2.5 rounded-2xl border border-cream-200 bg-white p-4 shadow-xs sm:p-5"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span
                                v-if="summary.qualifies_for_free_shipping"
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
                                    formatCurrency(
                                        summary.amount_until_free_shipping,
                                    )
                                }}</span>
                                more for free standard delivery
                            </span>
                            <span
                                class="text-charcoal-400 font-mono text-[11px]"
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
                        class="divide-y divide-cream-100 overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
                    >
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex flex-col items-start justify-between gap-4 p-4 transition-colors hover:bg-cream-50/50 sm:flex-row sm:items-center sm:p-6"
                        >
                            <!-- Product Image & Meta -->
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <Link
                                    :href="`/products/${item.product.slug}`"
                                    class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-100 sm:h-24 sm:w-24"
                                >
                                    <img
                                        v-if="item.product.main_image"
                                        :src="
                                            item.product.main_image.startsWith(
                                                'http',
                                            ) ||
                                            item.product.main_image.startsWith(
                                                '/',
                                            )
                                                ? item.product.main_image
                                                : `/storage/${item.product.main_image}`
                                        "
                                        :alt="item.product.name"
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
                                        v-if="item.product.category"
                                        class="block text-[11px] font-medium tracking-wider text-sage-600 uppercase"
                                    >
                                        {{ item.product.category.name }}
                                    </span>
                                    <Link
                                        :href="`/products/${item.product.slug}`"
                                        class="block truncate font-serif text-sm text-charcoal-900 transition-colors hover:text-sage-700 sm:text-base"
                                    >
                                        {{ item.product.name }}
                                    </Link>
                                    <div class="text-charcoal-500 text-xs">
                                        {{ formatCurrency(item.product.price) }}
                                        each
                                    </div>
                                    <div
                                        v-if="
                                            item.product.track_inventory &&
                                            item.product.stock_quantity <= 5
                                        "
                                        class="text-[11px] text-amber-700"
                                    >
                                        Only
                                        {{ item.product.stock_quantity }} left
                                        in stock
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity Adjuster & Subtotal -->
                            <div
                                class="flex w-full items-center justify-between gap-6 border-t border-cream-100 pt-2 sm:w-auto sm:justify-end sm:border-t-0 sm:pt-0"
                            >
                                <!-- Quantity controls -->
                                <div
                                    class="flex items-center rounded-full border border-cream-300 bg-white px-2 py-0.5"
                                >
                                    <button
                                        type="button"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity - 1,
                                            )
                                        "
                                        :disabled="
                                            updatingItemId === item.id ||
                                            removingItemId === item.id
                                        "
                                        class="text-charcoal-600 flex h-7 w-7 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:opacity-40"
                                        aria-label="Decrease quantity"
                                    >
                                        &minus;
                                    </button>
                                    <span
                                        class="w-8 text-center text-xs font-semibold text-charcoal-900 select-none"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="
                                            updateQuantity(
                                                item,
                                                item.quantity + 1,
                                            )
                                        "
                                        :disabled="
                                            updatingItemId === item.id ||
                                            (item.product.track_inventory &&
                                                item.quantity >=
                                                    item.product.stock_quantity)
                                        "
                                        class="text-charcoal-600 flex h-7 w-7 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:opacity-40"
                                        aria-label="Increase quantity"
                                    >
                                        +
                                    </button>
                                </div>

                                <!-- Subtotal for item -->
                                <div
                                    class="w-20 text-right text-sm font-medium text-charcoal-900"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </div>

                                <!-- Remove button -->
                                <button
                                    type="button"
                                    @click="removeItem(item)"
                                    :disabled="removingItemId === item.id"
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
                <div class="space-y-6 lg:sticky lg:top-28 lg:col-span-4">
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
                            <span>Subtotal ({{ summary.count }} items)</span>
                            <span class="font-medium text-charcoal-900">{{
                                formatCurrency(summary.subtotal)
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
                                v-for="option in deliveryOptions"
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
                                    {{ formatCurrency(summary.subtotal) }}
                                </span>
                                <span
                                    class="text-charcoal-400 block text-[10px]"
                                    >+ Shipping calculated at checkout</span
                                >
                            </div>
                        </div>

                        <!-- Checkout CTA -->
                        <div class="pt-2">
                            <Link
                                href="/checkout"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-sage-800 px-6 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md active:scale-[0.99]"
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
                                        stroke-width="1.75"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                    />
                                </svg>
                                <span>Proceed to Checkout</span>
                            </Link>
                        </div>

                        <div class="text-center">
                            <Link
                                href="/products"
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
                    href="/products"
                    class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
                >
                    Explore Apothecary Collections
                </Link>
            </div>
        </section>
    </StorefrontLayout>
</template>
