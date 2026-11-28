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
        role="list"
        aria-label="Products"
        class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:gap-x-6 lg:gap-y-12 xl:grid-cols-4"
    >
        <div v-for="product in products" :key="product.id" role="listitem">
            <ProductCard :product="product" />
        </div>
    </div>
    <div v-else class="py-12 text-center text-sm text-ink-muted">
        No products available at the moment.
    </div>
</template>
