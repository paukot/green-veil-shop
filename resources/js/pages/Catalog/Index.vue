<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import CatalogListingLayout from '@/components/storefront/CatalogListingLayout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { index as productsIndex } from '@/routes/products';
import type { CatalogIndexProps } from '@/types/storefront';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<CatalogIndexProps>();

const pageTitle = computed(() => {
    if (props.filters.q) {
        return `Search: "${props.filters.q}" | Green Veil`;
    }
    return 'Botanical Apothecary | All Products | Green Veil';
});
</script>

<template>
    <Head :title="pageTitle">
        <meta
            name="description"
            content="Explore our complete botanical apothecary collection. Clean formulations, herbal extracts, and sustainable cosmetics."
        />
        <meta property="og:title" :content="pageTitle" />
        <meta
            property="og:description"
            content="Explore our complete botanical apothecary collection. Clean formulations, herbal extracts, and sustainable cosmetics."
        />
        <meta property="og:type" content="website" />
    </Head>

    <!-- Page Header -->
    <section
        class="border-b border-cream-200 bg-linear-to-b from-cream-100 to-cream-50 py-10 sm:py-12"
    >
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <span
                class="text-xs font-semibold tracking-widest text-sage-700 uppercase"
            >
                Botanical Formulations
            </span>
            <h1
                class="mt-2 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl md:text-5xl"
            >
                {{
                    filters.q
                        ? `Search: "${filters.q}"`
                        : 'All Botanical Products'
                }}
            </h1>
            <p
                class="text-charcoal-600 mx-auto mt-3 max-w-xl text-sm leading-relaxed font-light"
            >
                {{
                    filters.q
                        ? 'Showing conscious products matching your botanical search query.'
                        : 'Consciously crafted skin and body care rooted in pure botanical ingredients and restorative rituals.'
                }}
            </p>
        </div>
    </section>

    <!-- Main Catalog Content -->
    <CatalogListingLayout
        :products="products"
        :filters="filters"
        :categories="categories"
        :facets="facets"
        :price-range="priceRange"
        :base-url="productsIndex.url()"
        empty-title="No matching botanical products"
    />
</template>
