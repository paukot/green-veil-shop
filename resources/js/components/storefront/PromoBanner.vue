<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { CheckIcon, CopyIcon } from '@lucide/vue';
import IconButton from '@/components/ui/IconButton.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Section from '@/components/ui/Section.vue';
import type { PromotionBanner } from '@/types/storefront';
import { formatDiscount } from '@/lib/formatters';
import { formatCurrency } from '@/lib/utils';

defineProps<{
    promotions: PromotionBanner[];
}>();

const copiedCode = ref<string | null>(null);
let resetTimer: ReturnType<typeof setTimeout> | null = null;

function copyCode(code: string): void {
    if (!navigator.clipboard?.writeText) return;

    void navigator.clipboard.writeText(code);
    copiedCode.value = code;

    if (resetTimer) clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
        copiedCode.value = null;
    }, 2500);
}

onUnmounted(() => {
    if (resetTimer) clearTimeout(resetTimer);
});

function discountLabel(promo: PromotionBanner): string {
    return formatDiscount(promo.discount_type, promo.discount_value);
}
</script>

<template>
    <Section v-if="promotions.length > 0" tone="accent">
        <div class="flex flex-col gap-8">
            <PageHeader
                level="section"
                eyebrow="Offers"
                title="Rituals, for less"
                description="Apply a code at checkout or pick an offer in your bag."
            />

            <ul
                class="-mx-4 scrollbar-none flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3"
            >
                <li
                    v-for="promo in promotions"
                    :key="promo.id"
                    class="flex w-80 shrink-0 snap-start flex-col gap-6 rounded-2xl border border-clay-200 bg-surface-raised p-6 sm:w-auto"
                >
                    <div class="flex flex-col gap-2">
                        <p class="font-serif text-5xl text-clay-700">
                            {{ discountLabel(promo) }}
                            <span class="text-2xl">off</span>
                        </p>
                        <p class="text-base font-medium text-ink">
                            {{ promo.name }}
                        </p>
                        <p
                            v-if="promo.description"
                            class="line-clamp-2 text-sm text-ink-muted"
                        >
                            {{ promo.description }}
                        </p>
                    </div>

                    <div class="mt-auto flex flex-col gap-3">
                        <p
                            v-if="promo.minimum_order_amount"
                            class="text-xs text-ink-subtle"
                        >
                            On orders over
                            {{ formatCurrency(promo.minimum_order_amount) }}
                        </p>
                        <div
                            v-if="promo.code"
                            class="flex h-12 items-center justify-between gap-2 rounded-full border border-dashed border-clay-300 pr-2 pl-4"
                        >
                            <span
                                class="font-mono text-sm font-semibold tracking-wider text-ink"
                            >
                                {{ promo.code }}
                            </span>
                            <IconButton
                                :label="
                                    copiedCode === promo.code
                                        ? 'Code copied'
                                        : `Copy code ${promo.code}`
                                "
                                size="sm"
                                @click="copyCode(promo.code)"
                            >
                                <CheckIcon
                                    v-if="copiedCode === promo.code"
                                    class="size-4 text-success-700"
                                    aria-hidden="true"
                                />
                                <CopyIcon
                                    v-else
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </IconButton>
                        </div>
                        <p v-else class="text-xs font-medium text-sage-700">
                            Applied automatically in your bag
                        </p>
                    </div>
                </li>
            </ul>
        </div>
    </Section>
</template>
