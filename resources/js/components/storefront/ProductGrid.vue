<script setup lang="ts">
import type { ProductCardData } from '@/types/storefront';
import ProductCard from '@/components/storefront/ProductCard.vue';

defineProps<{
    products: ProductCardData[];
}>();
</script>

<template>
    <div
        v-if="products.length > 0"
        class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:gap-x-6 lg:gap-y-12 xl:grid-cols-4"
    >
        <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
        />
    </div>
    <div v-else class="py-12 text-center text-sm text-ink-muted">
        No products available at the moment.
    </div>
</template>
