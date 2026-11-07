<script setup lang="ts">
import { computed, ref } from 'vue';
import type { PromotionBanner } from '@/types/storefront';
import { useCarousel } from '@/composables/useCarousel';

const props = defineProps<{
    promotions: PromotionBanner[];
}>();

const copiedCode = ref<string | null>(null);

const {
    currentIndex,
    next: nextSlide,
    prev: prevSlide,
    goTo: goToSlide,
    pause: pauseTimer,
    resume: resumeTimer,
} = useCarousel({
    itemCount: computed(() => props.promotions.length),
    intervalMs: 5000,
});

const hasMultiple = computed(() => props.promotions.length > 1);
const currentPromo = computed(
    () => props.promotions[currentIndex.value] ?? props.promotions[0],
);

function copyCode(code: string) {
    if (!navigator.clipboard?.writeText) return;
    navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => {
        copiedCode.value = null;
    }, 2500);
}
</script>

<template>
    <div
        v-if="promotions.length > 0 && currentPromo"
        class="mx-auto my-8 max-w-7xl px-4 sm:px-6 lg:px-8"
        aria-label="Promotions"
        role="region"
        @mouseenter="pauseTimer"
        @mouseleave="resumeTimer"
    >
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-sage-700 via-sage-800 to-sage-900 p-6 text-cream-50 shadow-md sm:p-8"
        >
            <!-- Background Decorative Glow -->
            <div
                class="pointer-events-none absolute -right-16 -bottom-16 h-64 w-64 rounded-full bg-cream-50/5 blur-2xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -top-16 -left-16 h-64 w-64 rounded-full bg-sage-500/10 blur-2xl"
                aria-hidden="true"
            />

            <!-- Top Row: Counter and Controls when multiple -->
            <div
                v-if="hasMultiple"
                class="relative z-10 mb-4 flex items-center justify-between border-b border-sage-600/30 pb-3"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-sage-950/40 px-2.5 py-0.5 text-[11px] font-medium tracking-wide text-cream-200"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-cream-300"
                        />
                        Exclusive Offers ({{ currentIndex + 1 }} of
                        {{ promotions.length }})
                    </span>
                </div>

                <!-- Navigation Arrows -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-sage-950/40 text-cream-200 transition-colors hover:bg-cream-50 hover:text-sage-900 focus:ring-2 focus:ring-cream-300 focus:outline-none sm:h-7 sm:w-7"
                        aria-label="Previous promotion"
                        @click="prevSlide"
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
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-sage-950/40 text-cream-200 transition-colors hover:bg-cream-50 hover:text-sage-900 focus:ring-2 focus:ring-cream-300 focus:outline-none sm:h-7 sm:w-7"
                        aria-label="Next promotion"
                        @click="nextSlide"
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
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Promotion Content (Animated Transition) -->
            <Transition name="promo-fade" mode="out-in">
                <div
                    :key="currentPromo.id"
                    class="relative z-10 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center"
                >
                    <div class="max-w-xl space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-cream-50 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-sage-900 uppercase shadow-xs"
                            >
                                {{
                                    currentPromo.discount_type === 'percentage'
                                        ? `${currentPromo.discount_value}% OFF`
                                        : `€${currentPromo.discount_value} OFF`
                                }}
                            </span>
                            <span
                                v-if="currentPromo.minimum_order_amount"
                                class="text-xs text-cream-200"
                            >
                                Orders over €{{
                                    currentPromo.minimum_order_amount
                                }}
                            </span>
                        </div>
                        <h3
                            class="font-serif text-xl font-normal text-cream-50 sm:text-2xl"
                        >
                            {{ currentPromo.name }}
                        </h3>
                        <p
                            v-if="currentPromo.description"
                            class="text-xs leading-relaxed text-cream-200/90 sm:text-sm"
                        >
                            {{ currentPromo.description }}
                        </p>
                    </div>

                    <div
                        v-if="currentPromo.code"
                        class="flex w-full shrink-0 items-center justify-between gap-2 rounded-xl border border-sage-600/40 bg-sage-950/40 p-2 shadow-xs sm:w-auto sm:justify-start sm:p-2.5"
                    >
                        <div class="px-2 text-left sm:px-3 sm:text-center">
                            <span
                                class="block text-[10px] tracking-wider text-cream-300 uppercase"
                                >Promo Code</span
                            >
                            <span
                                class="font-mono text-sm font-bold tracking-widest text-cream-50 sm:text-base"
                                >{{ currentPromo.code }}</span
                            >
                        </div>
                        <button
                            type="button"
                            class="flex min-h-[44px] items-center justify-center rounded-lg bg-cream-50 px-4 py-2 text-xs font-semibold text-sage-900 transition-colors hover:bg-cream-100 focus:ring-2 focus:ring-cream-300 focus:outline-none"
                            @click="copyCode(currentPromo.code)"
                        >
                            {{
                                copiedCode === currentPromo.code
                                    ? 'Copied!'
                                    : 'Copy Code'
                            }}
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Bottom Pagination Dots when multiple -->
            <div
                v-if="hasMultiple"
                class="relative z-10 mt-5 flex items-center justify-center gap-1.5"
            >
                <button
                    v-for="(promo, index) in promotions"
                    :key="promo.id"
                    type="button"
                    class="h-1.5 rounded-full transition-all duration-300 focus:ring-1 focus:ring-cream-200 focus:outline-none"
                    :class="
                        index === currentIndex
                            ? 'w-7 bg-cream-50'
                            : 'w-1.5 bg-cream-50/40 hover:bg-cream-50/70'
                    "
                    :aria-label="`Go to promotion ${index + 1}`"
                    @click="goToSlide(index)"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.promo-fade-enter-active,
.promo-fade-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s ease;
}

.promo-fade-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.promo-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
