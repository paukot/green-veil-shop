<script setup lang="ts">
import { ref, useId } from 'vue';
import { CheckIcon, XIcon } from '@lucide/vue';
import { TextInput } from '@/components/form';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import IconButton from '@/components/ui/IconButton.vue';
import { formatDiscount } from '@/lib/formatters';
import { cn, formatCurrency } from '@/lib/utils';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
} from '@/types/storefront';

/**
 * Applied promotion, promo-code form and the list of store offers. One component for the
 * bag, the desktop checkout summary and the mobile checkout summary.
 */
withDefaults(
    defineProps<{
        appliedPromotion?: AppliedPromotionData | null;
        availablePromotions?: AvailablePromotionData[];
        isApplying?: boolean;
        error?: string | null;
        success?: string | null;
    }>(),
    {
        appliedPromotion: null,
        availablePromotions: () => [],
        isApplying: false,
        error: null,
        success: null,
    },
);

const emit = defineEmits<{
    (e: 'apply-code', code: string): void;
    (e: 'apply-offer', promoId: number): void;
    (e: 'remove'): void;
}>();

const code = ref('');
const inputId = useId();

function submit() {
    const trimmed = code.value.trim();
    if (!trimmed) return;
    emit('apply-code', trimmed);
    code.value = '';
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-if="appliedPromotion"
            class="flex items-start gap-3 rounded-lg bg-success-50 p-4"
        >
            <span
                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sage-900 text-cream-50"
            >
                <CheckIcon class="size-4" aria-hidden="true" />
            </span>
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <p
                    class="flex flex-wrap items-center gap-2 text-sm font-medium text-ink"
                >
                    {{ appliedPromotion.name }}
                    <Badge
                        v-if="appliedPromotion.code"
                        tone="success"
                        class="font-mono"
                    >
                        {{ appliedPromotion.code }}
                    </Badge>
                </p>
                <p class="text-xs text-success-700">
                    {{ appliedPromotion.formatted_discount }} applied
                </p>
            </div>
            <IconButton
                label="Remove promotion"
                size="sm"
                :disabled="isApplying"
                class="-mt-1 -mr-1"
                @click="emit('remove')"
            >
                <XIcon class="size-4" aria-hidden="true" />
            </IconButton>
        </div>

        <form class="flex flex-col gap-2" @submit.prevent="submit">
            <label :for="inputId" class="text-sm font-medium text-ink">
                Promo code
            </label>
            <div class="flex gap-2">
                <TextInput
                    :id="inputId"
                    v-model="code"
                    placeholder="Enter code"
                    class="font-mono uppercase"
                    :disabled="isApplying"
                    autocomplete="off"
                />
                <Button
                    type="submit"
                    variant="outline"
                    :loading="isApplying"
                    :disabled="!code.trim()"
                >
                    Apply
                </Button>
            </div>
            <p v-if="error" class="text-xs text-danger-600" role="alert">
                {{ error }}
            </p>
            <p v-if="success" class="text-xs text-success-700" role="status">
                {{ success }}
            </p>
        </form>

        <div
            v-if="availablePromotions.length > 0"
            class="flex flex-col gap-3 border-t border-border pt-4"
        >
            <p
                class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
            >
                Store offers
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="offer in availablePromotions"
                    :key="offer.id"
                    :class="
                        cn(
                            'flex items-center justify-between gap-4 rounded-lg border p-4',
                            offer.is_applied
                                ? 'border-sage-300 bg-success-50'
                                : 'border-border',
                            !offer.is_eligible &&
                                !offer.is_applied &&
                                'opacity-70',
                        )
                    "
                >
                    <div class="flex min-w-0 flex-col gap-1">
                        <p
                            class="flex flex-wrap items-center gap-2 text-sm font-medium text-ink"
                        >
                            {{ offer.name }}
                            <Badge v-if="offer.code" class="font-mono">
                                {{ offer.code }}
                            </Badge>
                        </p>
                        <p class="text-xs text-ink-muted">
                            {{
                                formatDiscount(
                                    offer.discount_type,
                                    offer.discount_value,
                                )
                            }}
                            off
                            <template v-if="offer.minimum_order_amount">
                                · orders over
                                {{ formatCurrency(offer.minimum_order_amount) }}
                            </template>
                        </p>
                        <p
                            v-if="!offer.is_eligible && offer.amount_needed > 0"
                            class="text-xs font-medium text-warning-700"
                        >
                            Add {{ formatCurrency(offer.amount_needed) }} more
                            to unlock
                        </p>
                    </div>
                    <Badge v-if="offer.is_applied" tone="success">
                        <CheckIcon class="size-3" aria-hidden="true" />
                        Applied
                    </Badge>
                    <Button
                        v-else-if="offer.is_eligible"
                        size="sm"
                        variant="outline"
                        :disabled="isApplying"
                        @click="emit('apply-offer', offer.id)"
                    >
                        {{ appliedPromotion ? 'Switch' : 'Apply' }}
                    </Button>
                    <Badge v-else>Locked</Badge>
                </li>
            </ul>
        </div>
    </div>
</template>
