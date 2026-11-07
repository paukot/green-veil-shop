<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import FilterSidebar from '@/components/storefront/FilterSidebar.vue';
import Pagination from '@/components/storefront/Pagination.vue';
import BotanicalLoader from '@/components/ui/BotanicalLoader.vue';
import type { CatalogIndexProps } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import { usePageLoading } from '@/composables/usePageLoading';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<CatalogIndexProps>();

const mobileFilterOpen = ref(false);
const { isLoading } = usePageLoading();

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.category) count++;
    if (props.filters.min_price || props.filters.max_price) count++;
    if (props.filters.in_stock) count++;
    if (props.filters.sort && props.filters.sort !== 'featured') count++;
    if (props.filters.q) count++;
    const countFacet = (val?: string) =>
        val ? val.split(',').filter(Boolean).length : 0;
    count += countFacet(props.filters.brands);
    count += countFacet(props.filters.skin_types);
    count += countFacet(props.filters.hair_types);
    count += countFacet(props.filters.effects);
    count += countFacet(props.filters.ingredients);
    count += countFacet(props.filters.claims);
    count += countFacet(props.filters.classifications);
    count += countFacet(props.filters.formula_types);
    count += countFacet(props.filters.genders);
    count += countFacet(props.filters.spf);
    count += countFacet(props.filters.manufactured_in);
    return count;
});

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
                        ? `Showing conscious products matching your botanical search query.`
                        : 'Consciously crafted skin and body care rooted in pure botanical ingredients and restorative rituals.'
                }}
            </p>
        </div>
    </section>

    <!-- Main Catalog Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <!-- Mobile Filters Bar -->
        <div
            class="mb-6 flex items-center justify-between border-b border-cream-200 pb-4 lg:hidden"
        >
            <div class="text-charcoal-600 text-xs">
                <span class="font-semibold text-charcoal-900">{{
                    products.meta.total
                }}</span>
                {{ products.meta.total === 1 ? 'product' : 'products' }}
            </div>
            <button
                type="button"
                @click="mobileFilterOpen = true"
                class="inline-flex items-center gap-1.5 rounded-full border border-cream-300 bg-white px-3 py-1.5 text-xs font-medium text-charcoal-800 shadow-xs hover:border-sage-500"
            >
                <svg
                    class="h-4 w-4 text-sage-700"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"
                    />
                </svg>
                <span>Filters & Sort</span>
                <span
                    v-if="activeFilterCount > 0"
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-sage-700 text-[10px] font-bold text-white"
                >
                    {{ activeFilterCount }}
                </span>
            </button>
        </div>

        <div class="flex items-start gap-8 lg:gap-12">
            <!-- Desktop Sidebar -->
            <div
                class="hidden w-64 shrink-0 rounded-xl border border-cream-200 bg-white p-6 shadow-xs lg:block"
            >
                <FilterSidebar
                    :categories="categories"
                    :filters="filters"
                    :facets="facets"
                    :price-range="priceRange"
                    :base-url="productsIndex.url()"
                />
            </div>

            <!-- Product Listing Area -->
            <div class="relative min-w-0 flex-1">
                <!-- Loading State Overlay (rotating thingy and funny dots) -->
                <Transition
                    enter-active-class="transition-opacity duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="isLoading"
                        class="absolute inset-0 z-30 flex flex-col items-center pt-6 rounded-2xl bg-cream-50/75 backdrop-blur-[2px]"
                        aria-live="polite"
                        aria-busy="true"
                    >
                        <div
                            class="sticky top-28 z-40 flex flex-col items-center gap-3 rounded-2xl border border-cream-200/90 bg-white/95 px-8 py-6 shadow-xl backdrop-blur-md"
                        >
                            <BotanicalLoader
                                text="Curating botanical formulas..."
                                size="md"
                            />
                        </div>
                    </div>
                </Transition>

                <!-- Top Count (Desktop) -->
                <div
                    class="text-charcoal-600 mb-6 hidden items-center justify-between border-b border-cream-200 pb-4 text-xs lg:flex"
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
                </div>

                <!-- Products Grid -->
                <div v-if="products.data.length > 0">
                    <ProductGrid :products="products.data" />

                    <!-- Pagination -->
                    <Pagination :links="products.meta.links" />
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="mx-auto max-w-md rounded-2xl border border-cream-200 bg-white p-8 py-20 text-center shadow-xs"
                >
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
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                            />
                        </svg>
                    </div>
                    <h2 class="mb-2 font-serif text-xl text-charcoal-900">
                        No matching botanical
                    </h2>
                    <p
                        class="text-charcoal-600 mb-6 text-xs font-light sm:text-sm"
                    >
                        We couldn't find any products matching your selected
                        criteria. Try adjusting your filters or search keywords.
                    </p>
                    <Link
                        :href="productsIndex.url()"
                        class="inline-block rounded-full bg-sage-800 px-6 py-2.5 text-xs font-medium tracking-wider text-cream-50 uppercase transition-colors hover:bg-sage-900"
                    >
                        Reset All Filters
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Filter Drawer -->
    <div
        v-if="mobileFilterOpen"
        class="fixed inset-0 z-50 flex justify-end bg-charcoal-900/50 backdrop-blur-xs lg:hidden"
        @click.self="mobileFilterOpen = false"
    >
        <div
            class="flex h-full w-full max-w-sm flex-col justify-between overflow-y-auto bg-white p-6"
        >
            <div>
                <div
                    class="mb-6 flex items-center justify-between border-b border-cream-200 pb-4"
                >
                    <div class="font-serif text-lg text-charcoal-900">
                        Filter & Sort
                    </div>
                    <button
                        type="button"
                        @click="mobileFilterOpen = false"
                        class="text-charcoal-400 p-1 text-xl leading-none hover:text-charcoal-800"
                        aria-label="Close filters"
                    >
                        &times;
                    </button>
                </div>

                <FilterSidebar
                    :categories="categories"
                    :filters="filters"
                    :facets="facets"
                    :price-range="priceRange"
                    :base-url="productsIndex.url()"
                    @close="mobileFilterOpen = false"
                />
            </div>
        </div>
    </div>
</template>
