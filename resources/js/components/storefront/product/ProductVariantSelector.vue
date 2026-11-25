<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';
import { cn } from '@/lib/utils';
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
    <div class="flex flex-col gap-4">
        <div v-if="variants && variants.length > 0" class="flex flex-col gap-3">
            <p class="flex items-baseline gap-2 text-sm">
                <span class="font-medium text-ink">
                    {{ variants.some((v) => v.color_hex) ? 'Shade' : 'Option' }}
                </span>
                <span v-if="selectedVariant" class="text-ink-muted">
                    {{ selectedVariant.name }}
                </span>
            </p>

            <div class="flex flex-wrap items-center gap-2" role="radiogroup">
                <template v-for="variant in variants" :key="variant.id">
                    <button
                        v-if="variant.color_hex"
                        type="button"
                        role="radio"
                        :aria-checked="selectedVariant?.id === variant.id"
                        :aria-label="variant.name"
                        :title="variant.name"
                        :class="
                            cn(
                                'relative size-8 rounded-full ring-offset-2 ring-offset-surface transition-shadow',
                                selectedVariant?.id === variant.id
                                    ? 'ring-2 ring-ink'
                                    : 'ring-1 ring-border-strong hover:ring-ink-subtle',
                                !variant.is_in_stock && 'opacity-40',
                            )
                        "
                        :style="{ backgroundColor: variant.color_hex }"
                        @click="emit('select', variant)"
                    />
                    <button
                        v-else
                        type="button"
                        role="radio"
                        :aria-checked="selectedVariant?.id === variant.id"
                        :class="
                            cn(
                                'inline-flex h-8 items-center rounded-full border px-4 text-xs font-medium transition-colors',
                                selectedVariant?.id === variant.id
                                    ? 'border-rose-900 bg-rose-900 text-cream-50'
                                    : 'border-border-strong bg-surface-raised text-ink hover:border-ink',
                                !variant.is_in_stock &&
                                    'line-through opacity-50',
                            )
                        "
                        @click="emit('select', variant)"
                    >
                        {{ variant.name }}
                    </button>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <template v-if="isInStock">
                <span
                    class="size-2 rounded-full bg-success-600"
                    aria-hidden="true"
                />
                <span class="font-medium text-ink">In stock</span>
                <Badge
                    v-if="trackInventory && stockQuantity <= 5"
                    tone="warning"
                >
                    Only {{ stockQuantity }} left
                </Badge>
            </template>
            <template v-else>
                <span
                    class="size-2 rounded-full bg-danger-600"
                    aria-hidden="true"
                />
                <span class="font-medium text-danger-700"
                    >Currently sold out</span
                >
            </template>
        </div>
    </div>
</template>
