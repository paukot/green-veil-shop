<script setup lang="ts">
import { ref } from 'vue';
import type { PromotionBanner } from '@/types/storefront';

const props = defineProps<{
    promotions: PromotionBanner[];
}>();

const copiedCode = ref<string | null>(null);

function copyCode(code: string) {
    navigator.clipboard?.writeText(code);
    copiedCode.value = code;
    setTimeout(() => {
        copiedCode.value = null;
    }, 2500);
}
</script>

<template>
    <div
        v-if="promotions.length > 0"
        class="mx-auto my-8 max-w-7xl px-4 sm:px-6 lg:px-8"
    >
        <div
            v-for="promo in promotions"
            :key="promo.id"
            class="relative flex flex-col items-start justify-between gap-6 overflow-hidden rounded-2xl bg-gradient-to-r from-sage-700 via-sage-800 to-sage-900 p-6 text-cream-50 shadow-sm sm:p-8 md:flex-row md:items-center"
        >
            <div class="max-w-xl space-y-1.5">
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full bg-cream-50 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-sage-900 uppercase"
                    >
                        {{
                            promo.discount_type === 'percentage'
                                ? `${promo.discount_value}% OFF`
                                : `€${promo.discount_value} OFF`
                        }}
                    </span>
                    <span
                        v-if="promo.minimum_order_amount"
                        class="text-xs text-cream-200"
                    >
                        Orders over €{{ promo.minimum_order_amount }}
                    </span>
                </div>
                <h3
                    class="font-serif text-xl font-normal text-cream-50 sm:text-2xl"
                >
                    {{ promo.name }}
                </h3>
                <p
                    v-if="promo.description"
                    class="text-xs leading-relaxed text-cream-200/90 sm:text-sm"
                >
                    {{ promo.description }}
                </p>
            </div>

            <div
                v-if="promo.code"
                class="bg-sage-950/40 flex shrink-0 items-center gap-2 rounded-xl border border-sage-600/40 p-2 sm:p-2.5"
            >
                <div class="px-3 text-center">
                    <span
                        class="block text-[10px] tracking-wider text-cream-300 uppercase"
                        >Promo Code</span
                    >
                    <span
                        class="font-mono text-sm font-bold tracking-widest text-cream-50 sm:text-base"
                        >{{ promo.code }}</span
                    >
                </div>
                <button
                    type="button"
                    @click="copyCode(promo.code)"
                    class="rounded-lg bg-cream-50 px-4 py-2 text-xs font-semibold text-sage-900 transition-colors hover:bg-cream-100 focus:ring-2 focus:ring-cream-300 focus:outline-none"
                >
                    {{ copiedCode === promo.code ? 'Copied!' : 'Copy Code' }}
                </button>
            </div>
        </div>
    </div>
</template>
