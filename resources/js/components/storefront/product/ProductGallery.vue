<script setup lang="ts">
import { computed } from 'vue';
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

const activeImage = computed(() => {
    if (props.images.length === 0) return null;
    return props.images[activeIndex.value] || props.images[0];
});
</script>

<template>
    <div class="space-y-4">
        <!-- Main Image Stage -->
        <div
            class="relative flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
        >
            <img
                v-if="activeImage"
                :src="resolveStorageUrl(activeImage.path)"
                :alt="activeImage.alt_text || productName"
                class="h-full w-full object-cover object-center transition-all duration-300"
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
        </div>

        <!-- Gallery Thumbnails -->
        <div v-if="images.length > 1" class="flex gap-2.5 overflow-x-auto scrollbar-none pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
            <button
                v-for="(img, idx) in images"
                :key="img.id"
                type="button"
                @click="activeIndex = idx"
                class="relative h-16 w-16 sm:h-20 sm:w-20 shrink-0 overflow-hidden rounded-xl border-2 bg-white transition-all min-h-[44px] min-w-[44px]"
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
    </div>
</template>
