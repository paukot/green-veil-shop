<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { QuantityInput } from '@/components/form';
import type { LocalCartItem } from '@/composables/useCart';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';

defineProps<{
    items: LocalCartItem[];
    warnings?: string[];
    freeShippingProgress: number;
    qualifiesForFreeShipping: boolean;
    amountUntilFreeShipping: number;
}>();

const emit = defineEmits<{
    (e: 'dismiss-warnings'): void;
    (e: 'update-quantity', productId: number, quantity: number): void;
    (e: 'remove-item', productId: number): void;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Sync Warnings Alert -->
        <div
            v-if="warnings && warnings.length > 0"
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
                    @click="emit('dismiss-warnings')"
                    class="text-base leading-none text-amber-600 hover:text-amber-800"
                    aria-label="Dismiss warnings"
                >
                    &times;
                </button>
            </div>
            <ul class="list-inside list-disc space-y-0.5 text-amber-800">
                <li v-for="(warn, idx) in warnings" :key="idx">
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
            <div class="h-2 w-full overflow-hidden rounded-full bg-cream-100">
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
                v-for="item in items"
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
                                item.track_inventory && item.stock_quantity <= 5
                            "
                            class="text-[11px] text-amber-700"
                        >
                            Only {{ item.stock_quantity }} left in stock
                        </div>
                    </div>
                </div>

                <!-- Quantity Adjuster & Subtotal -->
                <div
                    class="flex w-full items-center justify-between gap-3 border-t border-cream-100 pt-3 sm:w-auto sm:justify-end sm:gap-6 sm:border-t-0 sm:pt-0"
                >
                    <QuantityInput
                        :model-value="item.quantity"
                        :min="1"
                        :max="item.track_inventory ? item.stock_quantity : 99"
                        @change="
                            (qty) =>
                                emit('update-quantity', item.product_id, qty)
                        "
                    />

                    <div
                        class="w-20 text-right text-sm font-semibold text-charcoal-900"
                    >
                        {{ formatCurrency(item.price * item.quantity) }}
                    </div>

                    <button
                        type="button"
                        @click="emit('remove-item', item.product_id)"
                        class="text-charcoal-400 flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 transition-colors hover:bg-rose-50 hover:text-rose-600"
                        aria-label="Remove item from bag"
                    >
                        <svg
                            class="h-4.5 w-4.5"
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
</template>
