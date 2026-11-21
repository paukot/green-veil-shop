<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { QuantityInput } from '@/components/form';
import type { LocalCartItem } from '@/composables/useCart';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import {
    BookmarkIcon,
    CheckIcon,
    ImageIcon,
    Trash2Icon,
    TriangleAlertIcon,
} from '@lucide/vue';

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
    (e: 'save-for-later', productId: number): void;
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
                    <TriangleAlertIcon
                        class="h-4 w-4 text-amber-600"
                        aria-hidden="true"
                    />
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
                    <CheckIcon
                        class="h-4 w-4 text-emerald-600"
                        aria-hidden="true"
                    />
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
                            <ImageIcon
                                class="h-8 w-8"
                                :stroke-width="1"
                                aria-hidden="true"
                            />
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

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="emit('save-for-later', item.product_id)"
                            class="text-charcoal-500 flex min-h-[44px] items-center gap-1 px-2 text-xs transition-colors hover:text-sage-800"
                            title="Save for later"
                        >
                            <BookmarkIcon
                                class="h-4 w-4"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <span class="hidden sm:inline">Save</span>
                        </button>

                        <button
                            type="button"
                            @click="emit('remove-item', item.product_id)"
                            class="text-charcoal-400 flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 transition-colors hover:bg-rose-50 hover:text-rose-600"
                            aria-label="Remove item from bag"
                            title="Remove from bag"
                        >
                            <Trash2Icon
                                class="h-4.5 w-4.5"
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
