<script setup lang="ts">
import FilterSidebar from '@/components/storefront/FilterSidebar.vue';
import Pagination from '@/components/storefront/Pagination.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import ProductGridSkeleton from '@/components/storefront/skeletons/ProductGridSkeleton.vue';
import ActiveFilterChips from '@/components/storefront/filters/ActiveFilterChips.vue';
import { useCatalogFilters } from '@/composables/useCatalogFilters';
import { usePageLoading } from '@/composables/usePageLoading';
import type {
    CatalogFilters,
    CategoryFilterItem,
    FilterFacets,
    PaginatedData,
    ProductCardData,
} from '@/types/storefront';
import { SearchIcon, SlidersHorizontalIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';
import Drawer from '@/components/ui/Drawer.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

const props = withDefaults(
    defineProps<{
        products: PaginatedData<ProductCardData>;
        filters: CatalogFilters;
        categories?: CategoryFilterItem[];
        facets?: FilterFacets;
        priceRange?: {
            min: number;
            max: number;
        };
        baseUrl: string;
        showCategoriesInSidebar?: boolean;
        resetUrl?: string;
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        categories: () => [],
        facets: undefined,
        priceRange: () => ({ min: 0, max: 100 }),
        showCategoriesInSidebar: true,
        resetUrl: undefined,
        emptyTitle: 'No matching botanical products',
        emptyDescription:
            "We couldn't find any products matching your selected criteria. Try adjusting your filters or search keywords.",
    },
);

const { isLoading } = usePageLoading();
const { mobileFilterOpen, activeFilterCount } = useCatalogFilters(
    () => props.filters,
);
</script>

<template>
    <Container class="py-8 lg:py-12">
        <div class="flex items-start gap-12">
            <!-- Desktop filters -->
            <aside
                class="sticky top-sticky scrollbar-none hidden max-h-[80vh] w-64 shrink-0 overflow-y-auto pr-4 lg:block"
                aria-label="Filters"
            >
                <FilterSidebar
                    :categories="categories"
                    :filters="filters"
                    :facets="facets"
                    :price-range="priceRange"
                    :base-url="baseUrl"
                    :show-categories="showCategoriesInSidebar"
                />
            </aside>

            <div
                id="catalog-results"
                class="flex min-w-0 flex-1 scroll-mt-24 flex-col gap-6 lg:scroll-mt-sticky"
            >
                <!-- Toolbar -->
                <div
                    class="flex items-center justify-between gap-4 border-b border-border pb-4"
                >
                    <p class="text-sm text-ink-muted">
                        <span class="font-semibold text-ink">{{
                            products.meta.total
                        }}</span>
                        {{ products.meta.total === 1 ? 'product' : 'products' }}
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        class="lg:hidden"
                        @click="mobileFilterOpen = true"
                    >
                        <template #icon>
                            <SlidersHorizontalIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </template>
                        Filter & sort
                        <template v-if="activeFilterCount > 0" #trailing>
                            <span
                                class="flex size-4 items-center justify-center rounded-full bg-sage-900 text-xs leading-4 text-cream-50"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </template>
                    </Button>
                </div>

                <ActiveFilterChips
                    :filters="filters"
                    :facets="facets"
                    :base-url="baseUrl"
                    :reset-url="resetUrl"
                />

                <ProductGridSkeleton
                    v-if="isLoading"
                    :count="products.data.length || 12"
                />
                <div
                    v-else-if="products.data.length > 0"
                    class="flex flex-col gap-12"
                >
                    <ProductGrid :products="products.data" />
                    <Pagination
                        :links="products.meta.links"
                        :only="['products', 'filters']"
                        scroll-target="catalog-results"
                    />
                </div>
                <EmptyState
                    v-else
                    :title="emptyTitle"
                    :description="emptyDescription"
                >
                    <template #icon>
                        <SearchIcon aria-hidden="true" />
                    </template>
                    <template #actions>
                        <Button :href="resetUrl || baseUrl"
                            >Reset filters</Button
                        >
                    </template>
                </EmptyState>
            </div>
        </div>
    </Container>

    <Drawer
        :open="mobileFilterOpen"
        title="Filter & sort"
        @close="mobileFilterOpen = false"
    >
        <div class="p-6">
            <FilterSidebar
                :categories="categories"
                :filters="filters"
                :facets="facets"
                :price-range="priceRange"
                :base-url="baseUrl"
                :show-categories="showCategoriesInSidebar"
                @close="mobileFilterOpen = false"
            />
        </div>
    </Drawer>
</template>
