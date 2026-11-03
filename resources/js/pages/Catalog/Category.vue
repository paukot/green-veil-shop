<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { SelectInput } from '@/components/form';
import Pagination from '@/components/storefront/Pagination.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CategoryPageProps } from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as productsIndex } from '@/routes/products';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<CategoryPageProps>();

function onSortChange(sort: string) {
    router.get(
        categoryShow.url(props.category.slug),
        { sort },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
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
                class="text-charcoal-600 mt-4 text-sm leading-relaxed font-light sm:text-base"
            >
                {{ category.description }}
            </p>

            <!-- Subcategory pills if available -->
            <div
                v-if="category.children && category.children.length > 0"
                class="mt-8 flex flex-wrap items-center justify-center gap-2"
            >
                <span
                    class="text-charcoal-500 mr-2 text-xs tracking-wider uppercase"
                    >Explore:</span
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

    <!-- Category Products Section -->
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
        <!-- Results count & sorting bar -->
        <div
            class="text-charcoal-600 mb-8 flex items-center justify-between border-b border-cream-200 pb-6 text-xs sm:text-sm"
        >
            <div>
                Showing
                <span class="font-semibold text-charcoal-900">{{
                    products.meta.total
                }}</span>
                {{
                    products.meta.total === 1
                        ? 'botanical product'
                        : 'botanical products'
                }}
            </div>

            <div class="flex items-center gap-2">
                <label
                    for="cat_sort"
                    class="text-charcoal-500 hidden text-xs sm:inline"
                    >Sort by:</label
                >
                <SelectInput
                    id="cat_sort"
                    :model-value="filters?.sort || 'featured'"
                    size="sm"
                    rounded="md"
                    @change="
                        onSortChange(($event.target as HTMLSelectElement).value)
                    "
                >
                    <option value="featured">Featured Botanical</option>
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                </SelectInput>
            </div>
        </div>

        <!-- Product Grid -->
        <div v-if="products.data.length > 0">
            <ProductGrid :products="products.data" />

            <!-- Pagination -->
            <Pagination :links="products.meta.links" />
        </div>

        <!-- Empty State -->
        <div v-else class="mx-auto max-w-md py-20 text-center">
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sage-100 text-sage-700"
            >
                <svg
                    class="h-8 w-8"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                    />
                </svg>
            </div>
            <h2 class="mb-2 font-serif text-xl text-charcoal-900">
                No products found
            </h2>
            <p class="text-charcoal-600 mb-6 text-xs font-light sm:text-sm">
                We currently do not have products listed under this collection.
                Explore our full botanical apothecary for conscious
                alternatives.
            </p>
            <Link
                :href="productsIndex.url()"
                class="inline-block rounded-full bg-sage-800 px-6 py-2.5 text-xs font-medium tracking-wider text-cream-50 uppercase transition-colors hover:bg-sage-900"
            >
                View All Products
            </Link>
        </div>
    </section>
</template>
