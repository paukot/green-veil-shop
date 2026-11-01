<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioInput,
    SelectInput,
    TextInput,
} from '@/components/form';
import type { CatalogFilters, CategoryFilterItem } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';

const props = withDefaults(
    defineProps<{
        categories?: CategoryFilterItem[];
        filters: CatalogFilters;
        priceRange?: {
            min: number;
            max: number;
        };
        baseUrl?: string;
        showCategories?: boolean;
    }>(),
    {
        categories: () => [],
        priceRange: () => ({ min: 0, max: 100 }),
        baseUrl: () => productsIndex.url(),
        showCategories: true,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const selectedCategory = ref<string>(props.filters.category || '');
const minPrice = ref<string>(
    props.filters.min_price !== null && props.filters.min_price !== undefined
        ? String(props.filters.min_price)
        : '',
);
const maxPrice = ref<string>(
    props.filters.max_price !== null && props.filters.max_price !== undefined
        ? String(props.filters.max_price)
        : '',
);
const inStock = ref<boolean>(Boolean(props.filters.in_stock));
const selectedSort = ref<string>(props.filters.sort || 'featured');
const searchQuery = ref<string>(props.filters.q || '');

watch(
    () => props.filters,
    (newFilters) => {
        selectedCategory.value = newFilters.category || '';
        minPrice.value =
            newFilters.min_price !== null && newFilters.min_price !== undefined
                ? String(newFilters.min_price)
                : '';
        maxPrice.value =
            newFilters.max_price !== null && newFilters.max_price !== undefined
                ? String(newFilters.max_price)
                : '';
        inStock.value = Boolean(newFilters.in_stock);
        selectedSort.value = newFilters.sort || 'featured';
        searchQuery.value = newFilters.q || '';
    },
    { deep: true },
);

function applyFilters(overrides: Partial<CatalogFilters> = {}) {
    const params: Record<string, string | number | boolean> = {};

    const q =
        overrides.q !== undefined ? overrides.q : searchQuery.value.trim();
    if (q) params.q = q;

    const cat =
        overrides.category !== undefined
            ? overrides.category
            : selectedCategory.value;
    if (cat) params.category = cat;

    const min =
        overrides.min_price !== undefined
            ? overrides.min_price
            : minPrice.value;
    if (min !== '' && min !== null && min !== undefined)
        params.min_price = Number(min);

    const max =
        overrides.max_price !== undefined
            ? overrides.max_price
            : maxPrice.value;
    if (max !== '' && max !== null && max !== undefined)
        params.max_price = Number(max);

    const stock =
        overrides.in_stock !== undefined ? overrides.in_stock : inStock.value;
    if (stock) params.in_stock = 1;

    const sort =
        overrides.sort !== undefined ? overrides.sort : selectedSort.value;
    if (sort && sort !== 'featured') params.sort = sort;

    router.get(props.baseUrl, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => emit('close'),
    });
}

function clearAllFilters() {
    selectedCategory.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    inStock.value = false;
    selectedSort.value = 'featured';
    searchQuery.value = '';

    router.get(
        props.baseUrl,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => emit('close'),
        },
    );
}

function removeSearchFilter() {
    searchQuery.value = '';
    applyFilters({ q: '' });
}
</script>

<template>
    <aside class="space-y-6 text-sm" aria-label="Filters and Sorting">
        <!-- Active Search Tag -->
        <div
            v-if="searchQuery"
            class="flex items-center justify-between rounded-lg border border-sage-200 bg-sage-50 p-3"
        >
            <div class="truncate text-xs">
                <span class="font-medium text-sage-700">Search:</span>
                <span class="ml-1 font-semibold text-charcoal-900"
                    >"{{ searchQuery }}"</span
                >
            </div>
            <button
                type="button"
                @click="removeSearchFilter"
                class="ml-2 text-xs font-bold text-sage-700 hover:text-sage-900 focus:outline-none"
                aria-label="Remove search query"
            >
                &times;
            </button>
        </div>

        <!-- Sort Section -->
        <div class="border-b border-cream-200 pb-5">
            <h3
                class="mb-3 text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
            >
                Sort By
            </h3>
            <SelectInput
                v-model="selectedSort"
                size="sm"
                rounded="md"
                @change="applyFilters()"
            >
                <option value="featured">Featured Botanicals</option>
                <option value="newest">Newest Additions</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
            </SelectInput>
        </div>

        <!-- Category Filter Section (shown on general catalog) -->
        <div
            v-if="showCategories && categories.length > 0"
            class="border-b border-cream-200 pb-5"
        >
            <h3
                class="mb-3 text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
            >
                Categories
            </h3>
            <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                <label
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{
                        'font-semibold text-sage-900': !selectedCategory,
                    }"
                >
                    <span class="flex items-center gap-2">
                        <RadioInput
                            name="category_filter"
                            value=""
                            :checked="!selectedCategory"
                            @change="
                                selectedCategory = '';
                                applyFilters({ category: '' });
                            "
                        />
                        <span>All Categories</span>
                    </span>
                </label>

                <label
                    v-for="cat in categories"
                    :key="cat.id"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{
                        'font-semibold text-sage-900':
                            selectedCategory === cat.slug,
                    }"
                >
                    <span class="flex items-center gap-2">
                        <RadioInput
                            name="category_filter"
                            :value="cat.slug"
                            :checked="selectedCategory === cat.slug"
                            @change="
                                selectedCategory = cat.slug;
                                applyFilters({ category: cat.slug });
                            "
                        />
                        <span>{{ cat.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]"
                        >({{ cat.products_count }})</span
                    >
                </label>
            </div>
        </div>

        <!-- Price Range Filter Section -->
        <div class="border-b border-cream-200 pb-5">
            <h3
                class="mb-3 text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
            >
                Price Range (€)
            </h3>
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <TextInput
                        v-model="minPrice"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="Min"
                        prefix-text="€"
                        size="sm"
                        rounded="md"
                    />
                </div>
                <span class="text-charcoal-400 text-xs">&ndash;</span>
                <div class="flex-1">
                    <TextInput
                        v-model="maxPrice"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="Max"
                        prefix-text="€"
                        size="sm"
                        rounded="md"
                    />
                </div>
                <button
                    type="button"
                    @click="applyFilters()"
                    class="rounded-md bg-cream-200 px-2.5 py-1.5 text-xs font-medium text-charcoal-700 transition-colors hover:bg-sage-600 hover:text-white"
                    aria-label="Apply price filter"
                >
                    Go
                </button>
            </div>
        </div>

        <!-- Availability Filter Section -->
        <div class="border-b border-cream-200 pb-5">
            <CheckboxField
                id="filter-in-stock"
                v-model="inStock"
                label="In Stock Only"
                @change="applyFilters()"
            />
        </div>

        <!-- Reset Button -->
        <div>
            <button
                type="button"
                @click="clearAllFilters"
                class="text-charcoal-600 w-full rounded-md border border-cream-300 px-3 py-2 text-xs font-medium transition-colors hover:border-sage-500 hover:text-sage-800"
            >
                Reset All Filters
            </button>
        </div>
    </aside>
</template>
