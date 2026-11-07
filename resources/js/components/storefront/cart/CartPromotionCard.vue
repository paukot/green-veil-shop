<script setup lang="ts">
import { ref } from 'vue';
import { formatCurrency } from '@/lib/utils';
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

const promoInput = ref('');

function handleSubmit() {
    const trimmed = promoInput.value.trim();
    if (!trimmed) return;
    emit('apply-code', trimmed);
    promoInput.value = '';
}
</script>

<template>
    <div
        class="space-y-4 rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-7"
    >
        <div class="flex items-center justify-between">
            <h3
                class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
            >
                Promotions & Codes
            </h3>
            <span
                v-if="appliedPromotion"
                class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 uppercase"
            >
                1 Active
            </span>
        </div>

        <!-- Active Promo Banner if Applied -->
        <div
            v-if="appliedPromotion"
            class="rounded-2xl border border-sage-200 bg-sage-50/80 p-3.5 text-xs"
        >
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sage-800 text-cream-50"
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
                        <div class="text-sage-950 font-semibold">
                            {{ appliedPromotion.name }}
                            <span
                                v-if="appliedPromotion.code"
                                class="ml-1 rounded bg-sage-200/60 px-1.5 py-0.5 font-mono text-[10px] text-sage-800 uppercase"
                            >
                                {{ appliedPromotion.code }}
                            </span>
                        </div>
                        <div class="text-[11px] text-sage-700">
                            {{ appliedPromotion.formatted_discount }}
                            discount active
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('remove')"
                    :disabled="isApplying"
                    class="rounded-md p-1 text-sage-500 transition-colors hover:bg-sage-200/60 hover:text-sage-900 disabled:opacity-50"
                    title="Remove promotion"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Promo Code Input Form -->
        <form @submit.prevent="handleSubmit" class="space-y-2">
            <div class="flex gap-2">
                <input
                    v-model="promoInput"
                    type="text"
                    placeholder="ENTER PROMO CODE"
                    class="placeholder:text-charcoal-400 flex-1 rounded-xl border border-cream-300 bg-cream-50/50 px-3.5 py-2 font-mono text-xs text-charcoal-900 uppercase focus:border-sage-500 focus:ring-1 focus:ring-sage-500 focus:outline-none"
                    :disabled="isApplying"
                />
                <button
                    type="submit"
                    :disabled="isApplying || !promoInput.trim()"
                    class="shrink-0 rounded-xl bg-sage-800 px-4 py-2 text-xs font-semibold text-cream-50 transition-colors hover:bg-sage-900 disabled:opacity-50"
                >
                    {{ isApplying ? '...' : 'Apply' }}
                </button>
            </div>
            <p v-if="error" class="text-[11px] font-medium text-red-600">
                {{ error }}
            </p>
            <p v-if="success" class="text-[11px] font-medium text-emerald-700">
                {{ success }}
            </p>
        </form>

        <!-- Available Botanical Offers -->
        <div
            v-if="availablePromotions.length > 0"
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
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="truncate text-xs font-medium text-charcoal-900"
                                >{{ offer.name }}</span
                            >
                            <span
                                v-if="offer.code"
                                class="rounded bg-cream-200/70 px-1.5 py-0.5 font-mono text-[10px] text-charcoal-700 uppercase"
                                >{{ offer.code }}</span
                            >
                        </div>
                        <div class="text-charcoal-500 text-[11px]">
                            <template
                                v-if="offer.discount_type === 'percentage'"
                            >
                                {{ offer.discount_value }}% off
                            </template>
                            <template v-else>
                                €{{ offer.discount_value }} off
                            </template>
                            <span v-if="offer.minimum_order_amount"
                                >• orders over €{{
                                    offer.minimum_order_amount
                                }}</span
                            >
                        </div>
                        <div
                            v-if="!offer.is_eligible && offer.amount_needed > 0"
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
                            :disabled="isApplying"
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
</template>
