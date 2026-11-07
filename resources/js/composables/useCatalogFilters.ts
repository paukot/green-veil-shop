import { computed, ref, type Ref } from 'vue';
import type { CatalogFilters } from '@/types/storefront';

/**
 * Calculates active filter count across all catalog facets and manages mobile filter drawer state.
 */
export function useCatalogFilters(
    filters: Ref<CatalogFilters> | (() => CatalogFilters),
) {
    const mobileFilterOpen = ref(false);

    const getFilters =
        typeof filters === 'function' ? filters : () => filters.value;

    const activeFilterCount = computed(() => {
        const f = getFilters();
        let count = 0;
        if (f.category) count++;
        if (f.min_price || f.max_price) count++;
        if (f.in_stock) count++;
        if (f.sort && f.sort !== 'featured') count++;
        if (f.q) count++;

        const countFacet = (val?: string) =>
            val ? val.split(',').filter(Boolean).length : 0;

        count += countFacet(f.brands);
        count += countFacet(f.skin_types);
        count += countFacet(f.hair_types);
        count += countFacet(f.effects);
        count += countFacet(f.ingredients);
        count += countFacet(f.claims);
        count += countFacet(f.classifications);
        count += countFacet(f.formula_types);
        count += countFacet(f.genders);
        count += countFacet(f.spf);
        count += countFacet(f.manufactured_in);
        return count;
    });

    return {
        mobileFilterOpen,
        activeFilterCount,
    };
}
