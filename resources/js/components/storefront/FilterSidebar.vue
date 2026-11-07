<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioInput,
    SelectInput,
    TextInput,
} from '@/components/form';
import FilterSection from '@/components/storefront/filters/FilterSection.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import { usePageLoading } from '@/composables/usePageLoading';
import { index as productsIndex } from '@/routes/products';
import type {
    CatalogFilters,
    CategoryFilterItem,
    FilterFacets,
} from '@/types/storefront';

const { isLoading } = usePageLoading();

const props = withDefaults(
    defineProps<{
        categories?: CategoryFilterItem[];
        filters: CatalogFilters;
        facets?: FilterFacets;
        priceRange?: {
            min: number;
            max: number;
        };
        baseUrl?: string;
        showCategories?: boolean;
    }>(),
    {
        categories: () => [],
        facets: undefined,
        priceRange: () => ({ min: 0, max: 100 }),
        baseUrl: () => productsIndex.url(),
        showCategories: true,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

// Helper to parse comma-separated string to array
function parseList(val?: string | null): string[] {
    if (!val) return [];
    return val
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);
}

// Local reactive filter states
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

// Multi-select facet arrays
const selectedBrands = ref<string[]>(parseList(props.filters.brands));
const selectedSkinTypes = ref<string[]>(parseList(props.filters.skin_types));
const selectedHairTypes = ref<string[]>(parseList(props.filters.hair_types));
const selectedEffects = ref<string[]>(parseList(props.filters.effects));
const selectedIngredients = ref<string[]>(parseList(props.filters.ingredients));
const selectedClaims = ref<string[]>(parseList(props.filters.claims));
const selectedClassifications = ref<string[]>(
    parseList(props.filters.classifications),
);
const selectedFormulaTypes = ref<string[]>(
    parseList(props.filters.formula_types),
);
const selectedGenders = ref<string[]>(parseList(props.filters.genders));
const selectedSpf = ref<string[]>(parseList(props.filters.spf));
const selectedOrigins = ref<string[]>(parseList(props.filters.manufactured_in));

// Accordion open/close state
const openSections = ref<Record<string, boolean>>({
    sort: true,
    categories: true,
    brands: selectedBrands.value.length > 0 || true,
    price: Boolean(props.filters.min_price || props.filters.max_price),
    skin_types: selectedSkinTypes.value.length > 0 || true,
    effects: selectedEffects.value.length > 0 || true,
    ingredients: selectedIngredients.value.length > 0,
    claims: selectedClaims.value.length > 0,
    formula_types: selectedFormulaTypes.value.length > 0,
    spf: selectedSpf.value.length > 0,
    hair_types: selectedHairTypes.value.length > 0,
    classifications: selectedClassifications.value.length > 0,
    genders: selectedGenders.value.length > 0,
    origins: selectedOrigins.value.length > 0,
    stock: true,
});

function toggleSection(section: string) {
    openSections.value[section] = !openSections.value[section];
}

// Watch props.filters changes to keep internal state aligned
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

        selectedBrands.value = parseList(newFilters.brands);
        selectedSkinTypes.value = parseList(newFilters.skin_types);
        selectedHairTypes.value = parseList(newFilters.hair_types);
        selectedEffects.value = parseList(newFilters.effects);
        selectedIngredients.value = parseList(newFilters.ingredients);
        selectedClaims.value = parseList(newFilters.claims);
        selectedClassifications.value = parseList(newFilters.classifications);
        selectedFormulaTypes.value = parseList(newFilters.formula_types);
        selectedGenders.value = parseList(newFilters.genders);
        selectedSpf.value = parseList(newFilters.spf);
        selectedOrigins.value = parseList(newFilters.manufactured_in);
    },
    { deep: true },
);

// Toggle selection helper
function toggleFacetItem(list: string[], val: string) {
    const idx = list.indexOf(val);
    if (idx > -1) {
        list.splice(idx, 1);
    } else {
        list.push(val);
    }
}

// Build URL query params and navigate
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

    const brands =
        overrides.brands !== undefined
            ? overrides.brands
            : selectedBrands.value.join(',');
    if (brands) params.brands = brands;

    const skinTypes =
        overrides.skin_types !== undefined
            ? overrides.skin_types
            : selectedSkinTypes.value.join(',');
    if (skinTypes) params.skin_types = skinTypes;

    const hairTypes =
        overrides.hair_types !== undefined
            ? overrides.hair_types
            : selectedHairTypes.value.join(',');
    if (hairTypes) params.hair_types = hairTypes;

    const effects =
        overrides.effects !== undefined
            ? overrides.effects
            : selectedEffects.value.join(',');
    if (effects) params.effects = effects;

    const ingredients =
        overrides.ingredients !== undefined
            ? overrides.ingredients
            : selectedIngredients.value.join(',');
    if (ingredients) params.ingredients = ingredients;

    const claims =
        overrides.claims !== undefined
            ? overrides.claims
            : selectedClaims.value.join(',');
    if (claims) params.claims = claims;

    const classifications =
        overrides.classifications !== undefined
            ? overrides.classifications
            : selectedClassifications.value.join(',');
    if (classifications) params.classifications = classifications;

    const formulaTypes =
        overrides.formula_types !== undefined
            ? overrides.formula_types
            : selectedFormulaTypes.value.join(',');
    if (formulaTypes) params.formula_types = formulaTypes;

    const genders =
        overrides.genders !== undefined
            ? overrides.genders
            : selectedGenders.value.join(',');
    if (genders) params.genders = genders;

    const spf =
        overrides.spf !== undefined
            ? overrides.spf
            : selectedSpf.value.join(',');
    if (spf) params.spf = spf;

    const origins =
        overrides.manufactured_in !== undefined
            ? overrides.manufactured_in
            : selectedOrigins.value.join(',');
    if (origins) params.manufactured_in = origins;

    const min =
        overrides.min_price !== undefined
            ? overrides.min_price
            : minPrice.value.trim();
    if (min !== '') params.min_price = Number(min);

    const max =
        overrides.max_price !== undefined
            ? overrides.max_price
            : maxPrice.value.trim();
    if (max !== '') params.max_price = Number(max);

    const stock =
        overrides.in_stock !== undefined ? overrides.in_stock : inStock.value;
    if (stock) params.in_stock = true;

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
    selectedBrands.value = [];
    selectedSkinTypes.value = [];
    selectedHairTypes.value = [];
    selectedEffects.value = [];
    selectedIngredients.value = [];
    selectedClaims.value = [];
    selectedClassifications.value = [];
    selectedFormulaTypes.value = [];
    selectedGenders.value = [];
    selectedSpf.value = [];
    selectedOrigins.value = [];

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

// Total active filter chips count
const totalActiveFilters = computed(() => {
    let count = 0;
    if (selectedCategory.value) count++;
    if (minPrice.value || maxPrice.value) count++;
    if (inStock.value) count++;
    if (selectedSort.value && selectedSort.value !== 'featured') count++;
    if (searchQuery.value) count++;
    count += selectedBrands.value.length;
    count += selectedSkinTypes.value.length;
    count += selectedHairTypes.value.length;
    count += selectedEffects.value.length;
    count += selectedIngredients.value.length;
    count += selectedClaims.value.length;
    count += selectedClassifications.value.length;
    count += selectedFormulaTypes.value.length;
    count += selectedGenders.value.length;
    count += selectedSpf.value.length;
    count += selectedOrigins.value.length;
    return count;
});
</script>

<template>
    <aside class="space-y-4 text-sm" aria-label="Filters and Sorting">
        <!-- Active Filter Summary & Clear All -->
        <div
            v-if="totalActiveFilters > 0"
            class="flex items-center justify-between border-b border-cream-200 pb-3"
        >
            <span
                class="text-xs font-semibold tracking-wider text-sage-800 uppercase"
            >
                Active Filters ({{ totalActiveFilters }})
            </span>
            <button
                type="button"
                @click="clearAllFilters"
                class="text-xs font-medium text-sage-700 underline underline-offset-2 hover:text-sage-900"
            >
                Clear all
            </button>
        </div>

        <!-- Active Search Tag -->
        <div
            v-if="searchQuery"
            class="flex items-center justify-between rounded-lg border border-sage-200 bg-sage-50 p-2.5"
        >
            <div class="truncate text-xs">
                <span class="font-medium text-sage-700">Search:</span>
                <span class="ml-1 font-semibold text-charcoal-900"
                    >"{{ searchQuery }}"</span
                >
            </div>
            <button
                type="button"
                @click="applyFilters({ q: '' })"
                class="ml-2 text-xs font-bold text-sage-700 hover:text-sage-900 focus:outline-none"
                aria-label="Remove search filter"
            >
                &times;
            </button>
        </div>

        <!-- Apply Filters Button (below active filters) -->
        <div class="border-b border-cream-200 pb-4">
            <button
                type="button"
                :disabled="isLoading"
                @click="applyFilters()"
                class="active:bg-sage-950 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-sage-800 px-4 py-2.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-75"
            >
                <LoadingSpinner v-if="isLoading" size="sm" class="text-white" />
                <svg
                    v-else
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"
                    />
                </svg>
                <span class="inline-flex items-center gap-1">
                    <span>{{ isLoading ? 'Applying' : 'Apply Filters' }}</span>
                    <span
                        v-if="isLoading"
                        class="ml-0.5 inline-flex items-center gap-0.5"
                    >
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: -0.32s"
                        />
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: -0.16s"
                        />
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: 0s"
                        />
                    </span>
                </span>
                <span
                    v-if="!isLoading && totalActiveFilters > 0"
                    class="ml-1 rounded-full bg-sage-700 px-1.5 py-0.5 text-[10px] font-bold text-white"
                >
                    {{ totalActiveFilters }}
                </span>
            </button>
        </div>

        <!-- Sort Section Accordion -->
        <div class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('sort')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span>Sort By</span>
                <span class="text-charcoal-400 text-xs">
                    {{ openSections.sort ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.sort" class="mt-2.5">
                <SelectInput v-model="selectedSort" size="sm" rounded="md">
                    <option value="featured">Featured Botanicals</option>
                    <option value="newest">Newest Additions</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                    <option value="name_desc">Name: Z to A</option>
                </SelectInput>
            </div>
        </div>

        <!-- Category Filter Section (shown on general catalog) -->
        <div
            v-if="showCategories && categories.length > 0"
            class="border-b border-cream-200 pb-4"
        >
            <button
                type="button"
                @click="toggleSection('categories')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span>Categories</span>
                <span class="text-charcoal-400 text-xs">
                    {{ openSections.categories ? '−' : '+' }}
                </span>
            </button>
            <div
                v-show="openSections.categories"
                class="mt-2.5 max-h-52 space-y-1.5 overflow-y-auto pr-1 lg:max-h-50"
            >
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
                            @change="selectedCategory = ''"
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
                            @change="selectedCategory = cat.slug"
                        />
                        <span>{{ cat.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]"
                        >({{ cat.products_count }})</span
                    >
                </label>
            </div>
        </div>

        <!-- Brands Filter Accordion -->
        <FilterSection
            title="Brands"
            :options="facets?.brands"
            :selected="selectedBrands"
            v-model:is-open="openSections.brands"
            search-placeholder="Search brand..."
            @toggle="toggleFacetItem(selectedBrands, $event)"
        />

        <!-- Skin Types Filter Accordion -->
        <FilterSection
            title="Skin Types"
            :options="facets?.skin_types"
            :selected="selectedSkinTypes"
            v-model:is-open="openSections.skin_types"
            search-placeholder="Search skin type..."
            @toggle="toggleFacetItem(selectedSkinTypes, $event)"
        />

        <!-- Effects / Actions Filter Accordion -->
        <FilterSection
            title="Effects & Actions"
            :options="facets?.effects"
            :selected="selectedEffects"
            v-model:is-open="openSections.effects"
            search-placeholder="Search effect..."
            @toggle="toggleFacetItem(selectedEffects, $event)"
        />

        <!-- Key Ingredients Filter Accordion -->
        <FilterSection
            title="Key Ingredients"
            :options="facets?.ingredients"
            :selected="selectedIngredients"
            v-model:is-open="openSections.ingredients"
            search-placeholder="Search ingredient..."
            @toggle="toggleFacetItem(selectedIngredients, $event)"
        />

        <!-- Hair Types Filter Accordion -->
        <FilterSection
            title="Hair Types"
            :options="facets?.hair_types"
            :selected="selectedHairTypes"
            v-model:is-open="openSections.hair_types"
            search-placeholder="Search hair type..."
            @toggle="toggleFacetItem(selectedHairTypes, $event)"
        />

        <!-- Clean Beauty Claims Filter Accordion -->
        <FilterSection
            title="Clean Beauty Claims"
            :options="facets?.claims"
            :selected="selectedClaims"
            v-model:is-open="openSections.claims"
            search-placeholder="Search claims..."
            @toggle="toggleFacetItem(selectedClaims, $event)"
        />

        <!-- Formula / Texture Filter Accordion -->
        <FilterSection
            title="Formula & Texture"
            :options="facets?.formula_types"
            :selected="selectedFormulaTypes"
            v-model:is-open="openSections.formula_types"
            search-placeholder="Search texture..."
            @toggle="toggleFacetItem(selectedFormulaTypes, $event)"
        />

        <!-- SPF Filter Accordion -->
        <FilterSection
            title="SPF Protection"
            :options="facets?.spf"
            :selected="selectedSpf"
            v-model:is-open="openSections.spf"
            @toggle="toggleFacetItem(selectedSpf, $event)"
        />

        <!-- Classification Filter Accordion -->
        <FilterSection
            title="Cosmetic Classification"
            :options="facets?.classifications"
            :selected="selectedClassifications"
            v-model:is-open="openSections.classifications"
            @toggle="toggleFacetItem(selectedClassifications, $event)"
        />

        <!-- Country of Origin Filter Accordion -->
        <FilterSection
            title="Country of Origin"
            :options="facets?.origins"
            :selected="selectedOrigins"
            v-model:is-open="openSections.origins"
            search-placeholder="Search origin..."
            @toggle="toggleFacetItem(selectedOrigins, $event)"
        />

        <!-- Price Range Filter Accordion -->
        <div class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('price')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span>Price Range (€)</span>
                <span class="text-charcoal-400 text-xs">
                    {{ openSections.price ? '−' : '+' }}
                </span>
            </button>
            <div
                v-show="openSections.price"
                class="mt-2.5 flex items-center gap-2"
            >
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
                        @keydown.enter="applyFilters()"
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
                        @keydown.enter="applyFilters()"
                    />
                </div>
            </div>
        </div>

        <!-- Availability Filter Section -->
        <div class="border-b border-cream-200 pb-4">
            <CheckboxField
                id="filter-in-stock"
                v-model="inStock"
                label="In Stock Only"
            />
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2 pt-2">
            <button
                type="button"
                :disabled="isLoading"
                @click="applyFilters()"
                class="active:bg-sage-950 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-sage-800 px-4 py-2.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-75"
            >
                <LoadingSpinner v-if="isLoading" size="sm" class="text-white" />
                <svg
                    v-else
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"
                    />
                </svg>
                <span class="inline-flex items-center gap-1">
                    <span>{{ isLoading ? 'Applying' : 'Apply Filters' }}</span>
                    <span
                        v-if="isLoading"
                        class="ml-0.5 inline-flex items-center gap-0.5"
                    >
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: -0.32s"
                        />
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: -0.16s"
                        />
                        <span
                            class="inline-block h-1 w-1 animate-bounce rounded-full bg-cream-100"
                            style="animation-delay: 0s"
                        />
                    </span>
                </span>
                <span
                    v-if="!isLoading && totalActiveFilters > 0"
                    class="ml-1 rounded-full bg-sage-700 px-1.5 py-0.5 text-[10px] font-bold text-white"
                >
                    {{ totalActiveFilters }}
                </span>
            </button>

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
