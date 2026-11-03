<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import type { ProductCardData } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';

defineOptions({
    layout: StorefrontLayout,
});

defineProps<{
    products: ProductCardData[];
}>();
</script>

<template>
    <Head title="Your Wishlist | Saved Botanicals | Green Veil" />

    <!-- Header Banner -->
    <section
        class="border-b border-cream-200 bg-gradient-to-b from-cream-100 to-cream-50 py-10 sm:py-14"
    >
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <span
                class="text-xs font-semibold tracking-widest text-sage-700 uppercase"
            >
                Your Personal Wishlist
            </span>
            <h1
                class="mt-2 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl"
            >
                Saved Botanicals
            </h1>
            <p
                class="text-charcoal-600 mt-3 text-sm leading-relaxed font-light"
            >
                A conscious curation of your favourite botanical formulations,
                gentle mists, and daily self-care rituals.
            </p>
        </div>
    </section>

    <!-- Main Favourites Content -->
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
        <div v-if="products.length > 0">
            <div
                class="text-charcoal-600 mb-8 flex items-center justify-between border-b border-cream-200 pb-4 text-xs sm:text-sm"
            >
                <div>
                    Saved items:
                    <span class="font-semibold text-charcoal-900">{{
                        products.length
                    }}</span>
                </div>
                <Link
                    :href="productsIndex.url()"
                    prefetch
                    class="text-xs font-medium text-sage-700 underline decoration-sage-300 underline-offset-4 hover:text-sage-900 hover:decoration-sage-700"
                >
                    Browse more botanicals &rarr;
                </Link>
            </div>

            <ProductGrid :products="products" />
        </div>

        <!-- Empty Wishlist State -->
        <div
            v-else
            class="mx-auto max-w-md rounded-3xl border border-cream-200 bg-white p-8 py-20 text-center shadow-xs sm:p-12"
        >
            <div
                class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-rose-50 text-rose-500"
            >
                <svg
                    class="h-10 w-10"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.25"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                    />
                </svg>
            </div>
            <h2 class="mb-2 font-serif text-2xl text-charcoal-900">
                Your wishlist is empty
            </h2>
            <p
                class="text-charcoal-600 mb-8 text-xs leading-relaxed font-light sm:text-sm"
            >
                Tap the heart icon on any product across our apothecary catalog
                to save your favourite formulas here.
            </p>
            <Link
                :href="productsIndex.url()"
                prefetch
                class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
            >
                Discover Botanicals
            </Link>
        </div>
    </section>
</template>
