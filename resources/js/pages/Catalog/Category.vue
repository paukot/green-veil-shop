<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import Pagination from '@/components/storefront/Pagination.vue';
import type { CategoryPageProps } from '@/types/storefront';

const props = defineProps<CategoryPageProps>();

function onSortChange(sort: string) {
    router.get(
        `/categories/${props.category.slug}`,
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

    <StorefrontLayout>
        <!-- Breadcrumbs Navigation -->
        <nav
            aria-label="Breadcrumb"
            class="border-b border-cream-200 bg-cream-100/60"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <ol
                    class="text-charcoal-600 flex items-center space-x-2 text-xs"
                >
                    <li
                        v-for="(crumb, idx) in breadcrumbs"
                        :key="crumb.url"
                        class="flex items-center"
                    >
                        <Link
                            v-if="idx < breadcrumbs.length - 1"
                            :href="crumb.url"
                            class="transition-colors hover:text-sage-800"
                        >
                            {{ crumb.name }}
                        </Link>
                        <span
                            v-else
                            class="font-semibold text-charcoal-900"
                            aria-current="page"
                        >
                            {{ crumb.name }}
                        </span>
                        <svg
                            v-if="idx < breadcrumbs.length - 1"
                            class="text-charcoal-400 mx-2 h-3 w-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </li>
                </ol>
            </div>
        </nav>

        <!-- Category Header Banner -->
        <section
            class="border-b border-cream-200 bg-gradient-to-b from-cream-100 to-cream-50 py-10 sm:py-14"
        >
            <div
                class="mx-auto max-w-3xl max-w-7xl px-4 text-center sm:px-6 lg:px-8"
            >
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
                        :href="`/categories/${child.slug}`"
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
                        products.total
                    }}</span>
                    {{
                        products.total === 1
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
                    <select
                        id="cat_sort"
                        :value="filters?.sort || 'featured'"
                        @change="
                            onSortChange(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                        class="rounded-md border border-cream-300 bg-white px-3 py-1.5 text-xs text-charcoal-800 focus:ring-1 focus:ring-sage-500 focus:outline-none"
                    >
                        <option value="featured">Featured Botanicals</option>
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A to Z</option>
                    </select>
                </div>
            </div>

            <!-- Product Grid -->
            <div v-if="products.data.length > 0">
                <ProductGrid :products="products.data" />

                <!-- Pagination -->
                <Pagination :links="products.links" />
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
                    We currently do not have products listed under this
                    collection. Explore our full botanical apothecary for
                    conscious alternatives.
                </p>
                <Link
                    href="/products"
                    class="inline-block rounded-full bg-sage-800 px-6 py-2.5 text-xs font-medium tracking-wider text-cream-50 uppercase transition-colors hover:bg-sage-900"
                >
                    View All Products
                </Link>
            </div>
        </section>
    </StorefrontLayout>
</template>
