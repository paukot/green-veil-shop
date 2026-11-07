<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type {
    AppliedPromotionData,
    CartSummary,
    PaymentOptionItem,
} from '@/types/storefront';

export type CheckoutOrderItem = {
    id: number | string;
    product_id: number;
    quantity: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
        slug: string;
        sku?: string | null;
        price: number;
        main_image?: string | null;
    };
};

defineProps<{
    items: CheckoutOrderItem[];
    summary: CartSummary;
    appliedPromotion?: AppliedPromotionData | null;
    selectedDelivery?: { name: string } | null;
    selectedPayment?: PaymentOptionItem | null;
    deliveryCost: number;
    discountAmount: number;
    calculatedTotal: number;
    processing: boolean;
}>();
</script>

<template>
    <div class="space-y-6 lg:sticky lg:top-32 lg:col-span-5">
        <div
            class="space-y-6 rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-7"
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
                    {{ summary.count }}
                    {{ summary.count === 1 ? 'item' : 'items' }}
                </span>
            </div>

            <!-- Items mini list -->
            <div
                class="max-h-80 divide-y divide-cream-100 overflow-y-auto pr-1"
            >
                <div
                    v-for="item in items"
                    :key="item.product_id"
                    class="flex items-center gap-3.5 py-3 first:pt-0 last:pb-0"
                >
                    <div
                        class="relative h-14 w-14 shrink-0 rounded-xl border border-cream-200 bg-cream-100"
                    >
                        <img
                            v-if="item.product.main_image"
                            :src="resolveStorageUrl(item.product.main_image)"
                            :alt="item.product.name"
                            class="h-full w-full rounded-[10px] object-cover"
                        />
                        <div
                            v-else
                            class="text-charcoal-300 flex h-full w-full items-center justify-center rounded-[10px]"
                        >
                            🍃
                        </div>
                        <span
                            class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-charcoal-800 text-[10px] font-bold text-cream-50 shadow-xs ring-2 ring-white"
                        >
                            {{ item.quantity }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-medium text-charcoal-900 sm:text-sm"
                        >
                            {{ item.product.name }}
                        </p>
                        <p class="text-charcoal-400 text-[11px]">
                            {{ formatCurrency(item.product.price) }} each
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-xs font-semibold text-charcoal-900 sm:text-sm"
                    >
                        {{ formatCurrency(item.subtotal) }}
                    </span>
                </div>
            </div>

            <!-- Applied Promotion Tag -->
            <div
                v-if="appliedPromotion"
                class="flex items-center justify-between rounded-2xl border border-sage-200 bg-sage-50/70 px-3.5 py-2.5 text-xs"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sage-800 text-cream-50"
                    >
                        <svg
                            class="h-3 w-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </span>
                    <span class="font-medium text-sage-900">
                        {{ appliedPromotion.name }}
                    </span>
                    <span
                        v-if="appliedPromotion.code"
                        class="rounded bg-sage-200/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wider text-sage-800 uppercase"
                    >
                        {{ appliedPromotion.code }}
                    </span>
                </div>
                <span class="font-semibold text-emerald-800">
                    {{ appliedPromotion.formatted_discount }}
                </span>
            </div>

            <!-- Calculations Table -->
            <div
                class="space-y-3 border-t border-cream-100 pt-4 text-xs sm:text-sm"
            >
                <div
                    class="text-charcoal-600 flex items-center justify-between"
                >
                    <span>Subtotal</span>
                    <span class="font-medium text-charcoal-900">{{
                        formatCurrency(summary.subtotal)
                    }}</span>
                </div>

                <div
                    v-if="discountAmount > 0"
                    class="flex items-center justify-between font-medium text-emerald-800"
                >
                    <span class="flex items-center gap-1.5">
                        <span>Promotion Savings</span>
                    </span>
                    <span class="font-semibold"
                        >-{{ formatCurrency(discountAmount) }}</span
                    >
                </div>

                <div
                    class="text-charcoal-600 flex items-center justify-between"
                >
                    <span class="flex items-center gap-1.5">
                        <span>Shipping</span>
                        <span
                            v-if="selectedDelivery"
                            class="text-charcoal-400 text-[11px]"
                            >({{ selectedDelivery.name }})</span
                        >
                    </span>
                    <span
                        v-if="deliveryCost === 0"
                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 uppercase"
                    >
                        Free
                    </span>
                    <span v-else class="font-medium text-charcoal-900">
                        {{ formatCurrency(deliveryCost) }}
                    </span>
                </div>

                <!-- Total -->
                <div class="border-t border-cream-200 pt-4">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <span
                                class="font-serif text-lg font-medium text-charcoal-900"
                                >Grand Total</span
                            >
                            <span class="text-charcoal-400 block text-[11px]"
                                >Taxes & duties included</span
                            >
                        </div>
                        <div class="text-right">
                            <span
                                v-if="discountAmount > 0"
                                class="text-charcoal-400 mr-2 text-xs line-through"
                            >
                                {{
                                    formatCurrency(
                                        summary.subtotal + deliveryCost,
                                    )
                                }}
                            </span>
                            <span
                                class="font-sans text-2xl font-bold tracking-tight text-charcoal-950 sm:text-3xl"
                            >
                                {{ formatCurrency(calculatedTotal) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="pt-1">
                <Button
                    type="submit"
                    size="lg"
                    class="w-full rounded-full py-4 text-xs font-semibold tracking-wider uppercase shadow-sm transition-all hover:shadow-md active:scale-[0.99]"
                    :loading="processing"
                >
                    <span v-if="processing">Securing Your Order...</span>
                    <span v-else-if="selectedPayment?.driver === 'stripe'"
                        >Proceed to Stripe Payment</span
                    >
                    <span v-else>Confirm & Place Order</span>
                </Button>
            </div>

            <!-- Trust signals -->
            <div
                class="text-charcoal-500 space-y-2 rounded-2xl bg-cream-50/70 p-3.5 text-[11px]"
            >
                <div class="flex items-center gap-2.5">
                    <svg
                        class="h-4 w-4 shrink-0 text-sage-700"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                        />
                    </svg>
                    <span>256-Bit SSL Encrypted & Secure</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg
                        class="h-4 w-4 shrink-0 text-sage-700"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V3.75"
                        />
                    </svg>
                    <span>Pure botanical artisanal formulations</span>
                </div>
            </div>
        </div>
    </div>
</template>
