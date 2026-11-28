<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    XIcon,
    ZoomInIcon,
    ZoomOutIcon,
} from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import IconButton from '@/components/ui/IconButton.vue';
import { useDialogBehaviour } from '@/composables/useDialogBehaviour';
import { cn, resolveStorageUrl, showPlaceholderOnError } from '@/lib/utils';

export type GalleryImageItem = {
    id: number | string;
    path: string;
    srcset?: string | null;
    alt_text?: string | null;
};

const props = defineProps<{
    images: GalleryImageItem[];
    productName: string;
    discountPercentage?: number | null;
    class?: string;
}>();

const activeIndex = defineModel<number>('activeIndex', { default: 0 });
const isLightboxOpen = ref(false);
const isZoomed = ref(false);
const lightboxPanel = ref<HTMLElement | null>(null);

const activeImage = computed(() => {
    if (props.images.length === 0) return null;
    return props.images[activeIndex.value] || props.images[0];
});

function openLightbox() {
    if (!activeImage.value) return;
    isZoomed.value = false;
    isLightboxOpen.value = true;
}

function closeLightbox() {
    isLightboxOpen.value = false;
    isZoomed.value = false;
}

useDialogBehaviour(isLightboxOpen, lightboxPanel, closeLightbox);

function nextImage() {
    if (props.images.length <= 1) return;
    activeIndex.value = (activeIndex.value + 1) % props.images.length;
}

function prevImage() {
    if (props.images.length <= 1) return;
    activeIndex.value =
        (activeIndex.value - 1 + props.images.length) % props.images.length;
}

function handleArrowKeys(e: KeyboardEvent) {
    if (!isLightboxOpen.value) return;
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
}

// Teleported lightbox is client-only, so it never takes part in SSR hydration.
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
    window.addEventListener('keydown', handleArrowKeys);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleArrowKeys);
});
</script>

<template>
    <div :class="cn('flex flex-col gap-4 lg:flex-row-reverse', props.class)">
        <!-- Main image -->
        <button
            type="button"
            class="group relative aspect-[4/5] flex-1 cursor-zoom-in overflow-hidden rounded-2xl bg-surface-sunken"
            :aria-label="`Enlarge image of ${productName}`"
            @click="openLightbox"
        >
            <img
                v-if="activeImage"
                :src="resolveStorageUrl(activeImage.path)"
                :srcset="activeImage.srcset ?? undefined"
                sizes="(min-width: 1024px) 50vw, 100vw"
                :alt="activeImage.alt_text || productName"
                fetchpriority="high"
                class="size-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                @error="showPlaceholderOnError"
            />
            <Badge
                v-if="discountPercentage"
                tone="inverse"
                class="absolute top-4 left-4 bg-clay-700"
            >
                Save {{ discountPercentage }}%
            </Badge>
            <span
                v-if="activeImage"
                class="pointer-events-none absolute right-4 bottom-4 flex size-12 items-center justify-center rounded-full bg-surface-raised/90 text-ink opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                aria-hidden="true"
            >
                <ZoomInIcon class="size-4" />
            </span>
        </button>

        <!-- Thumbnails: a vertical rail on desktop, a scroller on mobile -->
        <div
            v-if="images.length > 1"
            role="group"
            aria-label="Product images"
            class="-mx-4 scrollbar-none flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0 lg:max-h-[80vh] lg:flex-col lg:overflow-y-auto"
        >
            <button
                v-for="(img, idx) in images"
                :key="img.id"
                type="button"
                :class="
                    cn(
                        'size-16 shrink-0 overflow-hidden rounded-lg ring-offset-2 ring-offset-surface transition',
                        activeIndex === idx
                            ? 'ring-2 ring-ink'
                            : 'opacity-60 hover:opacity-100',
                    )
                "
                :aria-label="`Show image ${idx + 1} of ${images.length}`"
                :aria-current="activeIndex === idx"
                @click="activeIndex = idx"
            >
                <img
                    :src="resolveStorageUrl(img.path)"
                    :srcset="img.srcset ?? undefined"
                    sizes="4rem"
                    alt=""
                    loading="lazy"
                    class="size-full object-cover"
                    @error="showPlaceholderOnError"
                />
            </button>
        </div>

        <!-- Lightbox -->
        <Teleport v-if="isMounted" to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isLightboxOpen && activeImage"
                    ref="lightboxPanel"
                    class="fixed inset-0 z-50 flex flex-col gap-4 bg-charcoal-950/95 p-4 text-cream-100 backdrop-blur-md sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="`Images of ${productName}`"
                    tabindex="-1"
                >
                    <div class="flex items-center justify-between gap-4">
                        <p class="truncate text-sm">
                            <span class="tabular-nums">
                                {{ activeIndex + 1 }} / {{ images.length }}
                            </span>
                            <span class="ml-4 hidden text-cream-300 sm:inline">
                                {{ productName }}
                            </span>
                        </p>
                        <div class="flex items-center gap-2">
                            <IconButton
                                :label="isZoomed ? 'Fit to screen' : 'Zoom in'"
                                class="text-cream-100 hover:bg-white/10"
                                @click="isZoomed = !isZoomed"
                            >
                                <ZoomOutIcon
                                    v-if="isZoomed"
                                    class="size-6"
                                    aria-hidden="true"
                                />
                                <ZoomInIcon
                                    v-else
                                    class="size-6"
                                    aria-hidden="true"
                                />
                            </IconButton>
                            <IconButton
                                label="Close"
                                class="text-cream-100 hover:bg-white/10"
                                @click="closeLightbox"
                            >
                                <XIcon class="size-6" aria-hidden="true" />
                            </IconButton>
                        </div>
                    </div>

                    <div
                        class="relative flex min-h-0 flex-1 items-center justify-center"
                    >
                        <IconButton
                            v-if="images.length > 1"
                            label="Previous image"
                            class="absolute left-0 z-10 bg-charcoal-900/60 text-cream-100 hover:bg-charcoal-800"
                            @click="prevImage"
                        >
                            <ChevronLeftIcon
                                class="size-6"
                                aria-hidden="true"
                            />
                        </IconButton>

                        <div
                            :class="
                                cn(
                                    'flex size-full items-center justify-center',
                                    isZoomed
                                        ? 'overflow-auto'
                                        : 'overflow-hidden',
                                )
                            "
                            @click.self="closeLightbox"
                        >
                            <img
                                :src="resolveStorageUrl(activeImage.path)"
                                :alt="activeImage.alt_text || productName"
                                :class="
                                    cn(
                                        'rounded-2xl transition-transform duration-300',
                                        isZoomed
                                            ? 'max-w-none scale-150 cursor-zoom-out'
                                            : 'max-h-full max-w-full cursor-zoom-in object-contain',
                                    )
                                "
                                @click="isZoomed = !isZoomed"
                            />
                        </div>

                        <IconButton
                            v-if="images.length > 1"
                            label="Next image"
                            class="absolute right-0 z-10 bg-charcoal-900/60 text-cream-100 hover:bg-charcoal-800"
                            @click="nextImage"
                        >
                            <ChevronRightIcon
                                class="size-6"
                                aria-hidden="true"
                            />
                        </IconButton>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
