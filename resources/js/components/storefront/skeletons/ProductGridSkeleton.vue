<script setup lang="ts">
import ProductCardSkeleton from '@/components/storefront/skeletons/ProductCardSkeleton.vue';

withDefaults(
    defineProps<{
        count?: number;
    }>(),
    {
        count: 12,
    },
);
</script>

<template>
    <div
        class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:gap-x-6 lg:gap-y-12 xl:grid-cols-4"
        role="status"
        aria-label="Loading products"
    >
        <ProductCardSkeleton v-for="i in count" :key="i" />
    </div>
</template>
