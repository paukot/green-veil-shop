<script setup lang="ts">
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
    <!-- Skeleton Loading State -->
    <div v-if="loading" aria-busy="true" aria-label="Loading wishlist items">
        <ProductGridSkeleton :count="4" />
    </div>

    <div v-else-if="products.length > 0">
        <div
            class="text-charcoal-600 mb-6 flex items-center justify-between border-b border-cream-200 pb-4 text-xs sm:text-sm"
        >
            <div>
                Saved items:
                <span class="font-semibold text-charcoal-900">{{
                    products.length
                }}</span>
            </div>
            <Link
                v-if="showBrowseLink"
                :href="productsIndex.url()"
                prefetch
                class="text-xs font-medium text-sage-700 underline decoration-sage-300 underline-offset-4 hover:text-sage-900 hover:decoration-sage-700"
            >
                Browse more botanicals &rarr;
            </Link>
        </div>

        <ProductGrid :products="products" />
    </div>

    <!-- Empty State -->
    <div
        v-else
        class="mx-auto max-w-md rounded-3xl border border-cream-200 bg-white p-8 py-16 text-center shadow-xs sm:p-12"
    >
        <div
            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-rose-50 text-rose-500"
        >
            <HeartIcon
                class="h-10 w-10"
                :stroke-width="1.5"
                aria-hidden="true"
            />
        </div>
        <h2 class="mb-2 font-serif text-2xl text-charcoal-900">
            {{ emptyTitle }}
        </h2>
        <p
            class="text-charcoal-600 mb-8 text-xs leading-relaxed font-light sm:text-sm"
        >
            Tap the heart icon on any product across our apothecary catalog to
            save your favourite formulas here.
        </p>
        <Link
            :href="productsIndex.url()"
            prefetch
            class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
        >
            Discover Botanicals
        </Link>
    </div>
</template>
