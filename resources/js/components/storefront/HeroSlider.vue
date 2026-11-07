<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { HeroSlide } from '@/types/storefront';
import { resolveStorageUrl } from '@/lib/utils';
import { useCarousel } from '@/composables/useCarousel';

const props = defineProps<{
    slides: HeroSlide[];
}>();

const {
    currentIndex,
    next: nextSlide,
    prev: prevSlide,
    goTo: goToSlide,
} = useCarousel({
    itemCount: computed(() => props.slides.length),
    intervalMs: 6000,
});
</script>

<template>
    <section
        v-if="slides.length > 0"
        class="relative min-h-[440px] w-full overflow-hidden bg-cream-200 sm:min-h-0 sm:aspect-[16/9] lg:aspect-[21/9] lg:max-h-[580px]"
    >
        <!-- Slides -->
        <div class="relative h-full w-full">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                :class="
                    index === currentIndex
                        ? 'z-10 opacity-100'
                        : 'pointer-events-none z-0 opacity-0'
                "
            >
                <!-- Background Image or Botanical Fallback -->
                <div class="absolute inset-0 bg-sage-900">
                    <img
                        v-if="slide.image"
                        :src="resolveStorageUrl(slide.image) || ''"
                        :alt="slide.alt_text || slide.title || 'Slide image'"
                        class="h-full w-full object-cover object-center opacity-85"
                        loading="eager"
                        @error="
                            (e) =>
                                ((e.target as HTMLElement).style.display =
                                    'none')
                        "
                    />
                    <!-- Overlay gradient for crisp typography readability -->
                    <div
                        class="from-sage-950/80 via-sage-950/40 absolute inset-0 bg-gradient-to-r to-transparent"
                    ></div>
                </div>

                <!-- Text Content -->
                <div
                    class="relative z-20 mx-auto flex h-full max-w-7xl items-center px-5 py-12 sm:px-12 sm:py-0 lg:px-16"
                >
                    <div class="max-w-xl space-y-3 sm:space-y-4 text-white">
                        <span
                            class="inline-block rounded-full bg-sage-700/80 px-3 py-1 text-xs font-medium tracking-widest text-cream-100 uppercase backdrop-blur-xs"
                        >
                            Botanical Formula
                        </span>
                        <h1
                            class="font-serif text-2xl leading-tight font-normal text-cream-50 sm:text-4xl md:text-5xl lg:text-6xl"
                        >
                            {{ slide.title }}
                        </h1>
                        <p
                            v-if="slide.subtitle"
                            class="text-xs sm:text-base leading-relaxed font-light text-cream-200/90 line-clamp-3 sm:line-clamp-none"
                        >
                            {{ slide.subtitle }}
                        </p>
                        <div v-if="slide.url" class="pt-2">
                            <a
                                v-if="
                                    slide.url_opens_in_new_tab ||
                                    slide.url.startsWith('http')
                                "
                                :href="slide.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-full bg-cream-50 px-6 py-3 text-xs font-semibold tracking-wider text-sage-900 uppercase shadow-md transition-all duration-200 hover:bg-cream-100 hover:shadow-lg"
                            >
                                <span>{{
                                    slide.button_label || 'Discover'
                                }}</span>
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
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                                    />
                                </svg>
                            </a>
                            <Link
                                v-else
                                :href="slide.url"
                                class="inline-flex items-center gap-2 rounded-full bg-cream-50 px-6 py-3 text-xs font-semibold tracking-wider text-sage-900 uppercase shadow-md transition-all duration-200 hover:bg-cream-100 hover:shadow-lg"
                            >
                                <span>{{
                                    slide.button_label || 'Discover'
                                }}</span>
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
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls (Arrows) -->
        <div
            v-if="slides.length > 1"
            class="pointer-events-none absolute inset-y-0 right-4 left-4 z-20 flex items-center justify-between"
        >
            <button
                type="button"
                @click="prevSlide"
                class="pointer-events-auto rounded-full bg-cream-50/80 p-2 text-sage-900 shadow transition-all hover:bg-cream-50 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                aria-label="Previous slide"
            >
                <svg
                    class="h-5 w-5"
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
                @click="nextSlide"
                class="pointer-events-auto rounded-full bg-cream-50/80 p-2 text-sage-900 shadow transition-all hover:bg-cream-50 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                aria-label="Next slide"
            >
                <svg
                    class="h-5 w-5"
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

        <!-- Dots indicators -->
        <div
            v-if="slides.length > 1"
            class="absolute right-0 bottom-4 left-0 z-20 flex justify-center space-x-2"
        >
            <button
                v-for="(_, index) in slides"
                :key="index"
                @click="goToSlide(index)"
                class="h-2.5 w-2.5 rounded-full transition-all duration-300 focus:outline-none"
                :class="
                    index === currentIndex
                        ? 'w-7 bg-cream-50'
                        : 'bg-cream-100/60 hover:bg-cream-100'
                "
                :aria-label="`Go to slide ${index + 1}`"
            />
        </div>
    </section>
</template>
