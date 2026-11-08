<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { resolveStorageUrl } from '@/lib/utils';

export type GalleryImageItem = {
    id: number | string;
    path: string;
    alt_text?: string | null;
};

const props = defineProps<{
    images: GalleryImageItem[];
    productName: string;
    discountPercentage?: number | null;
}>();

const activeIndex = defineModel<number>('activeIndex', { default: 0 });
const isLightboxOpen = ref(false);
const isZoomed = ref(false);

const activeImage = computed(() => {
    if (props.images.length === 0) return null;
    return props.images[activeIndex.value] || props.images[0];
});

function openLightbox() {
    if (!activeImage.value) return;
    isLightboxOpen.value = true;
    isZoomed.value = false;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    isLightboxOpen.value = false;
    isZoomed.value = false;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
}

function nextImage() {
    if (props.images.length <= 1) return;
    activeIndex.value = (activeIndex.value + 1) % props.images.length;
}

function prevImage() {
    if (props.images.length <= 1) return;
    activeIndex.value =
        (activeIndex.value - 1 + props.images.length) % props.images.length;
}

function handleKeydown(e: KeyboardEvent) {
    if (!isLightboxOpen.value) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Main Image Stage -->
        <div
            class="group relative flex aspect-square cursor-zoom-in items-center justify-center overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
            @click="openLightbox"
            role="button"
            tabindex="0"
            @keydown.enter="openLightbox"
            :aria-label="`Zoom into ${productName} image`"
        >
            <img
                v-if="activeImage"
                :src="resolveStorageUrl(activeImage.path)"
                :alt="activeImage.alt_text || productName"
                class="h-full w-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-105"
            />
            <div
                v-else
                class="text-charcoal-400 p-8 text-center text-sm font-light"
            >
                <svg
                    class="mx-auto mb-2 h-16 w-16 text-cream-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                <span>Botanical Image Preview</span>
            </div>

            <!-- Savings Badge -->
            <span
                v-if="discountPercentage"
                class="absolute top-4 left-4 rounded-full bg-sage-800 px-3 py-1 text-[11px] font-semibold tracking-wider text-cream-50 uppercase shadow-sm"
            >
                Save {{ discountPercentage }}%
            </span>

            <!-- Zoom Indicator Hover Badge -->
            <div
                v-if="activeImage"
                class="pointer-events-none absolute right-4 bottom-4 flex items-center gap-1.5 rounded-full bg-charcoal-900/60 px-3 py-1 text-[11px] font-medium text-cream-50 opacity-0 backdrop-blur-xs transition-opacity duration-200 group-hover:opacity-100"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"
                    />
                </svg>
                <span>Click to zoom</span>
            </div>
        </div>

        <!-- Gallery Thumbnails -->
        <div
            v-if="images.length > 1"
            class="-mx-4 flex scrollbar-none gap-2.5 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0"
        >
            <button
                v-for="(img, idx) in images"
                :key="img.id"
                type="button"
                @click="activeIndex = idx"
                class="relative h-16 min-h-[44px] w-16 min-w-[44px] shrink-0 overflow-hidden rounded-xl border-2 bg-white transition-all sm:h-20 sm:w-20"
                :class="
                    activeIndex === idx
                        ? 'border-sage-700 ring-2 ring-sage-200'
                        : 'border-cream-200 opacity-70 hover:opacity-100'
                "
                :aria-label="`View image ${idx + 1}`"
            >
                <img
                    :src="resolveStorageUrl(img.path)"
                    :alt="img.alt_text || productName"
                    loading="lazy"
                    class="h-full w-full object-cover"
                />
            </button>
        </div>

        <!-- Fullscreen Image Lightbox Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isLightboxOpen && activeImage"
                    class="fixed inset-0 z-50 flex flex-col items-center justify-between bg-charcoal-950/95 p-4 backdrop-blur-md sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="`Enlarged view of ${productName}`"
                >
                    <!-- Top Toolbar -->
                    <div
                        class="flex w-full max-w-6xl items-center justify-between text-cream-100"
                    >
                        <div
                            class="text-xs font-light tracking-wider uppercase"
                        >
                            <span>{{ activeIndex + 1 }}</span>
                            <span class="text-charcoal-400"> / </span>
                            <span>{{ images.length }}</span>
                            <span
                                class="text-charcoal-400 ml-3 hidden sm:inline"
                            >
                                {{ productName }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Toggle Zoom button -->
                            <button
                                type="button"
                                @click="isZoomed = !isZoomed"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-charcoal-800/80 text-cream-100 transition-colors hover:bg-charcoal-700"
                                :title="isZoomed ? 'Actual size' : 'Zoom in'"
                            >
                                <svg
                                    v-if="!isZoomed"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM13.5 10.5h-6"
                                    />
                                </svg>
                            </button>

                            <!-- Close button -->
                            <button
                                type="button"
                                @click="closeLightbox"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-charcoal-800/80 text-cream-100 transition-colors hover:bg-charcoal-700"
                                title="Close (Esc)"
                            >
                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Main Stage Image with Navigation Arrows -->
                    <div
                        class="relative flex w-full max-w-5xl flex-1 items-center justify-center overflow-hidden py-4"
                    >
                        <!-- Prev Arrow -->
                        <button
                            v-if="images.length > 1"
                            type="button"
                            @click="prevImage"
                            class="absolute left-2 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-charcoal-800/80 text-cream-100 shadow-md backdrop-blur-xs transition-colors hover:bg-charcoal-700 sm:left-4"
                            aria-label="Previous image"
                        >
                            <svg
                                class="h-6 w-6"
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

                        <!-- The Image -->
                        <div
                            class="flex max-h-full max-w-full items-center justify-center overflow-auto"
                            :class="{
                                'cursor-zoom-out': isZoomed,
                                'cursor-zoom-in': !isZoomed,
                            }"
                            @click="isZoomed = !isZoomed"
                        >
                            <img
                                :src="resolveStorageUrl(activeImage.path)"
                                :alt="activeImage.alt_text || productName"
                                class="rounded-xl object-contain transition-transform duration-300"
                                :class="
                                    isZoomed
                                        ? 'max-h-none max-w-none scale-150'
                                        : 'max-h-[75vh] max-w-[85vw]'
                                "
                            />
                        </div>

                        <!-- Next Arrow -->
                        <button
                            v-if="images.length > 1"
                            type="button"
                            @click="nextImage"
                            class="absolute right-2 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-charcoal-800/80 text-cream-100 shadow-md backdrop-blur-xs transition-colors hover:bg-charcoal-700 sm:right-4"
                            aria-label="Next image"
                        >
                            <svg
                                class="h-6 w-6"
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

                    <!-- Bottom Thumbnail Strip -->
                    <div
                        v-if="images.length > 1"
                        class="flex max-w-xl gap-2 overflow-x-auto p-2"
                    >
                        <button
                            v-for="(img, idx) in images"
                            :key="img.id"
                            type="button"
                            @click="activeIndex = idx"
                            class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border-2 transition-all"
                            :class="
                                activeIndex === idx
                                    ? 'border-sage-400 ring-sage-400/30 ring-2'
                                    : 'border-transparent opacity-50 hover:opacity-100'
                            "
                        >
                            <img
                                :src="resolveStorageUrl(img.path)"
                                :alt="img.alt_text || productName"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
