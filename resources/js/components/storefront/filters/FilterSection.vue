<script setup lang="ts">
import { computed, ref } from 'vue';
import type { FacetOption } from '@/types/storefront';
import FilterAccordion from '@/components/storefront/filters/FilterAccordion.vue';
import { CheckboxInput, SearchInput } from '@/components/form';

const props = withDefaults(
    defineProps<{
        title: string;
        filterKey?: string;
        options?: FacetOption[];
        selected: string[];
        searchPlaceholder?: string;
        searchableThreshold?: number;
    }>(),
    {
        options: () => [],
        searchPlaceholder: 'Search...',
        searchableThreshold: 6,
    },
);

const emit = defineEmits<{
    (e: 'update:selected', value: string[]): void;
    (e: 'toggle', value: string): void;
}>();

const isOpen = defineModel<boolean>('isOpen', { default: false });
const searchQuery = ref('');

const filteredOptions = computed(() => {
    if (!props.options || props.options.length === 0) return [];
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.options;

    return props.options.filter((opt) => {
        const label = (opt.name || opt.value || '').toLowerCase();
        return label.includes(query);
    });
});

function toggle(val: string) {
    emit('toggle', val);
}
</script>

<template>
    <FilterAccordion
        v-if="options && options.length > 0"
        v-model:is-open="isOpen"
        :title="title"
        :filter-key="filterKey"
        :count="selected.length"
    >
        <SearchInput
            v-if="options.length > searchableThreshold"
            v-model="searchQuery"
            size="sm"
            :placeholder="searchPlaceholder"
            :aria-label="searchPlaceholder"
        />
        <ul
            class="flex max-h-64 flex-col overflow-y-auto"
            :aria-label="`${title} options`"
        >
            <li
                v-for="opt in filteredOptions"
                :key="opt.slug || opt.value || opt.id"
            >
                <label
                    class="flex h-8 cursor-pointer items-center gap-3 text-sm text-ink-muted transition-colors hover:text-ink"
                >
                    <CheckboxInput
                        :model-value="
                            selected.includes(opt.slug || opt.value || '')
                        "
                        :value="opt.slug || opt.value"
                        @update:model-value="
                            toggle(opt.slug || opt.value || '')
                        "
                    />
                    <span
                        class="flex-1 truncate"
                        :class="{
                            'font-medium text-ink': selected.includes(
                                opt.slug || opt.value || '',
                            ),
                        }"
                    >
                        {{ opt.name || opt.value }}
                    </span>
                    <span
                        class="text-xs text-ink-subtle tabular-nums"
                        aria-hidden="true"
                    >
                        {{ opt.products_count ?? opt.count ?? 0 }}
                    </span>
                </label>
            </li>
        </ul>
    </FilterAccordion>
</template>
