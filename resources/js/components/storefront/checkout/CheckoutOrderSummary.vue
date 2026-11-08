<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
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
    availablePromotions?: AvailablePromotionData[];
    selectedDelivery?: { name: string } | null;
    selectedPayment?: PaymentOptionItem | null;
    deliveryCost: number;
    discountAmount: number;
    calculatedTotal: number;
    processing: boolean;
    isApplyingPromo?: boolean;
    promoError?: string | null;
    promoSuccess?: string | null;
}>();

const emit = defineEmits<{
    (e: 'apply-promo', code: string): void;
    (e: 'apply-offer', promoId: number): void;
    (e: 'remove-promo'): void;
}>();

const promoCodeInput = ref('');

function applyCode() {
    const code = promoCodeInput.value.trim();
    if (!code) return;
    emit('apply-promo', code);
    promoCodeInput.value = '';
}
</script>

<template>
    <div class="space-y-6 lg:col-span-5">
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

            <!-- Active Promotion Badge or Promo Code Input -->
            <div class="border-t border-cream-100 pt-4">
                <div
                    v-if="appliedPromotion"
                    class="flex items-center justify-between rounded-2xl border border-sage-200 bg-sage-50/70 p-3 text-xs"
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
                        <div>
                            <span class="font-medium text-sage-900">
                                {{ appliedPromotion.name }}
                            </span>
                            <span
                                v-if="appliedPromotion.code"
                                class="ml-1.5 rounded bg-sage-200/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wider text-sage-800 uppercase"
                            >
                                {{ appliedPromotion.code }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-emerald-800">
                            {{ appliedPromotion.formatted_discount }}
                        </span>
                        <button
                            type="button"
                            @click="emit('remove-promo')"
                            class="text-[11px] font-medium text-rose-600 hover:text-rose-800 hover:underline"
                            title="Remove promo code"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Input for Promo Code if not yet applied -->
                <div v-else class="space-y-1.5">
                    <div class="flex gap-2">
                        <input
                            v-model="promoCodeInput"
                            type="text"
                            placeholder="Promo code (e.g. WELCOME10)"
                            class="placeholder-charcoal-400 w-full rounded-xl border border-cream-300 bg-cream-50/50 px-3 py-2 text-xs uppercase placeholder:normal-case focus:border-sage-500 focus:bg-white focus:ring-1 focus:ring-sage-500/20 focus:outline-none"
                            :disabled="isApplyingPromo"
                            @keydown.enter.prevent="applyCode"
                        />
                        <button
                            type="button"
                            @click="applyCode"
                            :disabled="
                                isApplyingPromo || !promoCodeInput.trim()
                            "
                            class="shrink-0 rounded-xl bg-sage-800 px-4 py-2 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 disabled:opacity-50"
                        >
                            {{ isApplyingPromo ? '...' : 'Apply' }}
                        </button>
                    </div>
                    <p v-if="promoError" class="text-xs text-rose-600">
                        {{ promoError }}
                    </p>
                    <p
                        v-if="promoSuccess"
                        class="text-xs font-medium text-emerald-700"
                    >
                        {{ promoSuccess }}
                    </p>
                </div>

                <!-- Available Store Offers / Applicable Promotions -->
                <div
                    v-if="availablePromotions && availablePromotions.length > 0"
                    class="space-y-2.5 border-t border-cream-100 pt-3"
                >
                    <div
                        class="text-[11px] font-semibold tracking-wider text-charcoal-700 uppercase"
                    >
                        Available Store Offers
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="offer in availablePromotions"
                            :key="offer.id"
                            class="flex items-center justify-between gap-2 rounded-2xl border p-3 transition-all"
                            :class="
                                offer.is_applied
                                    ? 'border-sage-300 bg-sage-50/70'
                                    : offer.is_eligible
                                      ? 'border-cream-200 bg-cream-50/40 hover:border-sage-200'
                                      : 'border-cream-100 bg-cream-50/20 opacity-75'
                            "
                        >
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <span
                                        class="truncate text-xs font-medium text-charcoal-900"
                                    >
                                        {{ offer.name }}
                                    </span>
                                    <span
                                        v-if="offer.code"
                                        class="rounded bg-cream-200/70 px-1.5 py-0.5 font-mono text-[10px] text-charcoal-700 uppercase"
                                    >
                                        {{ offer.code }}
                                    </span>
                                </div>
                                <div class="text-charcoal-500 text-[11px]">
                                    <template
                                        v-if="
                                            offer.discount_type === 'percentage'
                                        "
                                    >
                                        {{ offer.discount_value }}% off
                                    </template>
                                    <template v-else>
                                        €{{ offer.discount_value }} off
                                    </template>
                                    <span v-if="offer.minimum_order_amount">
                                        • orders over €{{
                                            offer.minimum_order_amount
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        !offer.is_eligible &&
                                        offer.amount_needed > 0
                                    "
                                    class="mt-0.5 text-[10px] font-medium text-amber-700"
                                >
                                    Add
                                    {{ formatCurrency(offer.amount_needed) }}
                                    more to unlock
                                </div>
                            </div>

                            <div class="shrink-0">
                                <span
                                    v-if="offer.is_applied"
                                    class="inline-flex items-center gap-1 rounded-full bg-sage-100 px-2.5 py-1 text-[10px] font-semibold text-sage-800"
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
                                    Applied
                                </span>
                                <button
                                    v-else-if="offer.is_eligible"
                                    type="button"
                                    @click="emit('apply-offer', offer.id)"
                                    :disabled="isApplyingPromo"
                                    class="rounded-full bg-sage-800 px-3 py-1 text-[11px] font-medium text-cream-50 transition-colors hover:bg-sage-900 disabled:opacity-50"
                                >
                                    {{ appliedPromotion ? 'Switch' : 'Apply' }}
                                </button>
                                <span
                                    v-else
                                    class="text-charcoal-500 rounded-full bg-cream-200/70 px-2.5 py-1 text-[10px] font-medium"
                                >
                                    Locked
                                </span>
                            </div>
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
        </div>
    </div>
</template>
