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
import FilterAccordion from '@/components/storefront/filters/FilterAccordion.vue';
import Button from '@/components/ui/Button.vue';
import Chip from '@/components/ui/Chip.vue';
import { usePageLoading } from '@/composables/usePageLoading';
import { index as productsIndex } from '@/routes/products';
import type {
    CatalogFilters,
    CategoryFilterItem,
    FilterFacets,
} from '@/types/storefront';
import { FunnelIcon } from '@lucide/vue';

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
    brands: selectedBrands.value.length > 0,
    price: Boolean(props.filters.min_price || props.filters.max_price),
    skin_types: selectedSkinTypes.value.length > 0,
    effects: selectedEffects.value.length > 0,
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
    <div class="flex flex-col" aria-label="Filters and sorting">
        <div
            v-if="totalActiveFilters > 0 || searchQuery"
            class="flex flex-col gap-3 border-b border-border pb-4"
        >
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-ink">
                    {{ totalActiveFilters }} active
                    {{ totalActiveFilters === 1 ? 'filter' : 'filters' }}
                </p>
                <button
                    type="button"
                    class="text-sm font-medium text-rose-800 underline-offset-4 hover:underline"
                    @click="clearAllFilters"
                >
                    Clear all
                </button>
            </div>
            <div v-if="searchQuery">
                <Chip
                    removable
                    static
                    remove-label="Remove search"
                    @remove="applyFilters({ q: '' })"
                >
                    Search: “{{ searchQuery }}”
                </Chip>
            </div>
        </div>

        <FilterAccordion v-model:is-open="openSections.sort" title="Sort by">
            <SelectInput
                v-model="selectedSort"
                size="sm"
                aria-label="Sort products"
            >
                <option value="featured">Featured</option>
                <option value="newest">Newest</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
            </SelectInput>
        </FilterAccordion>

        <FilterAccordion
            v-if="showCategories && categories.length > 0"
            v-model:is-open="openSections.categories"
            title="Categories"
            :count="selectedCategory ? 1 : 0"
        >
            <ul class="flex max-h-64 flex-col overflow-y-auto">
                <li>
                    <label
                        class="flex h-8 cursor-pointer items-center gap-3 text-sm text-ink-muted hover:text-ink"
                    >
                        <RadioInput
                            name="category_filter"
                            value=""
                            :checked="!selectedCategory"
                            @change="selectedCategory = ''"
                        />
                        <span
                            class="flex-1"
                            :class="{
                                'font-medium text-ink': !selectedCategory,
                            }"
                        >
                            All categories
                        </span>
                    </label>
                </li>
                <li v-for="cat in categories" :key="cat.id">
                    <label
                        class="flex h-8 cursor-pointer items-center gap-3 text-sm text-ink-muted hover:text-ink"
                    >
                        <RadioInput
                            name="category_filter"
                            :value="cat.slug"
                            :checked="selectedCategory === cat.slug"
                            @change="selectedCategory = cat.slug"
                        />
                        <span
                            class="flex-1 truncate"
                            :class="{
                                'font-medium text-ink':
                                    selectedCategory === cat.slug,
                            }"
                        >
                            {{ cat.name }}
                        </span>
                        <span class="text-xs text-ink-subtle tabular-nums">
                            {{ cat.products_count }}
                        </span>
                    </label>
                </li>
            </ul>
        </FilterAccordion>

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

        <FilterAccordion v-model:is-open="openSections.price" title="Price (€)">
            <div class="flex items-center gap-2">
                <TextInput
                    v-model="minPrice"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Min"
                    size="sm"
                    aria-label="Minimum price"
                    @keydown.enter="applyFilters()"
                />
                <span class="text-ink-subtle" aria-hidden="true">–</span>
                <TextInput
                    v-model="maxPrice"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Max"
                    size="sm"
                    aria-label="Maximum price"
                    @keydown.enter="applyFilters()"
                />
            </div>
        </FilterAccordion>

        <div class="border-b border-border py-4">
            <CheckboxField
                id="filter-in-stock"
                v-model="inStock"
                label="In stock only"
            />
        </div>

        <div
            class="sticky bottom-0 -mx-6 -mb-6 flex flex-col gap-3 border-t border-border bg-surface-raised p-6 lg:static lg:mx-0 lg:mb-0 lg:border-t-0 lg:bg-transparent lg:px-0 lg:pb-0"
        >
            <Button block :loading="isLoading" @click="applyFilters()">
                <template #icon>
                    <FunnelIcon class="size-4" aria-hidden="true" />
                </template>
                {{ isLoading ? 'Applying' : 'Apply filters' }}
            </Button>
            <Button block variant="ghost" @click="clearAllFilters">
                Reset filters
            </Button>
        </div>
    </div>
</template>
