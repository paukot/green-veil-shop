<script setup lang="ts">
import { computed, ref } from 'vue';
import type {
    ProductDetailPageProps,
    ProductVariantItem,
} from '@/types/storefront';

const props = defineProps<{
    product: ProductDetailPageProps['product'];
    selectedVariant: ProductVariantItem | null;
}>();

const activeTab = ref<'description' | 'inci' | 'usage' | 'warnings' | 'specs'>(
    'description',
);

const countryNames: Record<string, string> = {
    FR: 'France',
    PL: 'Poland',
    IT: 'Italy',
    KR: 'South Korea',
    US: 'United States',
    DE: 'Germany',
    ES: 'Spain',
    GB: 'United Kingdom',
    JP: 'Japan',
    CH: 'Switzerland',
};

const manufacturedCountryName = computed(() => {
    const code = props.product.manufactured_in?.toUpperCase();
    if (!code) return null;
    return countryNames[code] || code;
});
</script>

<template>
    <div class="mt-16 border-t border-cream-200 pt-10">
        <!-- Tabs Navigation -->
        <div class="flex flex-wrap gap-2 border-b border-cream-200 pb-px">
            <button
                type="button"
                @click="activeTab = 'description'"
                class="border-b-2 px-5 py-3 text-xs font-semibold tracking-wider uppercase transition-colors"
                :class="
                    activeTab === 'description'
                        ? 'border-sage-800 text-sage-900'
                        : 'text-charcoal-500 border-transparent hover:text-charcoal-900'
                "
            >
                Ritual & Description
            </button>

            <button
                v-if="product.inci_list"
                type="button"
                @click="activeTab = 'inci'"
                class="border-b-2 px-5 py-3 text-xs font-semibold tracking-wider uppercase transition-colors"
                :class="
                    activeTab === 'inci'
                        ? 'border-sage-800 text-sage-900'
                        : 'text-charcoal-500 border-transparent hover:text-charcoal-900'
                "
            >
                Ingredients (INCI)
            </button>

            <button
                v-if="product.how_to_use"
                type="button"
                @click="activeTab = 'usage'"
                class="border-b-2 px-5 py-3 text-xs font-semibold tracking-wider uppercase transition-colors"
                :class="
                    activeTab === 'usage'
                        ? 'border-sage-800 text-sage-900'
                        : 'text-charcoal-500 border-transparent hover:text-charcoal-900'
                "
            >
                How to Use
            </button>

            <button
                v-if="product.warnings || product.responsible_person"
                type="button"
                @click="activeTab = 'warnings'"
                class="border-b-2 px-5 py-3 text-xs font-semibold tracking-wider uppercase transition-colors"
                :class="
                    activeTab === 'warnings'
                        ? 'border-sage-800 text-sage-900'
                        : 'text-charcoal-500 border-transparent hover:text-charcoal-900'
                "
            >
                Safety & Warnings
            </button>

            <button
                type="button"
                @click="activeTab = 'specs'"
                class="border-b-2 px-5 py-3 text-xs font-semibold tracking-wider uppercase transition-colors"
                :class="
                    activeTab === 'specs'
                        ? 'border-sage-800 text-sage-900'
                        : 'text-charcoal-500 border-transparent hover:text-charcoal-900'
                "
            >
                Specifications
            </button>
        </div>

        <!-- Tab 1: Description -->
        <div
            v-show="activeTab === 'description'"
            class="max-w-4xl space-y-4 py-8"
        >
            <div
                v-if="product.description"
                class="prose prose-sm prose-stone max-w-none leading-relaxed font-light text-charcoal-700"
            >
                <p class="whitespace-pre-line">{{ product.description }}</p>
            </div>
            <p v-else class="text-charcoal-400 italic">
                No detailed ritual description provided for this botanical
                formulation.
            </p>
        </div>

        <!-- Tab 2: Full INCI Formulation -->
        <div v-show="activeTab === 'inci'" class="max-w-4xl space-y-6 py-8">
            <div>
                <h3 class="mb-2 font-serif text-xl text-charcoal-900">
                    Full Ingredient Declaration (INCI)
                </h3>
                <p class="text-charcoal-500 text-xs">
                    In compliance with EU Cosmetic Regulation (EC) No 1223/2009.
                    Listed in descending order of concentration.
                </p>
            </div>

            <div
                class="rounded-xl border border-cream-200 bg-cream-50/50 p-6 leading-relaxed"
            >
                <p
                    class="font-mono text-xs leading-loose text-charcoal-800 select-all"
                >
                    {{ product.inci_list }}
                </p>
            </div>

            <div
                v-if="product.ingredients && product.ingredients.length > 0"
                class="space-y-3"
            >
                <h4
                    class="text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
                >
                    Key Active Ingredients Highlighted:
                </h4>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="ing in product.ingredients"
                        :key="ing.id"
                        class="rounded-lg border border-cream-200 bg-white p-3 shadow-2xs"
                    >
                        <div class="text-xs font-medium text-charcoal-900">
                            {{ ing.name }}
                        </div>
                        <div
                            v-if="ing.inci_name"
                            class="font-mono text-[10px] text-sage-700"
                        >
                            {{ ing.inci_name }}
                        </div>
                        <p
                            v-if="ing.description"
                            class="text-charcoal-600 mt-1 line-clamp-2 text-[11px]"
                        >
                            {{ ing.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: How to Use -->
        <div v-show="activeTab === 'usage'" class="max-w-4xl space-y-4 py-8">
            <h3 class="font-serif text-xl text-charcoal-900">
                Application & Ritual
            </h3>
            <div
                class="prose prose-sm prose-stone max-w-none leading-relaxed font-light text-charcoal-700"
            >
                <p class="whitespace-pre-line">{{ product.how_to_use }}</p>
            </div>
        </div>

        <!-- Tab 4: Warnings & Safety -->
        <div
            v-show="activeTab === 'warnings'"
            class="max-w-4xl space-y-6 py-8 text-xs"
        >
            <div
                class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 text-amber-900"
            >
                <div class="flex items-start gap-3">
                    <svg
                        class="h-5 w-5 shrink-0 text-amber-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-amber-900">
                            Cosmetic Precautionary Advice
                        </h4>
                        <p class="mt-1 leading-relaxed text-amber-800">
                            {{
                                product.warnings ||
                                'For external use only. Avoid contact with eyes. Discontinue use if irritation occurs. Keep out of reach of children.'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="product.responsible_person"
                class="rounded-lg border border-cream-200 bg-white p-4"
            >
                <span class="font-semibold text-charcoal-900"
                    >EU Responsible Person:</span
                >
                <span class="ml-2 font-mono">{{
                    product.responsible_person
                }}</span>
            </div>
        </div>

        <!-- Tab 5: Specifications Table -->
        <div v-show="activeTab === 'specs'" class="max-w-4xl py-8">
            <h3 class="mb-6 font-serif text-xl text-charcoal-900">
                Product Specifications
            </h3>

            <div
                class="overflow-hidden rounded-xl border border-cream-200 bg-white"
            >
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-cream-200">
                        <tr v-if="product.brand">
                            <td
                                class="text-charcoal-600 w-1/3 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Brand
                            </td>
                            <td
                                class="px-4 py-3 font-semibold text-charcoal-900"
                            >
                                {{ product.brand.name }}
                            </td>
                        </tr>
                        <tr v-if="product.series">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Series
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.series }}
                            </td>
                        </tr>
                        <tr v-if="product.category">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Category
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.category.name }}
                            </td>
                        </tr>
                        <tr v-if="product.volume">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Volume / Net Content
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.volume }}
                                {{ product.unit || 'ml' }}
                            </td>
                        </tr>
                        <tr v-if="product.formatted_unit_price">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                EU Unit Price
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.formatted_unit_price }}
                            </td>
                        </tr>
                        <tr v-if="product.classification">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Classification
                            </td>
                            <td class="px-4 py-3 text-charcoal-800 capitalize">
                                {{ product.classification }}
                            </td>
                        </tr>
                        <tr v-if="product.formula_type">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Formula / Texture
                            </td>
                            <td class="px-4 py-3 text-charcoal-800 capitalize">
                                {{ product.formula_type }}
                            </td>
                        </tr>
                        <tr v-if="product.gender">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Target Audience
                            </td>
                            <td class="px-4 py-3 text-charcoal-800 capitalize">
                                {{ product.gender }}
                            </td>
                        </tr>
                        <tr v-if="product.spf">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Sun Protection (SPF)
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.spf }}
                            </td>
                        </tr>
                        <tr v-if="product.pao_months">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Period After Opening (PAO)
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ product.pao_months }} months
                            </td>
                        </tr>
                        <tr v-if="manufacturedCountryName">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Country of Origin
                            </td>
                            <td class="px-4 py-3 text-charcoal-800">
                                {{ manufacturedCountryName }}
                            </td>
                        </tr>
                        <tr v-if="product.barcode">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                Barcode / EAN
                            </td>
                            <td class="px-4 py-3 font-mono text-charcoal-800">
                                {{ product.barcode }}
                            </td>
                        </tr>
                        <tr v-if="product.sku">
                            <td
                                class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                            >
                                SKU
                            </td>
                            <td class="px-4 py-3 font-mono text-charcoal-800">
                                {{ selectedVariant?.sku || product.sku }}
                            </td>
                        </tr>
                        <!-- Custom Characteristics Key-Values -->
                        <template v-if="product.custom_characteristics">
                            <tr
                                v-for="(
                                    val, key
                                ) in product.custom_characteristics"
                                :key="key"
                            >
                                <td
                                    class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                                >
                                    {{ key }}
                                </td>
                                <td class="px-4 py-3 text-charcoal-800">
                                    {{ val }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
