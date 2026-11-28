<script setup lang="ts">
import PromotionsPanel from '@/components/storefront/checkout/PromotionsPanel.vue';
import Card from '@/components/ui/Card.vue';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
} from '@/types/storefront';

defineProps<{
    appliedPromotion: AppliedPromotionData | null;
    availablePromotions: AvailablePromotionData[];
    isApplying: boolean;
    error?: string | null;
    success?: string | null;
}>();

const emit = defineEmits<{
    (e: 'apply-code', code: string): void;
    (e: 'apply-offer', promoId: number): void;
    (e: 'remove'): void;
}>();
</script>

<template>
    <Card
        as="section"
        padding="lg"
        class="flex flex-col gap-4"
        aria-labelledby="cart-promotions-heading"
    >
        <h2 id="cart-promotions-heading" class="font-serif text-xl text-ink">
            Promotions
        </h2>
        <PromotionsPanel
            :applied-promotion="appliedPromotion"
            :available-promotions="availablePromotions"
            :is-applying="isApplying"
            :error="error"
            :success="success"
            @apply-code="emit('apply-code', $event)"
            @apply-offer="emit('apply-offer', $event)"
            @remove="emit('remove')"
        />
    </Card>
</template>
