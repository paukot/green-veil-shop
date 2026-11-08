<script setup lang="ts">
import type { ProductVariantItem } from '@/types/storefront';

defineProps<{
    variants?: ProductVariantItem[];
    selectedVariant: ProductVariantItem | null;
    isInStock: boolean;
    trackInventory: boolean;
    stockQuantity: number;
}>();

const emit = defineEmits<{
    (e: 'select', variant: ProductVariantItem): void;
}>();
</script>

<template>
    <div class="space-y-3">
        <!-- Interactive Variant / Shade Selector -->
        <div
            v-if="variants && variants.length > 0"
            class="space-y-3 rounded-xl border border-cream-200 bg-cream-50/60 p-4"
        >
            <div class="flex items-center justify-between text-xs">
                <span
                    class="font-semibold tracking-wider text-charcoal-800 uppercase"
                >
                    Choose Shade / Variant:
                </span>
                <span v-if="selectedVariant" class="font-medium text-sage-800">
                    {{ selectedVariant.name }}
                </span>
            </div>

            <!-- Swatches or Pill buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    v-for="variant in variants"
                    :key="variant.id"
                    type="button"
                    @click="emit('select', variant)"
                    class="relative flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
                    :class="
                        selectedVariant?.id === variant.id
                            ? 'border-sage-700 bg-white text-charcoal-900 shadow-xs ring-2 ring-sage-300'
                            : 'hover:border-sage-400 border-cream-300 bg-white/80 text-charcoal-700'
                    "
                >
                    <!-- Color Swatch Circle if color_hex present -->
                    <span
                        v-if="variant.color_hex"
                        class="border-charcoal-200 h-4 w-4 shrink-0 rounded-full border shadow-inner"
                        :style="{ backgroundColor: variant.color_hex }"
                    />
                    <span>{{ variant.name }}</span>
                    <span
                        v-if="!variant.is_in_stock"
                        class="text-[10px] font-normal text-rose-500"
                    >
                        (Out)
                    </span>
                </button>
            </div>
        </div>

        <!-- Stock Status -->
        <div class="flex items-center gap-2 text-xs">
            <span
                v-if="isInStock"
                class="flex flex-wrap items-center gap-2 font-medium text-charcoal-700"
            >
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                <span>In Stock</span>
                <span
                    v-if="trackInventory && stockQuantity <= 5"
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"
                    />
                    Only {{ stockQuantity }} left in current batch
                </span>
            </span>
            <span
                v-else
                class="flex items-center gap-2 font-medium text-rose-700"
            >
                <span class="h-2.5 w-2.5 rounded-full bg-rose-500" />
                <span>Currently Out of Stock</span>
            </span>
        </div>
    </div>
</template>
