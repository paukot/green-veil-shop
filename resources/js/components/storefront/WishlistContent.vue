<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import ProductGridSkeleton from '@/components/storefront/skeletons/ProductGridSkeleton.vue';
import type { ProductCardData } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import { HeartIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        products: ProductCardData[];
        showBrowseLink?: boolean;
        emptyTitle?: string;
        loading?: boolean;
    }>(),
    {
        showBrowseLink: false,
        emptyTitle: 'Your wishlist is empty',
        loading: false,
    },
);
</script>

<template>
    <div v-if="loading" aria-busy="true" aria-label="Loading saved items">
        <ProductGridSkeleton :count="4" />
    </div>

    <div v-else-if="products.length > 0" class="flex flex-col gap-6">
        <div
            class="flex items-center justify-between gap-4 border-b border-border pb-4 text-sm text-ink-muted"
        >
            <p>
                <span class="font-semibold text-ink">{{
                    products.length
                }}</span>
                saved {{ products.length === 1 ? 'item' : 'items' }}
            </p>
            <Link
                v-if="showBrowseLink"
                :href="productsIndex.url()"
                prefetch
                class="font-medium text-rose-800 underline-offset-4 hover:underline"
            >
                Keep browsing
            </Link>
        </div>
        <ProductGrid :products="products" />
    </div>

    <EmptyState
        v-else
        :title="emptyTitle"
        description="Tap the heart on any product to save it here."
    >
        <template #icon>
            <HeartIcon aria-hidden="true" />
        </template>
        <template #actions>
            <Button :href="productsIndex.url()">Discover products</Button>
        </template>
    </EmptyState>
</template>
