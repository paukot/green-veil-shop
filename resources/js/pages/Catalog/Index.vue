<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/ui/PageHeader.vue';
import Section from '@/components/ui/Section.vue';
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
    return 'All Products | Green Veil';
});
</script>

<template>
    <Head :title="pageTitle">
        <meta
            name="description"
            content="Explore our complete range of skincare, makeup, hair, body and fragrance from leading beauty brands."
        />
        <meta property="og:title" :content="pageTitle" />
        <meta
            property="og:description"
            content="Explore our complete range of skincare, makeup, hair, body and fragrance from leading beauty brands."
        />
        <meta property="og:type" content="website" />
    </Head>

    <Section
        spacing="sm"
        class="border-b border-border"
        aria-label="Catalog header"
    >
        <PageHeader
            :eyebrow="filters.q ? 'Search results' : 'The collection'"
            :title="filters.q ? `“${filters.q}”` : 'All products'"
            :description="
                filters.q
                    ? 'Products matching your search.'
                    : 'Skincare, makeup, hair, body and fragrance from the brands you love.'
            "
        />
    </Section>

    <!-- Main Catalog Content -->
    <CatalogListingLayout
        :products="products"
        :filters="filters"
        :categories="categories"
        :facets="facets"
        :price-range="priceRange"
        :base-url="productsIndex.url()"
        empty-title="No matching products"
    />
</template>
