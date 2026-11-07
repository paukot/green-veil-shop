<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
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
    <Head :title="`${category.name} | Green Veil`">
        <meta
            name="description"
            :content="
                category.description ||
                `Explore our curated botanical collection of ${category.name} skincare and cosmetics.`
            "
        />
        <meta property="og:title" :content="`${category.name} | Green Veil`" />
        <meta
            property="og:description"
            :content="
                category.description ||
                `Explore our curated botanical collection of ${category.name} skincare and cosmetics.`
            "
        />
        <meta property="og:type" content="website" />
    </Head>

    <!-- Breadcrumbs Navigation -->
    <Breadcrumbs :items="breadcrumbs" />

    <!-- Category Header Banner -->
    <section
        class="border-b border-cream-200 bg-linear-to-b from-cream-100 to-cream-50 py-10 sm:py-14"
    >
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <span
                class="text-xs font-semibold tracking-widest text-sage-700 uppercase"
            >
                {{
                    category.parent
                        ? category.parent.name
                        : 'Botanical Collection'
                }}
            </span>
            <h1
                class="mt-2 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl md:text-5xl"
            >
                {{ category.name }}
            </h1>
            <p
                v-if="category.description"
                class="text-charcoal-600 mx-auto mt-4 max-w-2xl text-sm leading-relaxed font-light sm:text-base"
            >
                {{ category.description }}
            </p>

            <!-- Subcategory navigation pills -->
            <div
                v-if="category.children && category.children.length > 0"
                class="mt-8 flex flex-wrap items-center justify-center gap-2"
            >
                <Link
                    v-for="child in category.children"
                    :key="child.id"
                    :href="categoryShow.url(child.slug)"
                    class="rounded-full border border-cream-300 bg-white px-4 py-1.5 text-xs font-medium text-charcoal-700 shadow-xs transition-all hover:border-sage-500 hover:bg-sage-50 hover:text-sage-800"
                >
                    {{ child.name }}
                </Link>
            </div>
        </div>
    </section>

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
