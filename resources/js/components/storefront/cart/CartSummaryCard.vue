<script setup lang="ts">
import { computed } from 'vue';
import { ArrowLeftIcon, LoaderCircleIcon, LockIcon } from '@lucide/vue';
import PriceBreakdown from '@/components/storefront/checkout/PriceBreakdown.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { index as productsIndex } from '@/routes/products';
import type {
    AppliedPromotionData,
    DeliveryOptionItem,
} from '@/types/storefront';

const props = defineProps<{
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

const estimatedShipping = computed(() => {
    if (props.qualifiesForFreeShipping) return 0;
    return props.deliveryOptions[0]?.price ?? null;
});
</script>

<template>
    <Card
        as="section"
        padding="lg"
        class="flex flex-col gap-6"
        aria-labelledby="cart-summary-heading"
    >
        <div class="flex items-center justify-between gap-4">
            <h2 id="cart-summary-heading" class="font-serif text-2xl text-ink">
                Order summary
            </h2>
            <Badge
                >{{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }}</Badge
            >
        </div>

        <PriceBreakdown
            test-id-prefix="cart"
            :subtotal="subtotal"
            :discount-amount="discountAmount"
            :promo-code="appliedPromotion?.code"
            :shipping="estimatedShipping"
            :total="totalAfterDiscount"
            total-label="Estimated total"
            note="Delivery is confirmed at checkout"
        />

        <div class="flex flex-col gap-3">
            <Button
                size="lg"
                block
                :loading="isNavigatingToCheckout"
                :disabled="isEmpty"
                @click="emit('checkout')"
            >
                <template #icon>
                    <LockIcon class="size-4" aria-hidden="true" />
                </template>
                {{ isNavigatingToCheckout ? 'Securing bag' : 'Checkout' }}
            </Button>
            <p
                v-if="isSyncing"
                class="flex items-center justify-center gap-2 text-xs text-ink-subtle"
                role="status"
            >
                <LoaderCircleIcon
                    class="size-3 animate-spin"
                    aria-hidden="true"
                />
                Saving your bag…
            </p>
            <Button
                :href="productsIndex.url()"
                variant="link"
                class="self-center"
            >
                <template #icon>
                    <ArrowLeftIcon class="size-4" aria-hidden="true" />
                </template>
                Continue shopping
            </Button>
        </div>
    </Card>
</template>
