<script setup lang="ts">
import { computed, nextTick, ref, useId } from 'vue';
import { TriangleAlertIcon } from '@lucide/vue';
import type {
    ProductDetailPageProps,
    ProductVariantItem,
} from '@/types/storefront';
import { cn } from '@/lib/utils';

type TabKey = 'description' | 'inci' | 'usage' | 'warnings' | 'specs';

const props = defineProps<{
    product: ProductDetailPageProps['product'];
    selectedVariant: ProductVariantItem | null;
}>();

const baseId = useId();
const activeTab = ref<TabKey>('description');
const tabRefs = ref<HTMLButtonElement[]>([]);

const tabs = computed(() =>
    (
        [
            { key: 'description', label: 'Description', show: true },
            {
                key: 'inci',
                label: 'Ingredients',
                show: Boolean(props.product.inci_list),
            },
            {
                key: 'usage',
                label: 'How to use',
                show: Boolean(props.product.how_to_use),
            },
            {
                key: 'warnings',
                label: 'Safety',
                show: Boolean(
                    props.product.warnings || props.product.responsible_person,
                ),
            },
            { key: 'specs', label: 'Specifications', show: true },
        ] as Array<{ key: TabKey; label: string; show: boolean }>
    ).filter((tab) => tab.show),
);

/** Arrow keys move between tabs, as the WAI-ARIA tabs pattern expects. */
async function onTabKeydown(event: KeyboardEvent, index: number) {
    const delta =
        event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
    if (delta === 0) return;

    event.preventDefault();
    const nextIndex = (index + delta + tabs.value.length) % tabs.value.length;
    activeTab.value = tabs.value[nextIndex].key;
    await nextTick();
    tabRefs.value[nextIndex]?.focus();
}

const manufacturedCountryName = computed(
    () =>
        props.product.manufactured_in_name ||
        props.product.manufactured_in ||
        null,
);

const capitalise = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

const specifications = computed(() => {
    const product = props.product;
    const rows: Array<{
        label: string;
        value: string | number | null | undefined;
        mono?: boolean;
    }> = [
        { label: 'Brand', value: product.brand?.name },
        { label: 'Series', value: product.series },
        { label: 'Category', value: product.category?.name },
        {
            label: 'Net content',
            value: product.volume
                ? `${product.volume} ${product.unit || 'ml'}`
                : null,
        },
        { label: 'Unit price', value: product.formatted_unit_price },
        {
            label: 'Classification',
            value: product.classification
                ? capitalise(product.classification)
                : null,
        },
        {
            label: 'Formula',
            value: product.formula_type
                ? capitalise(product.formula_type)
                : null,
        },
        {
            label: 'For',
            value: product.gender ? capitalise(product.gender) : null,
        },
        { label: 'SPF', value: product.spf },
        {
            label: 'After opening',
            value: product.pao_months ? `${product.pao_months} months` : null,
        },
        { label: 'Country of origin', value: manufacturedCountryName.value },
        { label: 'Barcode', value: product.barcode, mono: true },
        {
            label: 'SKU',
            value: props.selectedVariant?.sku || product.sku,
            mono: true,
        },
        ...Object.entries(product.custom_characteristics ?? {}).map(
            ([label, value]) => ({
                label,
                value: String(value),
            }),
        ),
    ];

    return rows.filter(
        (row) =>
            row.value !== null && row.value !== undefined && row.value !== '',
    );
});
</script>

<template>
    <section
        class="mt-16 flex flex-col gap-8 border-t border-border pt-12 lg:mt-24"
    >
        <div
            role="tablist"
            aria-label="Product information"
            class="-mx-4 scrollbar-none flex gap-8 overflow-x-auto border-b border-border px-4 sm:mx-0 sm:px-0"
        >
            <button
                v-for="(tab, index) in tabs"
                :id="`${baseId}-tab-${tab.key}`"
                :key="tab.key"
                ref="tabRefs"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :aria-controls="`${baseId}-panel-${tab.key}`"
                :tabindex="activeTab === tab.key ? 0 : -1"
                :class="
                    cn(
                        '-mb-px flex h-12 shrink-0 items-center border-b-2 text-sm font-medium whitespace-nowrap transition-colors',
                        activeTab === tab.key
                            ? 'border-ink text-ink'
                            : 'border-transparent text-ink-subtle hover:text-ink',
                    )
                "
                @click="activeTab = tab.key"
                @keydown="onTabKeydown($event, index)"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="max-w-3xl">
            <div
                v-show="activeTab === 'description'"
                :id="`${baseId}-panel-description`"
                role="tabpanel"
                :aria-labelledby="`${baseId}-tab-description`"
                class="rich-text"
            >
                <p v-if="product.description" class="whitespace-pre-line">
                    {{ product.description }}
                </p>
                <p v-else class="italic">No detailed description yet.</p>
            </div>

            <div
                v-show="activeTab === 'inci'"
                :id="`${baseId}-panel-inci`"
                role="tabpanel"
                :aria-labelledby="`${baseId}-tab-inci`"
                class="flex flex-col gap-6"
            >
                <div class="flex flex-col gap-2">
                    <h3 class="font-serif text-2xl text-ink">
                        Full ingredient list (INCI)
                    </h3>
                    <p class="text-sm text-ink-muted">
                        Per EU Cosmetic Regulation (EC) No 1223/2009, in
                        descending order of concentration.
                    </p>
                </div>
                <p
                    class="rounded-2xl bg-surface-sunken p-6 font-mono text-xs leading-6 text-ink select-all"
                >
                    {{ product.inci_list }}
                </p>
                <div
                    v-if="product.ingredients && product.ingredients.length > 0"
                    class="flex flex-col gap-4"
                >
                    <h4
                        class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                    >
                        Key actives
                    </h4>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        <li
                            v-for="ing in product.ingredients"
                            :key="ing.id"
                            class="flex flex-col gap-1 rounded-lg border border-border p-4"
                        >
                            <p class="text-sm font-medium text-ink">
                                {{ ing.name }}
                            </p>
                            <p
                                v-if="ing.inci_name"
                                class="font-mono text-xs text-rose-700"
                            >
                                {{ ing.inci_name }}
                            </p>
                            <p
                                v-if="ing.description"
                                class="line-clamp-2 text-xs text-ink-muted"
                            >
                                {{ ing.description }}
                            </p>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                v-show="activeTab === 'usage'"
                :id="`${baseId}-panel-usage`"
                role="tabpanel"
                :aria-labelledby="`${baseId}-tab-usage`"
                class="rich-text"
            >
                <h3>How to use</h3>
                <p class="whitespace-pre-line">{{ product.how_to_use }}</p>
            </div>

            <div
                v-show="activeTab === 'warnings'"
                :id="`${baseId}-panel-warnings`"
                role="tabpanel"
                :aria-labelledby="`${baseId}-tab-warnings`"
                class="flex flex-col gap-4"
            >
                <div
                    class="flex items-start gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-6"
                >
                    <TriangleAlertIcon
                        class="size-6 shrink-0 text-warning-600"
                        aria-hidden="true"
                    />
                    <div class="flex flex-col gap-1">
                        <h3 class="text-sm font-semibold text-warning-800">
                            Precautions
                        </h3>
                        <p class="text-sm text-warning-700">
                            {{
                                product.warnings ||
                                'For external use only. Avoid contact with eyes. Discontinue use if irritation occurs. Keep out of reach of children.'
                            }}
                        </p>
                    </div>
                </div>
                <p
                    v-if="product.responsible_person"
                    class="text-sm text-ink-muted"
                >
                    <span class="font-medium text-ink"
                        >EU responsible person:</span
                    >
                    {{ product.responsible_person }}
                </p>
            </div>

            <div
                v-show="activeTab === 'specs'"
                :id="`${baseId}-panel-specs`"
                role="tabpanel"
                :aria-labelledby="`${baseId}-tab-specs`"
            >
                <dl class="divide-y divide-border border-y border-border">
                    <div
                        v-for="row in specifications"
                        :key="row.label"
                        class="grid grid-cols-3 gap-4 py-3 text-sm"
                    >
                        <dt class="text-ink-subtle">{{ row.label }}</dt>
                        <dd
                            :class="
                                cn(
                                    'col-span-2 text-ink',
                                    row.mono && 'font-mono text-xs leading-5',
                                )
                            "
                        >
                            {{ row.value }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
