<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import Chip from '@/components/ui/Chip.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Section from '@/components/ui/Section.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import CatalogListingLayout from '@/components/storefront/CatalogListingLayout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CategoryPageProps } from '@/types/storefront';

defineOptions({
    layout: StorefrontLayout,
});

defineProps<CategoryPageProps>();
</script>

<template>
    <Head :title="`${category.name}`">
        <meta
            name="description"
            :content="
                category.description ||
                `Shop our curated ${category.name} collection of skincare and cosmetics.`
            "
        />
        <meta property="og:title" :content="`${category.name} | Green Veil`" />
        <meta
            property="og:description"
            :content="
                category.description ||
                `Shop our curated ${category.name} collection of skincare and cosmetics.`
            "
        />
        <meta property="og:type" content="website" />
    </Head>

    <!-- Breadcrumbs Navigation -->
    <Breadcrumbs :items="breadcrumbs" />

    <Section spacing="sm" class="border-b border-border">
        <div class="flex flex-col gap-8">
            <PageHeader
                :eyebrow="category.parent ? category.parent.name : 'Collection'"
                :title="category.name"
                :description="category.description ?? undefined"
            />
            <nav
                v-if="category.children && category.children.length > 0"
                aria-label="Subcategories"
                class="-mx-4 scrollbar-none flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0"
            >
                <Chip
                    v-for="child in category.children"
                    :key="child.id"
                    :href="categoryShow.url(child.slug)"
                >
                    {{ child.name }}
                </Chip>
            </nav>
        </div>
    </Section>

    <!-- Main Catalog Content -->
    <CatalogListingLayout
        :products="products"
        :filters="filters"
        :facets="facets"
        :price-range="priceRange"
        :base-url="categoryShow.url(category.slug)"
        :show-categories-in-sidebar="false"
        :reset-url="categoryShow.url(category.slug)"
    />
</template>
