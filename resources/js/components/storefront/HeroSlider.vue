<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import {
    ArrowRightIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PauseIcon,
    PlayIcon,
} from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';
import IconButton from '@/components/ui/IconButton.vue';
import { useCarousel } from '@/composables/useCarousel';
import { index as productsIndex } from '@/routes/products';
import type { HeroSlide } from '@/types/storefront';
import { cn, resolveStorageUrl, showPlaceholderOnError } from '@/lib/utils';

const props = defineProps<{
    slides: HeroSlide[];
}>();

const {
    currentIndex,
    isPaused,
    next: nextSlide,
    prev: prevSlide,
    goTo: goToSlide,
    pause,
    resume,
} = useCarousel({
    itemCount: computed(() => props.slides.length),
    intervalMs: 7000,
});

/** Paused by the viewer (button), as opposed to the temporary pause on hover or focus. */
const isPausedByUser = ref(false);

function togglePlayback(): void {
    isPausedByUser.value = !isPausedByUser.value;

    if (isPausedByUser.value) {
        pause();
    } else {
        resume();
    }
}

function pauseTemporarily(): void {
    pause();
}

function resumeUnlessPausedByUser(): void {
    if (!isPausedByUser.value) {
        resume();
    }
}

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        isPausedByUser.value = true;
        pause();
    }
});

const current = computed(() => props.slides[currentIndex.value]);
const counter = (index: number) => String(index + 1).padStart(2, '0');
</script>

<template>
    <section
        v-if="slides.length > 0"
        aria-roledescription="carousel"
        aria-label="Featured collections"
        @mouseenter="pauseTemporarily"
        @mouseleave="resumeUnlessPausedByUser"
        @focusin="pauseTemporarily"
        @focusout="resumeUnlessPausedByUser"
    >
        <Container
            class="grid items-center gap-8 py-8 lg:grid-cols-12 lg:gap-16 lg:py-16"
        >
            <!-- Image -->
            <div
                class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-surface-sunken lg:order-last lg:col-span-7 lg:aspect-[5/4]"
            >
                <img
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    :src="resolveStorageUrl(slide.image)"
                    :srcset="slide.image_srcset ?? undefined"
                    sizes="(min-width: 1024px) 60vw, 100vw"
                    :alt="slide.alt_text || slide.title || ''"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                    :aria-hidden="index !== currentIndex"
                    :class="
                        cn(
                            'absolute inset-0 size-full object-cover transition-opacity duration-700',
                            index === currentIndex
                                ? 'opacity-100'
                                : 'opacity-0',
                        )
                    "
                    @error="showPlaceholderOnError"
                />
            </div>

            <!-- Copy and controls -->
            <div class="flex flex-col gap-8 lg:col-span-5">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="translate-y-2 opacity-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="current"
                        :key="current.id"
                        class="flex flex-col gap-6"
                        aria-live="polite"
                    >
                        <p
                            class="text-xs font-semibold tracking-widest text-clay-600 uppercase"
                        >
                            {{ counter(currentIndex) }} /
                            {{ counter(slides.length - 1) }}
                        </p>
                        <h1
                            v-if="current.title"
                            class="font-serif text-5xl tracking-tight text-balance text-ink lg:text-7xl"
                        >
                            {{ current.title }}
                        </h1>
                        <p
                            v-if="current.subtitle"
                            class="max-w-md text-base text-pretty text-ink-muted lg:text-lg"
                        >
                            {{ current.subtitle }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                v-if="current.url"
                                :href="current.url"
                                :as="
                                    current.url_opens_in_new_tab
                                        ? 'a'
                                        : undefined
                                "
                                :target="
                                    current.url_opens_in_new_tab
                                        ? '_blank'
                                        : undefined
                                "
                                size="lg"
                            >
                                {{ current.button_label || 'Shop now' }}
                                <template #trailing>
                                    <ArrowRightIcon
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                </template>
                            </Button>
                            <Button
                                :href="productsIndex.url()"
                                variant="outline"
                                size="lg"
                            >
                                Shop all
                            </Button>
                        </div>
                    </div>
                </Transition>

                <div
                    v-if="slides.length > 1"
                    class="flex items-center gap-4 border-t border-border pt-6"
                >
                    <div class="flex items-center gap-2">
                        <IconButton
                            label="Previous slide"
                            variant="outline"
                            size="sm"
                            @click="prevSlide"
                        >
                            <ChevronLeftIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </IconButton>
                        <IconButton
                            label="Next slide"
                            variant="outline"
                            size="sm"
                            @click="nextSlide"
                        >
                            <ChevronRightIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </IconButton>
                    </div>

                    <div class="flex flex-1 items-center gap-2">
                        <button
                            v-for="(slide, index) in slides"
                            :key="slide.id"
                            type="button"
                            class="group flex h-8 flex-1 items-center"
                            :aria-label="`Show slide ${index + 1}`"
                            :aria-current="
                                index === currentIndex ? 'true' : undefined
                            "
                            @click="goToSlide(index)"
                        >
                            <span
                                :class="
                                    cn(
                                        'h-px w-full transition-colors',
                                        index === currentIndex
                                            ? 'bg-ink'
                                            : 'bg-border-strong group-hover:bg-ink-subtle',
                                    )
                                "
                            />
                        </button>
                    </div>

                    <IconButton
                        :label="isPaused ? 'Play slideshow' : 'Pause slideshow'"
                        size="sm"
                        @click="togglePlayback"
                    >
                        <PlayIcon
                            v-if="isPausedByUser"
                            class="size-4"
                            aria-hidden="true"
                        />
                        <PauseIcon v-else class="size-4" aria-hidden="true" />
                    </IconButton>
                </div>
            </div>
        </Container>
    </section>
</template>
