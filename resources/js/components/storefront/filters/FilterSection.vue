<script setup lang="ts">
import { computed, ref } from 'vue';
import type { FacetOption } from '@/types/storefront';

const props = withDefaults(
    defineProps<{
        title: string;
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
    <div
        v-if="options && options.length > 0"
        class="border-b border-cream-200 pb-4"
    >
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="flex w-full items-center justify-between py-1 text-left text-xs font-semibold tracking-wider text-charcoal-800 uppercase hover:text-sage-800"
        >
            <span class="flex items-center gap-1.5">
                <span>{{ title }}</span>
                <span
                    v-if="selected.length > 0"
                    class="py-0.2 rounded-full bg-sage-100 px-1.5 text-[10px] font-bold text-sage-800"
                >
                    {{ selected.length }}
                </span>
            </span>
            <span class="text-charcoal-400 text-xs">
                {{ isOpen ? '−' : '+' }}
            </span>
        </button>

        <div v-show="isOpen" class="mt-2.5 space-y-2">
            <input
                v-if="options.length > searchableThreshold"
                v-model="searchQuery"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full rounded-md border border-cream-300 bg-cream-50 px-2.5 py-1 text-xs focus:border-sage-600 focus:bg-white focus:outline-none"
            />
            <div class="max-h-52 space-y-1.5 overflow-y-auto pr-1 lg:max-h-50">
                <label
                    v-for="opt in filteredOptions"
                    :key="opt.slug || opt.value || opt.id"
                    class="flex cursor-pointer items-center justify-between py-0.5 text-xs transition-colors hover:text-sage-800"
                    :class="{
                        'font-semibold text-sage-900': selected.includes(
                            opt.slug || opt.value || '',
                        ),
                    }"
                >
                    <span class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :value="opt.slug || opt.value"
                            :checked="
                                selected.includes(opt.slug || opt.value || '')
                            "
                            @change="toggle(opt.slug || opt.value || '')"
                            class="h-3.5 w-3.5 rounded border-cream-300 text-sage-700 focus:ring-sage-500"
                        />
                        <span>{{ opt.name || opt.value }}</span>
                    </span>
                    <span class="text-charcoal-400 text-[11px]">
                        ({{ opt.products_count ?? opt.count ?? 0 }})
                    </span>
                </label>
            </div>
        </div>
    </div>
</template>
