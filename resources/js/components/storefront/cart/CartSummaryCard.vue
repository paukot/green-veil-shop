<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatCurrency } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type {
    AppliedPromotionData,
    DeliveryOptionItem,
} from '@/types/storefront';

defineProps<{
    itemCount: number;
    subtotal: number;
    discountAmount: number;
    totalAfterDiscount: number;
    appliedPromotion: AppliedPromotionData | null;
    qualifiesForFreeShipping: boolean;
    deliveryOptions: DeliveryOptionItem[];
    isSyncing: boolean;
    isNavigatingToCheckout: boolean;
    isEmpty: boolean;
}>();

const emit = defineEmits<{
    (e: 'checkout'): void;
}>();
</script>

<template>
    <div
        class="space-y-5 rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-7"
    >
        <div
            class="flex items-center justify-between border-b border-cream-100 pb-4"
        >
            <h2 class="font-serif text-lg font-medium text-charcoal-900">
                Order Summary
            </h2>
            <span
                class="rounded-full bg-cream-200/80 px-2.5 py-0.5 text-xs font-medium text-charcoal-700"
            >
                {{ itemCount }}
                {{ itemCount === 1 ? 'item' : 'items' }}
            </span>
        </div>

        <!-- Financial Breakdown Table -->
        <div class="space-y-3 text-xs sm:text-sm">
            <!-- Subtotal -->
            <div class="text-charcoal-600 flex items-center justify-between">
                <span>Subtotal</span>
                <span class="font-medium text-charcoal-900">
                    {{ formatCurrency(subtotal) }}
                </span>
            </div>

            <!-- Promotion Savings -->
            <div
                v-if="discountAmount > 0"
                class="flex items-center justify-between font-medium text-emerald-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Promotion Savings</span>
                    <span
                        v-if="appliedPromotion?.code"
                        class="rounded bg-emerald-100/70 px-1.5 py-0.5 font-mono text-[10px] tracking-wide text-emerald-900 uppercase"
                    >
                        {{ appliedPromotion.code }}
                    </span>
                </span>
                <span class="font-semibold text-emerald-800">
                    -{{ formatCurrency(discountAmount) }}
                </span>
            </div>

            <!-- Shipping Estimate -->
            <div class="text-charcoal-600 flex items-center justify-between">
                <span class="flex items-center gap-1">
                    <span>Estimated Shipping</span>
                </span>
                <span
                    v-if="qualifiesForFreeShipping"
                    class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800 uppercase"
                >
                    Free
                </span>
                <span
                    v-else-if="deliveryOptions.length > 0"
                    class="font-medium text-charcoal-900"
                >
                    {{ formatCurrency(deliveryOptions[0].price) }}
                </span>
                <span v-else class="text-charcoal-400"> At checkout </span>
            </div>

            <!-- Total Row -->
            <div class="border-t border-cream-200/90 pt-4">
                <div class="flex items-baseline justify-between">
                    <div>
                        <span
                            class="font-serif text-lg font-medium text-charcoal-900"
                            >Estimated Total</span
                        >
                        <span class="text-charcoal-400 block text-[11px]"
                            >Shipping finalized at checkout</span
                        >
                    </div>
                    <div class="text-right">
                        <span
                            v-if="discountAmount > 0"
                            class="text-charcoal-400 mr-2 text-xs line-through"
                        >
                            {{ formatCurrency(subtotal) }}
                        </span>
                        <span
                            class="font-sans text-2xl font-bold tracking-tight text-charcoal-950 sm:text-3xl"
                        >
                            {{ formatCurrency(totalAfterDiscount) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync status indicator -->
        <div
            v-if="isSyncing"
            class="text-charcoal-400 flex items-center justify-center gap-1.5 text-[11px]"
        >
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
        </div>

        <!-- Checkout CTA -->
        <div class="pt-2">
            <button
                type="button"
                @click="emit('checkout')"
                :disabled="isNavigatingToCheckout || isEmpty"
                class="flex w-full items-center justify-center gap-2 rounded-full bg-sage-800 px-6 py-4 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
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
</template>
