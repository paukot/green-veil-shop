<script setup lang="ts">
import Chip from '@/components/ui/Chip.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { CatalogFilters, FilterFacets } from '@/types/storefront';

const props = defineProps<{
    filters: CatalogFilters;
    facets?: FilterFacets;
    baseUrl: string;
    resetUrl?: string;
}>();

export type FilterChip = {
    key: keyof CatalogFilters;
    value: string;
    label: string;
    isMulti: boolean;
};

const activeChips = computed<FilterChip[]>(() => {
    const chips: FilterChip[] = [];
    const f = props.filters;

    if (f.q && f.q.trim()) {
        chips.push({
            key: 'q',
            value: f.q,
            label: `Search: "${f.q}"`,
            isMulti: false,
        });
    }

    if (f.category) {
        chips.push({
            key: 'category',
            value: f.category,
            label: `Category: ${f.category}`,
            isMulti: false,
        });
    }

    if (f.in_stock) {
        chips.push({
            key: 'in_stock',
            value: 'true',
            label: 'In Stock Only',
            isMulti: false,
        });
    }

    if (f.min_price || f.max_price) {
        const min = f.min_price ? `€${f.min_price}` : '€0';
        const max = f.max_price ? `€${f.max_price}` : 'Any';
        chips.push({
            key: 'min_price',
            value: `${f.min_price}_${f.max_price}`,
            label: `Price: ${min} – ${max}`,
            isMulti: false,
        });
    }

    const addMulti = (
        key: keyof CatalogFilters,
        val: string | undefined,
        prefix: string,
        facetList?: Array<{ slug?: string; name?: string; value?: string }>,
    ) => {
        if (!val) return;
        const items = val.split(',').filter(Boolean);
        for (const item of items) {
            let display = item;
            if (facetList) {
                const found = facetList.find(
                    (fac) =>
                        fac.slug === item ||
                        fac.value === item ||
                        fac.name?.toLowerCase() === item.toLowerCase(),
                );
                if (found?.name) display = found.name;
            }
            chips.push({
                key,
                value: item,
                label: `${prefix}: ${display}`,
                isMulti: true,
            });
        }
    };

    addMulti('brands', f.brands, 'Brand', props.facets?.brands);
    addMulti('skin_types', f.skin_types, 'Skin', props.facets?.skin_types);
    addMulti('hair_types', f.hair_types, 'Hair', props.facets?.hair_types);
    addMulti('effects', f.effects, 'Effect', props.facets?.effects);
    addMulti('ingredients', f.ingredients, 'Active', props.facets?.ingredients);
    addMulti('claims', f.claims, 'Claim', props.facets?.claims);
    addMulti(
        'classifications',
        f.classifications,
        'Classification',
        props.facets?.classifications,
    );
    addMulti(
        'formula_types',
        f.formula_types,
        'Texture',
        props.facets?.formula_types,
    );
    addMulti('genders', f.genders, 'Target', props.facets?.genders);
    addMulti('spf', f.spf, 'SPF', props.facets?.spf);
    addMulti(
        'manufactured_in',
        f.manufactured_in,
        'Origin',
        props.facets?.origins,
    );

    return chips;
});

function removeChip(chip: FilterChip) {
    const currentParams: Record<
        string,
        string | number | boolean | null | undefined
    > = { ...props.filters };

    if (chip.isMulti) {
        const currentVal = (currentParams[chip.key] as string) || '';
        const remaining = currentVal.split(',').filter((v) => v !== chip.value);

        if (remaining.length > 0) {
            currentParams[chip.key] = remaining.join(',');
        } else {
            delete currentParams[chip.key];
        }
    } else {
        if (chip.key === 'min_price') {
            delete currentParams.min_price;
            delete currentParams.max_price;
        } else {
            delete currentParams[chip.key];
        }
    }

    router.get(props.baseUrl, currentParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearAllFilters() {
    router.get(
        props.resetUrl || props.baseUrl,
        {},
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <div
        v-if="activeChips.length > 0"
        class="flex flex-wrap items-center gap-2"
        role="group"
        aria-label="Active filters"
    >
        <Chip
            v-for="chip in activeChips"
            :key="`${chip.key}_${chip.value}`"
            static
            removable
            :remove-label="`Remove filter ${chip.label}`"
            @remove="removeChip(chip)"
        >
            {{ chip.label }}
        </Chip>
        <button
            type="button"
            aria-label="Clear all active filters"
            class="ml-2 text-sm font-medium text-rose-800 underline-offset-4 hover:underline"
            @click="clearAllFilters"
        >
            Clear all
        </button>
    </div>
</template>
