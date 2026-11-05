<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioInput,
    SelectInput,
    TextInput,
} from '@/components/form';
import type {
    CatalogFilters,
    CategoryFilterItem,
    FilterFacets,
    FacetOption,
} from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';

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

// Search queries inside facet lists
const brandSearch = ref('');
const ingredientSearch = ref('');

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
    applyFilters();
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

// Filtered lists for searching inside facets
const filteredBrands = computed(() => {
    if (!props.facets?.brands) return [];
    if (!brandSearch.value.trim()) return props.facets.brands;
    const q = brandSearch.value.toLowerCase();
    return props.facets.brands.filter((b) =>
        (b.name || b.value || '').toLowerCase().includes(q),
    );
});

const filteredIngredients = computed(() => {
    if (!props.facets?.ingredients) return [];
    if (!ingredientSearch.value.trim()) return props.facets.ingredients;
    const q = ingredientSearch.value.toLowerCase();
    return props.facets.ingredients.filter((i) =>
        (i.name || i.value || '').toLowerCase().includes(q),
    );
});

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
            <span class="text-xs font-semibold tracking-wider text-sage-800 uppercase">
                Active Filters ({{ totalActiveFilters }})
            </span>
            <button
                type="button"
                @click="clearAllFilters"
                class="text-xs font-medium text-sage-700 hover:text-sage-900 underline underline-offset-2"
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
                <span class="ml-1 font-semibold text-charcoal-900">"{{ searchQuery }}"</span>
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

        <!-- Sort Section Accordion -->
        <div class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('sort')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span>Sort By</span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.sort ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.sort" class="mt-2.5">
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
                <span class="text-xs text-charcoal-400">
                    {{ openSections.categories ? '−' : '+' }}
                </span>
            </button>
            <div
                v-show="openSections.categories"
                class="mt-2.5 max-h-52 space-y-1.5 overflow-y-auto pr-1"
            >
                <label
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': !selectedCategory }"
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
                    :class="{ 'font-semibold text-sage-900': selectedCategory === cat.slug }"
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
                    <span class="text-charcoal-400 text-[11px]">({{ cat.products_count }})</span>
                </label>
            </div>
        </div>

        <!-- Brands Filter Accordion -->
        <div v-if="facets?.brands && facets.brands.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('brands')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Brands</span>
                    <span
                        v-if="selectedBrands.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedBrands.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.brands ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.brands" class="mt-2.5 space-y-2">
                <input
                    v-if="facets.brands.length > 6"
                    v-model="brandSearch"
                    type="text"
                    placeholder="Search brand..."
                    class="w-full rounded-md border border-cream-300 bg-cream-50 px-2.5 py-1 text-xs focus:border-sage-600 focus:bg-white focus:outline-none"
                />
                <div class="max-h-48 space-y-1.5 overflow-y-auto pr-1">
                    <label
                        v-for="brand in filteredBrands"
                        :key="brand.id || brand.slug"
                        class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                        :class="{ 'font-semibold text-sage-900': selectedBrands.includes(brand.slug!) }"
                    >
                        <span class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :value="brand.slug"
                                :checked="selectedBrands.includes(brand.slug!)"
                                @change="toggleFacetItem(selectedBrands, brand.slug!)"
                                class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                            />
                            <span>{{ brand.name }}</span>
                        </span>
                        <span class="text-charcoal-400 text-[11px]">
                            ({{ brand.products_count ?? brand.count ?? 0 }})
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Skin Types Filter Accordion -->
        <div v-if="facets?.skin_types && facets.skin_types.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('skin_types')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Skin Types</span>
                    <span
                        v-if="selectedSkinTypes.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedSkinTypes.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.skin_types ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.skin_types" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="st in facets.skin_types"
                    :key="st.id || st.slug"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedSkinTypes.includes(st.slug!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="st.slug"
                            :checked="selectedSkinTypes.includes(st.slug!)"
                            @change="toggleFacetItem(selectedSkinTypes, st.slug!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ st.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ st.products_count ?? st.count ?? 0 }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Effects / Actions Filter Accordion -->
        <div v-if="facets?.effects && facets.effects.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('effects')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Effects & Action</span>
                    <span
                        v-if="selectedEffects.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedEffects.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.effects ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.effects" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="ef in facets.effects"
                    :key="ef.id || ef.slug"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedEffects.includes(ef.slug!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="ef.slug"
                            :checked="selectedEffects.includes(ef.slug!)"
                            @change="toggleFacetItem(selectedEffects, ef.slug!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ ef.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ ef.products_count ?? ef.count ?? 0 }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Key Ingredients Filter Accordion -->
        <div v-if="facets?.ingredients && facets.ingredients.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('ingredients')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Active Ingredients</span>
                    <span
                        v-if="selectedIngredients.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedIngredients.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.ingredients ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.ingredients" class="mt-2.5 space-y-2">
                <input
                    v-if="facets.ingredients.length > 6"
                    v-model="ingredientSearch"
                    type="text"
                    placeholder="Search ingredient..."
                    class="w-full rounded-md border border-cream-300 bg-cream-50 px-2.5 py-1 text-xs focus:border-sage-600 focus:bg-white focus:outline-none"
                />
                <div class="max-h-48 space-y-1.5 overflow-y-auto pr-1">
                    <label
                        v-for="ing in filteredIngredients"
                        :key="ing.id || ing.slug"
                        class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                        :class="{ 'font-semibold text-sage-900': selectedIngredients.includes(ing.slug!) }"
                    >
                        <span class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :value="ing.slug"
                                :checked="selectedIngredients.includes(ing.slug!)"
                                @change="toggleFacetItem(selectedIngredients, ing.slug!)"
                                class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                            />
                            <span>{{ ing.name }}</span>
                        </span>
                        <span class="text-charcoal-400 text-[11px]">
                            ({{ ing.products_count ?? ing.count ?? 0 }})
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Hair Types Filter Accordion -->
        <div v-if="facets?.hair_types && facets.hair_types.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('hair_types')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Hair Types</span>
                    <span
                        v-if="selectedHairTypes.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedHairTypes.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.hair_types ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.hair_types" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="ht in facets.hair_types"
                    :key="ht.id || ht.slug"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedHairTypes.includes(ht.slug!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="ht.slug"
                            :checked="selectedHairTypes.includes(ht.slug!)"
                            @change="toggleFacetItem(selectedHairTypes, ht.slug!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ ht.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ ht.products_count ?? ht.count ?? 0 }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Clean Beauty Claims Filter Accordion -->
        <div v-if="facets?.claims && facets.claims.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('claims')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Beauty Claims</span>
                    <span
                        v-if="selectedClaims.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedClaims.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.claims ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.claims" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="cl in facets.claims"
                    :key="cl.id || cl.slug"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedClaims.includes(cl.slug!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="cl.slug"
                            :checked="selectedClaims.includes(cl.slug!)"
                            @change="toggleFacetItem(selectedClaims, cl.slug!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ cl.name }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ cl.products_count ?? cl.count ?? 0 }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Formula / Texture Filter Accordion -->
        <div v-if="facets?.formula_types && facets.formula_types.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('formula_types')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Formula & Texture</span>
                    <span
                        v-if="selectedFormulaTypes.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedFormulaTypes.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.formula_types ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.formula_types" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="ft in facets.formula_types"
                    :key="ft.value"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs capitalize transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedFormulaTypes.includes(ft.value!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="ft.value"
                            :checked="selectedFormulaTypes.includes(ft.value!)"
                            @change="toggleFacetItem(selectedFormulaTypes, ft.value!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ ft.value }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ ft.count }})
                    </span>
                </label>
            </div>
        </div>

        <!-- SPF Filter Accordion -->
        <div v-if="facets?.spf && facets.spf.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('spf')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>SPF Protection</span>
                    <span
                        v-if="selectedSpf.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedSpf.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.spf ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.spf" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="s in facets.spf"
                    :key="s.value"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedSpf.includes(s.value!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="s.value"
                            :checked="selectedSpf.includes(s.value!)"
                            @change="toggleFacetItem(selectedSpf, s.value!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ s.value }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ s.count }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Classification Filter Accordion -->
        <div v-if="facets?.classifications && facets.classifications.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('classifications')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Classification</span>
                    <span
                        v-if="selectedClassifications.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedClassifications.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.classifications ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.classifications" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="cls in facets.classifications"
                    :key="cls.value"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs capitalize transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedClassifications.includes(cls.value!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="cls.value"
                            :checked="selectedClassifications.includes(cls.value!)"
                            @change="toggleFacetItem(selectedClassifications, cls.value!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ cls.value }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ cls.count }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Country of Origin Filter Accordion -->
        <div v-if="facets?.origins && facets.origins.length > 0" class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('origins')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span class="flex items-center gap-1.5">
                    <span>Country of Origin</span>
                    <span
                        v-if="selectedOrigins.length > 0"
                        class="rounded-full bg-sage-100 px-1.5 py-0.2 text-[10px] font-bold text-sage-800"
                    >
                        {{ selectedOrigins.length }}
                    </span>
                </span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.origins ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.origins" class="mt-2.5 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                <label
                    v-for="orig in facets.origins"
                    :key="orig.value"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{ 'font-semibold text-sage-900': selectedOrigins.includes(orig.value!) }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="orig.value"
                            :checked="selectedOrigins.includes(orig.value!)"
                            @change="toggleFacetItem(selectedOrigins, orig.value!)"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ orig.value }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ orig.count }})
                    </span>
                </label>
            </div>
        </div>

        <!-- Price Range Filter Accordion -->
        <div class="border-b border-cream-200 pb-4">
            <button
                type="button"
                @click="toggleSection('price')"
                class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
            >
                <span>Price Range (€)</span>
                <span class="text-xs text-charcoal-400">
                    {{ openSections.price ? '−' : '+' }}
                </span>
            </button>
            <div v-show="openSections.price" class="mt-2.5 flex items-center gap-2">
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
        <div class="border-b border-cream-200 pb-4">
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
