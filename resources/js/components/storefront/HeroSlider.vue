<script setup lang="ts">
import { computed, ref } from 'vue';
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

/**
 * Tracks perceived luminance for each slide: 'light' (bright background) | 'dark' (dark background).
 * Defaults to 'dark' (cream text over dark botanical green background).
 */
const slideThemes = ref<Record<number, 'light' | 'dark'>>({});

function detectContrast(event: Event, slideId: number) {
    try {
        const img = event.target as HTMLImageElement;
        if (!img.naturalWidth || !img.naturalHeight) return;

        const canvas = document.createElement('canvas');
        canvas.width = 40;
        canvas.height = 40;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;

        ctx.drawImage(img, 0, 0, 40, 40);
        // Sample the left 55% of the slide where text sits
        const imgData = ctx.getImageData(0, 0, 22, 40).data;
        let totalLuminance = 0;
        const pixelCount = imgData.length / 4;

        for (let i = 0; i < imgData.length; i += 4) {
            const r = imgData[i];
            const g = imgData[i + 1];
            const b = imgData[i + 2];
            // Standard perceived luminance
            totalLuminance += 0.299 * r + 0.587 * g + 0.114 * b;
        }

        const avgLuminance = totalLuminance / pixelCount;
        // If average luminance is > 135, background is light; switch font to dark charcoal
        slideThemes.value[slideId] = avgLuminance > 135 ? 'light' : 'dark';
    } catch {
        // Fallback to dark theme on CORS or canvas errors
        slideThemes.value[slideId] = 'dark';
    }
}

function getSlideTheme(slideId: number): 'light' | 'dark' {
    return slideThemes.value[slideId] || 'dark';
}

const currentTheme = computed<'light' | 'dark'>(() => {
    const current = props.slides[currentIndex.value];
    if (!current) return 'dark';
    return getSlideTheme(current.id);
});
</script>

<template>
    <section
        v-if="slides.length > 0"
        class="relative min-h-[440px] w-full overflow-hidden bg-cream-200 sm:aspect-[16/9] sm:min-h-0 lg:aspect-[21/9] lg:max-h-[580px]"
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
                        class="h-full w-full object-cover object-center"
                        crossorigin="anonymous"
                        loading="eager"
                        @load="(e) => detectContrast(e, slide.id)"
                        @error="
                            (e) =>
                                ((e.target as HTMLElement).style.display =
                                    'none')
                        "
                    />

                    <!-- Clean natural overlays without green tint -->
                    <!-- Light image: whisper-soft subtle vignette behind text -->
                    <div
                        v-if="getSlideTheme(slide.id) === 'light'"
                        class="absolute inset-0 bg-linear-to-t from-white/30 via-white/10 to-transparent sm:bg-linear-to-r sm:from-white/50 sm:via-white/20 sm:to-transparent"
                    ></div>

                    <!-- Dark image or fallback: clean, subtle neutral black dimming -->
                    <div
                        v-else
                        class="absolute inset-0 bg-black/25 sm:bg-linear-to-r sm:from-black/40 sm:via-black/15 sm:to-transparent"
                    ></div>
                </div>

                <!-- Text Content -->
                <div
                    class="relative z-20 mx-auto flex h-full max-w-7xl items-center px-5 py-12 sm:px-12 sm:py-0 lg:px-16"
                >
                    <div
                        class="max-w-xl space-y-3 transition-colors duration-300 sm:space-y-4"
                        :class="
                            getSlideTheme(slide.id) === 'light'
                                ? 'text-charcoal-900'
                                : 'text-cream-50'
                        "
                    >
                        <span
                            class="inline-block rounded-full px-3.5 py-1 text-xs font-medium tracking-widest uppercase transition-colors duration-300"
                            :class="
                                getSlideTheme(slide.id) === 'light'
                                    ? 'border border-charcoal-900/15 bg-charcoal-900/10 text-charcoal-800'
                                    : 'border border-white/20 bg-white/10 text-cream-100 backdrop-blur-xs'
                            "
                        >
                            Botanical Formula
                        </span>

                        <h1
                            class="font-serif text-2xl leading-tight font-normal transition-colors duration-300 sm:text-4xl md:text-5xl lg:text-6xl"
                            :class="
                                getSlideTheme(slide.id) === 'light'
                                    ? 'text-charcoal-900'
                                    : 'text-cream-50'
                            "
                        >
                            {{ slide.title }}
                        </h1>

                        <p
                            v-if="slide.subtitle"
                            class="line-clamp-3 text-xs leading-relaxed font-light transition-colors duration-300 sm:line-clamp-none sm:text-base sm:leading-relaxed"
                            :class="
                                getSlideTheme(slide.id) === 'light'
                                    ? 'text-charcoal-700'
                                    : 'text-cream-200'
                            "
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
                                class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-xs font-semibold tracking-wider uppercase shadow-md transition-all duration-200 hover:scale-105 active:scale-95"
                                :class="
                                    getSlideTheme(slide.id) === 'light'
                                        ? 'bg-sage-800 text-cream-50 shadow-sage-900/10 hover:bg-sage-900'
                                        : 'border border-white/30 bg-cream-50 text-sage-900 hover:bg-white'
                                "
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
                                class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-xs font-semibold tracking-wider uppercase shadow-md transition-all duration-200 hover:scale-105 active:scale-95"
                                :class="
                                    getSlideTheme(slide.id) === 'light'
                                        ? 'bg-sage-800 text-cream-50 shadow-sage-900/10 hover:bg-sage-900'
                                        : 'border border-white/30 bg-cream-50 text-sage-900 hover:bg-white'
                                "
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
                class="pointer-events-auto rounded-full p-2.5 transition-all duration-300 hover:scale-105 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                :class="
                    currentTheme === 'light'
                        ? 'border border-black/10 bg-white/80 text-charcoal-900 shadow-xs hover:bg-white'
                        : 'border border-white/20 bg-black/30 text-white shadow-xs hover:bg-black/50'
                "
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
                class="pointer-events-auto rounded-full p-2.5 transition-all duration-300 hover:scale-105 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                :class="
                    currentTheme === 'light'
                        ? 'border border-black/10 bg-white/80 text-charcoal-900 shadow-xs hover:bg-white'
                        : 'border border-white/20 bg-black/30 text-white shadow-xs hover:bg-black/50'
                "
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
                class="h-2 rounded-full transition-all duration-300 focus:outline-none"
                :class="[
                    index === currentIndex ? 'w-6 shadow-xs' : 'w-2',
                    currentTheme === 'light'
                        ? index === currentIndex
                            ? 'bg-charcoal-900'
                            : 'bg-charcoal-900/30 hover:bg-charcoal-900/60'
                        : index === currentIndex
                          ? 'bg-cream-50'
                          : 'bg-cream-100/40 hover:bg-cream-100/70',
                ]"
                :aria-label="`Go to slide ${index + 1}`"
            />
        </div>
    </section>
</template>
