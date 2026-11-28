<script setup lang="ts">
import { LockIcon } from '@lucide/vue';
import PriceBreakdown from '@/components/storefront/checkout/PriceBreakdown.vue';
import PromotionsPanel from '@/components/storefront/checkout/PromotionsPanel.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
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

const props = withDefaults(
    defineProps<{
        /** Unique per rendered copy (e.g. `mobile` / `desktop`) so field ids never collide. */
        idPrefix?: string;
        /** Appended to root/total/submit testids, e.g. `-mobile` or `-desktop`. */
        testIdSuffix?: string;
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
        /** The mobile summary sits above the form, so it has no submit button of its own. */
        showSubmit?: boolean;
        /** Render without the card chrome (used inside the mobile accordion). */
        bare?: boolean;
    }>(),
    {
        idPrefix: 'summary',
        testIdSuffix: '',
        appliedPromotion: null,
        availablePromotions: () => [],
        selectedDelivery: null,
        selectedPayment: null,
        isApplyingPromo: false,
        promoError: null,
        promoSuccess: null,
        showSubmit: true,
        bare: false,
    },
);

const emit = defineEmits<{
    (e: 'apply-promo', code: string): void;
    (e: 'apply-offer', promoId: number): void;
    (e: 'remove-promo'): void;
}>();
</script>

<template>
    <component
        :is="bare ? 'div' : Card"
        :padding="bare ? undefined : 'lg'"
        class="flex flex-col gap-6"
        role="region"
        aria-label="Order summary"
    >
        <div v-if="!bare" class="flex items-center justify-between gap-4">
            <h2 class="font-serif text-2xl text-ink">Order summary</h2>
            <Badge>
                {{ summary.count }} {{ summary.count === 1 ? 'item' : 'items' }}
            </Badge>
        </div>

        <ul
            class="-mt-2 flex max-h-80 flex-col gap-4 overflow-y-auto pt-2 pr-2"
            aria-label="Order items"
        >
            <li
                v-for="item in items"
                :key="item.product_id"
                class="flex items-center gap-4"
            >
                <div class="relative size-16 shrink-0">
                    <div
                        class="size-full overflow-hidden rounded-lg bg-surface-sunken"
                    >
                        <img
                            v-if="item.product.main_image"
                            :src="resolveStorageUrl(item.product.main_image)"
                            alt=""
                            class="size-full object-cover"
                            @error="showPlaceholderOnError"
                        />
                    </div>
                    <span
                        class="absolute -top-2 -right-2 flex h-6 min-w-6 items-center justify-center rounded-full bg-rose-900 px-1 text-xs font-semibold text-cream-50 ring-2 ring-surface-raised"
                    >
                        <span class="sr-only">Quantity </span>
                        {{ item.quantity }}
                    </span>
                </div>
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <p class="truncate text-sm font-medium text-ink">
                        {{ item.product.name }}
                    </p>
                    <p class="text-xs text-ink-subtle tabular-nums">
                        {{ formatCurrency(item.product.price) }} each
                    </p>
                </div>
                <p class="text-sm font-semibold text-ink tabular-nums">
                    {{ formatCurrency(item.subtotal) }}
                </p>
            </li>
        </ul>

        <div class="border-t border-border pt-6">
            <PriceBreakdown
                :test-id-prefix="`checkout${testIdSuffix.replace(/^-/, '-')}`"
                :subtotal="summary.subtotal"
                :discount-amount="discountAmount"
                :promo-code="appliedPromotion?.code"
                :shipping="deliveryCost"
                :total="calculatedTotal"
                :note="
                    selectedDelivery
                        ? `Delivery: ${selectedDelivery.name}`
                        : 'Taxes included'
                "
            />
        </div>

        <div class="border-t border-border pt-6">
            <PromotionsPanel
                :id-prefix="idPrefix"
                :applied-promotion="appliedPromotion"
                :available-promotions="availablePromotions"
                :is-applying="isApplyingPromo"
                :error="promoError"
                :success="promoSuccess"
                @apply-code="emit('apply-promo', $event)"
                @apply-offer="emit('apply-offer', $event)"
                @remove="emit('remove-promo')"
            />
        </div>

        <Button
            v-if="showSubmit"
            type="submit"
            size="lg"
            block
            :loading="processing"
        >
            <template #icon>
                <LockIcon class="size-4" aria-hidden="true" />
            </template>
            <span v-if="processing">Placing order</span>
            <span v-else-if="selectedPayment?.driver === 'stripe'">
                Continue to payment
            </span>
            <span v-else>Place order</span>
        </Button>
    </component>
</template>
