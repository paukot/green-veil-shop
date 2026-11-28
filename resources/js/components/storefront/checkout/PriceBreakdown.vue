<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';
import { formatCurrency } from '@/lib/utils';

/**
 * The subtotal → savings → delivery → total table shared by the bag, mini bag and checkout.
 */
const props = withDefaults(
    defineProps<{
        subtotal: number;
        discountAmount?: number;
        promoCode?: string | null;
        /** Delivery cost; 0 shows "Free", null shows "At checkout". */
        shipping?: number | null;
        total: number;
        totalLabel?: string;
        note?: string;
        /** Prefix for ``s (`<prefix>-subtotal`, `<prefix>-total` …). */
        testIdPrefix?: string;
    }>(),
    {
        discountAmount: 0,
        promoCode: null,
        shipping: null,
        totalLabel: 'Total',
        note: undefined,
        testIdPrefix: 'price',
    },
);
</script>

<template>
    <dl class="flex flex-col gap-3 text-sm" aria-label="Price breakdown">
        <div class="flex items-center justify-between gap-4">
            <dt class="text-ink-muted">Subtotal</dt>
            <dd class="font-medium text-ink tabular-nums">
                {{ formatCurrency(subtotal) }}
            </dd>
        </div>

        <div
            v-if="discountAmount > 0"
            class="flex items-center justify-between gap-4"
        >
            <dt class="flex items-center gap-2 text-success-700">
                Savings
                <Badge v-if="promoCode" tone="success" class="font-mono">
                    {{ promoCode }}
                </Badge>
            </dt>
            <dd class="font-medium text-success-700 tabular-nums">
                −{{ formatCurrency(discountAmount) }}
            </dd>
        </div>

        <div class="flex items-center justify-between gap-4">
            <dt class="text-ink-muted">Delivery</dt>
            <dd v-if="shipping === 0" class="font-medium text-success-700">
                Free
            </dd>
            <dd
                v-else-if="shipping !== null"
                class="font-medium text-ink tabular-nums"
            >
                {{ formatCurrency(shipping) }}
            </dd>
            <dd v-else class="text-ink-subtle">At checkout</dd>
        </div>

        <div
            class="mt-1 flex items-baseline justify-between gap-4 border-t border-border pt-4"
        >
            <dt class="flex flex-col gap-1">
                <span class="font-serif text-xl text-ink">{{
                    totalLabel
                }}</span>
                <span v-if="note" class="text-xs text-ink-subtle">{{
                    note
                }}</span>
            </dt>
            <dd
                class="text-2xl font-semibold text-ink tabular-nums"
                :data-testid="`${props.testIdPrefix}-total`"
            >
                {{ formatCurrency(total) }}
            </dd>
        </div>
    </dl>
</template>
