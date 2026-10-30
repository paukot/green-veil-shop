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
        class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4 lg:gap-8"
    >
        <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
        />
    </div>
    <div v-else class="text-charcoal-500 py-12 text-center text-sm">
        No products available at the moment.
    </div>
</template>
