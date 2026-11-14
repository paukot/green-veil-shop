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
        class="grid grid-cols-2 gap-3.5 sm:gap-6 md:grid-cols-3 xl:grid-cols-4"
        role="status"
        aria-label="Loading botanical products"
    >
        <ProductCardSkeleton v-for="i in count" :key="i" />
        <span class="sr-only">Loading botanical products, please wait...</span>
    </div>
</template>
