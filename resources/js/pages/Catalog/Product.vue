<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import { QuantityInput } from '@/components/form';
import type {
    ProductDetailPageProps,
    ProductVariantItem,
} from '@/types/storefront';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { index as productsIndex } from '@/routes/products';
import { formatCurrency } from '@/lib/utils';
import { useCart } from '@/composables/useCart';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<ProductDetailPageProps>();

// Variant selection state
const selectedVariant = ref<ProductVariantItem | null>(
    props.product.variants?.find((v) => v.is_default) ||
        props.product.variants?.[0] ||
        null,
);

function selectVariant(variant: ProductVariantItem) {
    selectedVariant.value = variant;
    // If variant has its own image, switch to it
    if (variant.image) {
        const foundIdx = allImages.value.findIndex(
            (img) => img.path === variant.image,
        );
        if (foundIdx !== -1) {
            activeImageIndex.value = foundIdx;
        }
    }
}

// Current effective price & stock
const currentPrice = computed(() => {
    return selectedVariant.value?.price ?? props.product.price;
});

const currentComparePrice = computed(() => {
    return (
        selectedVariant.value?.compare_at_price ??
        props.product.compare_at_price
    );
});

const isInStock = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.is_in_stock;
    }
    return props.product.is_in_stock;
});

const stockQuantity = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.stock_quantity;
    }
    return props.product.stock_quantity;
});

// Gallery state
const allImages = computed(() => {
    const list: Array<{
        id: number | string;
        path: string;
        alt_text?: string | null;
    }> = [];

    if (props.product.main_image) {
        list.push({
            id: 'main',
            path: props.product.main_image,
            alt_text: props.product.name,
        });
    }

    if (props.product.images && props.product.images.length > 0) {
        props.product.images.forEach((img) => {
            if (img.path !== props.product.main_image) {
                list.push(img);
            }
        });
    }

    // Add variant images if unique
    if (props.product.variants) {
        props.product.variants.forEach((v) => {
            if (v.image && !list.some((item) => item.path === v.image)) {
                list.push({
                    id: `variant-${v.id}`,
                    path: v.image,
                    alt_text: `${props.product.name} - ${v.name}`,
                });
            }
        });
    }

    return list;
});

const activeImageIndex = ref(0);
const activeImage = computed(() => {
    if (allImages.value.length === 0) return null;
    return allImages.value[activeImageIndex.value] || allImages.value[0];
});

// Quantity state
const quantity = ref(1);
const maxQuantity = computed(() => {
    const track = selectedVariant.value
        ? selectedVariant.value.track_inventory
        : props.product.track_inventory;
    if (!track) return 99;
    return Math.max(1, stockQuantity.value);
});

const cart = useCart();
const addedToCartNotice = ref(false);

function addToCart() {
    if (!isInStock.value) return;

    cart.addItem(
        {
            id: props.product.id,
            name: props.product.name,
            slug: props.product.slug,
            sku: selectedVariant.value?.sku || props.product.sku,
            price: currentPrice.value,
            compare_at_price: currentComparePrice.value,
            discount_percentage: props.product.discount_percentage,
            main_image: props.product.main_image,
            volume: props.product.volume,
            unit: props.product.unit,
            formatted_unit_price: props.product.formatted_unit_price,
            track_inventory: selectedVariant.value
                ? selectedVariant.value.track_inventory
                : props.product.track_inventory,
            stock_quantity: stockQuantity.value,
            is_in_stock: isInStock.value,
            brand: props.product.brand,
            category: props.product.category,
        },
        quantity.value,
    );

    addedToCartNotice.value = true;
    setTimeout(() => {
        addedToCartNotice.value = false;
    }, 2000);
}

// Active tab for cosmetic information disclosures
const activeTab = ref<'description' | 'inci' | 'usage' | 'warnings' | 'specs'>(
    'description',
);

// Map ISO 2-letter country codes to human-readable names
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
    <Head :title="`${product.name} | Green Veil`">
        <meta
            name="description"
            :content="
                product.short_description ||
                product.description?.slice(0, 160) ||
                'Handcrafted botanical care by Green Veil.'
            "
        />
        <meta
            property="og:title"
            :content="`${product.name} | Green Veil Botanical Apothecary`"
        />
        <meta
            property="og:description"
            :content="
                product.short_description ||
                product.description?.slice(0, 160) ||
                'Handcrafted botanical care by Green Veil.'
            "
        />
        <meta property="og:type" content="product" />
        <meta
            property="og:price:amount"
            :content="Number(currentPrice).toFixed(2)"
        />
        <meta property="og:price:currency" content="EUR" />
        <meta
            v-if="activeImage"
            property="og:image"
            :content="activeImage.path"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="`${product.name} | Green Veil`" />
        <meta
            name="twitter:description"
            :content="
                product.short_description ||
                product.description?.slice(0, 160) ||
                'Handcrafted botanical care by Green Veil.'
            "
        />
    </Head>

    <!-- Breadcrumbs Navigation -->
    <Breadcrumbs :items="breadcrumbs" />

    <!-- Product Main Showcase -->
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <div
            class="grid grid-cols-1 items-start gap-10 lg:grid-cols-2 lg:gap-16"
        >
            <!-- Gallery Column -->
            <div class="space-y-4">
                <!-- Main Image Stage -->
                <div
                    class="relative flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
                >
                    <img
                        v-if="activeImage"
                        :src="
                            activeImage.path.startsWith('http')
                                ? activeImage.path
                                : `/${activeImage.path}`
                        "
                        :alt="activeImage.alt_text || product.name"
                        class="h-full w-full object-cover object-center transition-all duration-300"
                    />
                    <div
                        v-else
                        class="text-charcoal-400 p-8 text-center text-sm font-light"
                    >
                        <svg
                            class="mx-auto mb-2 h-16 w-16 text-cream-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span>Botanical Image Preview</span>
                    </div>

                    <!-- Savings Badge -->
                    <span
                        v-if="product.discount_percentage"
                        class="absolute top-4 left-4 rounded-full bg-sage-800 px-3 py-1 text-[11px] font-semibold tracking-wider text-cream-50 uppercase shadow-sm"
                    >
                        Save {{ product.discount_percentage }}%
                    </span>
                </div>

                <!-- Gallery Thumbnails -->
                <div
                    v-if="allImages.length > 1"
                    class="flex gap-3 overflow-x-auto pb-2"
                >
                    <button
                        v-for="(img, idx) in allImages"
                        :key="img.id"
                        type="button"
                        @click="activeImageIndex = idx"
                        class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border-2 bg-white transition-all"
                        :class="
                            activeImageIndex === idx
                                ? 'border-sage-700 ring-2 ring-sage-200'
                                : 'border-cream-200 opacity-70 hover:opacity-100'
                        "
                        :aria-label="`View image ${idx + 1}`"
                    >
                        <img
                            :src="
                                img.path.startsWith('http')
                                    ? img.path
                                    : `/${img.path}`
                            "
                            :alt="img.alt_text || product.name"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                    </button>
                </div>
            </div>

            <!-- Product Details Column -->
            <div class="space-y-6">
                <!-- Brand, Series & Category Top Bar -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <!-- Brand Link -->
                            <Link
                                v-if="product.brand"
                                :href="
                                    productsIndex.url({
                                        query: { brands: product.brand.slug },
                                    })
                                "
                                class="hover:text-sage-950 font-bold tracking-widest text-sage-800 uppercase hover:underline"
                            >
                                {{ product.brand.name }}
                            </Link>
                            <span
                                v-if="product.brand && product.category"
                                class="text-charcoal-300"
                                >&bull;</span
                            >
                            <!-- Category Link -->
                            <Link
                                v-if="product.category"
                                :href="categoryShow.url(product.category.slug)"
                                class="text-charcoal-500 font-medium tracking-wider uppercase hover:text-sage-800"
                            >
                                {{ product.category.name }}
                            </Link>
                        </div>

                        <!-- SKU / Code -->
                        <span
                            v-if="product.sku"
                            class="text-charcoal-400 font-mono text-[11px]"
                        >
                            SKU: {{ selectedVariant?.sku || product.sku }}
                        </span>
                    </div>

                    <!-- Series / Product Line Subtitle -->
                    <div
                        v-if="product.series"
                        class="text-xs font-medium tracking-wide text-sage-700"
                    >
                        Series: {{ product.series }}
                    </div>
                </div>

                <!-- Product Name -->
                <h1
                    class="font-serif text-3xl leading-tight tracking-tight text-charcoal-900 sm:text-4xl"
                >
                    {{ product.name }}
                </h1>

                <!-- Cosmetic Quick Badge Pills -->

                <!-- Pricing Section with EU Unit Pricing (Directive 98/6/EC) -->
                <div class="py-4">
                    <div class="flex items-baseline gap-3">
                        <span
                            class="text-2xl font-semibold text-charcoal-900 sm:text-3xl"
                        >
                            {{ formatCurrency(currentPrice) }}
                        </span>
                        <span
                            v-if="
                                currentComparePrice &&
                                currentComparePrice > currentPrice
                            "
                            class="text-charcoal-400 text-lg line-through"
                        >
                            {{ formatCurrency(currentComparePrice) }}
                        </span>
                    </div>

                    <!-- EU Unit Pricing line -->
                    <div
                        v-if="product.formatted_unit_price || product.volume"
                        class="text-charcoal-500 mt-1 text-xs font-light"
                    >
                        <span v-if="product.volume"
                            >Net volume: {{ product.volume }}
                            {{ product.unit || 'ml' }}</span
                        >
                        <span
                            v-if="
                                product.volume && product.formatted_unit_price
                            "
                        >
                            &middot;
                        </span>
                        <span
                            v-if="product.formatted_unit_price"
                            class="font-medium text-charcoal-700"
                        >
                            ({{ product.formatted_unit_price }})
                        </span>
                    </div>
                </div>

                <!-- Active Promotions Banner -->
                <div
                    v-if="product.promotions && product.promotions.length > 0"
                    class="space-y-1 rounded-xl border border-sage-200 bg-sage-50 p-4"
                >
                    <div
                        v-for="promo in product.promotions"
                        :key="promo.id"
                        class="flex items-center gap-2 text-xs text-sage-900"
                    >
                        <span
                            class="rounded bg-sage-200 px-2 py-0.5 font-mono font-bold text-sage-800"
                        >
                            {{ promo.code }}
                        </span>
                        <span class="font-medium">{{ promo.name }}</span>
                        <span
                            v-if="promo.discount_type === 'percentage'"
                            class="text-sage-700"
                        >
                            ({{ promo.discount_value }}% off)
                        </span>
                        <span v-else class="text-sage-700">
                            ({{ formatCurrency(promo.discount_value) }} off)
                        </span>
                    </div>
                </div>

                <!-- Interactive Variant / Shade Selector -->
                <div
                    v-if="product.variants && product.variants.length > 0"
                    class="space-y-3 rounded-xl border border-cream-200 bg-cream-50/60 p-4"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span
                            class="font-semibold tracking-wider text-charcoal-800 uppercase"
                        >
                            Choose Shade / Variant:
                        </span>
                        <span
                            v-if="selectedVariant"
                            class="font-medium text-sage-800"
                        >
                            {{ selectedVariant.name }}
                        </span>
                    </div>

                    <!-- Swatches or Pill buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            @click="selectVariant(variant)"
                            class="relative flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
                            :class="
                                selectedVariant?.id === variant.id
                                    ? 'border-sage-700 bg-white text-charcoal-900 shadow-xs ring-2 ring-sage-300'
                                    : 'hover:border-sage-400 border-cream-300 bg-white/80 text-charcoal-700'
                            "
                        >
                            <!-- Color Swatch Circle if color_hex present -->
                            <span
                                v-if="variant.color_hex"
                                class="border-charcoal-200 h-4 w-4 shrink-0 rounded-full border shadow-inner"
                                :style="{ backgroundColor: variant.color_hex }"
                            />
                            <span>{{ variant.name }}</span>
                            <span
                                v-if="!variant.is_in_stock"
                                class="text-[10px] font-normal text-rose-500"
                            >
                                (Out)
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Stock Status -->
                <div class="flex items-center gap-2 text-xs">
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :class="isInStock ? 'bg-emerald-500' : 'bg-rose-500'"
                    />
                    <span
                        v-if="isInStock"
                        class="font-medium text-charcoal-700"
                    >
                        In Stock
                        <span
                            v-if="
                                (selectedVariant?.track_inventory ??
                                    product.track_inventory) &&
                                stockQuantity <= 5
                            "
                            class="ml-1 font-semibold text-amber-700"
                        >
                            (Only {{ stockQuantity }} remaining)
                        </span>
                    </span>
                    <span v-else class="font-medium text-rose-700">
                        Currently Out of Stock
                    </span>
                </div>

                <!-- Short Description -->
                <p
                    v-if="product.short_description"
                    class="text-sm leading-relaxed font-light text-charcoal-700"
                >
                    {{ product.short_description }}
                </p>

                <!-- Targeted Benefits Badges (Skin types, Effects, Active Ingredients, Claims) -->
                <div class="space-y-2 border-t border-cream-200 pt-4">
                    <!-- Skin Types -->
                    <div
                        v-if="
                            product.skin_types && product.skin_types.length > 0
                        "
                        class="flex items-start gap-2 text-xs"
                    >
                        <span class="shrink-0 font-semibold text-charcoal-700"
                            >Skin Type:</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="st in product.skin_types"
                                :key="st.id"
                                class="rounded bg-cream-100 px-2 py-0.5 text-charcoal-700"
                            >
                                {{ st.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Effects & Actions -->
                    <div
                        v-if="product.effects && product.effects.length > 0"
                        class="flex items-start gap-2 text-xs"
                    >
                        <span class="shrink-0 font-semibold text-charcoal-700"
                            >Action:</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="ef in product.effects"
                                :key="ef.id"
                                class="rounded bg-sage-100 px-2 py-0.5 font-medium text-sage-800"
                            >
                                {{ ef.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Active Ingredients -->
                    <div
                        v-if="
                            product.ingredients &&
                            product.ingredients.length > 0
                        "
                        class="flex items-start gap-2 text-xs"
                    >
                        <span class="shrink-0 font-semibold text-charcoal-700"
                            >Actives:</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="ing in product.ingredients"
                                :key="ing.id"
                                class="rounded bg-cream-200/80 px-2 py-0.5 font-medium text-charcoal-800"
                            >
                                {{ ing.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Clean Beauty Claims -->
                    <div
                        v-if="product.claims && product.claims.length > 0"
                        class="flex items-start gap-2 text-xs"
                    >
                        <span class="shrink-0 font-semibold text-charcoal-700"
                            >Claims:</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="cl in product.claims"
                                :key="cl.id"
                                class="rounded-full border border-sage-300 bg-white px-2 py-0.5 text-[11px] font-medium text-sage-800"
                            >
                                {{ cl.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quantity & Add to Cart Action -->
                <div class="space-y-4 border-t border-cream-200 pt-4">
                    <div class="flex items-center gap-4">
                        <!-- Quantity Selector -->
                        <QuantityInput
                            v-model="quantity"
                            :max="maxQuantity"
                            :disabled="!isInStock"
                        />

                        <!-- Add to Cart Button -->
                        <button
                            type="button"
                            @click="addToCart"
                            :disabled="!isInStock"
                            class="flex flex-1 items-center justify-center gap-2 rounded-full px-6 py-3.5 text-xs font-semibold tracking-wider uppercase shadow-sm transition-all sm:text-sm"
                            :class="
                                addedToCartNotice
                                    ? 'bg-emerald-700 text-white shadow-md'
                                    : isInStock
                                      ? 'bg-sage-800 text-cream-50 hover:bg-sage-900 hover:shadow-md active:scale-[0.99]'
                                      : 'text-charcoal-400 cursor-not-allowed bg-cream-200'
                            "
                        >
                            <svg
                                v-if="!addedToCartNotice"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.75"
                                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4.5 12.75l6 6 9-13.5"
                                />
                            </svg>
                            <span>{{
                                addedToCartNotice
                                    ? 'Added to Bag ✓'
                                    : isInStock
                                      ? 'Add to Bag'
                                      : 'Out of Stock'
                            }}</span>
                        </button>

                        <!-- Favourite Toggle Button -->
                        <FavouriteButton
                            :product-id="product.id"
                            :product-name="product.name"
                            size="lg"
                        />
                    </div>
                </div>

                <!-- Botanical Promises & Value Props -->
                <div
                    class="grid grid-cols-2 gap-3 border-t border-cream-200 pt-6 text-xs text-charcoal-700"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base text-sage-700">&bull;</span>
                        <span>Pure botanical actives</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-base text-sage-700">&bull;</span>
                        <span>Cruelty-free & vegan formulas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-base text-sage-700">&bull;</span>
                        <span>Recyclable amber packaging</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-base text-sage-700">&bull;</span>
                        <span>Free shipping on orders over €50</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disclosure Tabs & Full Cosmetic Details (makeup.pl style) -->
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
                <h3 class="font-serif text-xl text-charcoal-900">
                    Botanical Ritual & Care
                </h3>
                <div
                    v-if="product.description"
                    class="space-y-4 text-sm leading-relaxed font-light whitespace-pre-line text-charcoal-700"
                >
                    {{ product.description }}
                </div>
                <p v-else class="text-charcoal-500 text-sm font-light italic">
                    {{
                        product.short_description ||
                        'Detailed botanical description coming soon.'
                    }}
                </p>
            </div>

            <!-- Tab 2: Full INCI Formulation -->
            <div v-show="activeTab === 'inci'" class="max-w-4xl space-y-6 py-8">
                <div>
                    <h3 class="font-serif text-xl text-charcoal-900">
                        Complete Chemical Composition (INCI)
                    </h3>
                    <p class="text-charcoal-500 mt-1 text-xs font-light">
                        Full international nomenclature of cosmetic ingredients
                        as required by EU Regulation (EC) No 1223/2009.
                    </p>
                </div>

                <div
                    class="rounded-xl border border-cream-200 bg-cream-50/80 p-5 font-mono text-xs leading-relaxed whitespace-pre-line text-charcoal-800"
                >
                    {{ product.inci_list }}
                </div>

                <div
                    class="rounded-lg border border-sage-200 bg-sage-50/70 p-4 text-xs text-sage-900"
                >
                    <span class="text-sage-950 font-semibold"
                        >Clean Beauty Notice:</span
                    >
                    Green Veil continuously formulates with verified natural and
                    active cosmetic compounds. Always refer to the ingredient
                    list printed directly on the physical packaging for the most
                    current batch composition.
                </div>
            </div>

            <!-- Tab 3: How to Use -->
            <div
                v-show="activeTab === 'usage'"
                class="max-w-4xl space-y-4 py-8"
            >
                <h3 class="font-serif text-xl text-charcoal-900">
                    Application Ritual
                </h3>
                <div
                    class="space-y-4 text-sm leading-relaxed font-light whitespace-pre-line text-charcoal-700"
                >
                    {{ product.how_to_use }}
                </div>
            </div>

            <!-- Tab 4: Warnings & Safety -->
            <div
                v-show="activeTab === 'warnings'"
                class="max-w-4xl space-y-6 py-8"
            >
                <h3 class="font-serif text-xl text-charcoal-900">
                    Precautions & Regulatory Information
                </h3>

                <div v-if="product.warnings" class="space-y-3">
                    <h4
                        class="text-xs font-semibold tracking-wider text-charcoal-800 uppercase"
                    >
                        Safety Warnings
                    </h4>
                    <div
                        class="text-sm leading-relaxed font-light whitespace-pre-line text-charcoal-700"
                    >
                        {{ product.warnings }}
                    </div>
                </div>

                <div
                    v-if="product.responsible_person"
                    class="rounded-lg border border-cream-200 bg-cream-50 p-4 text-xs text-charcoal-700"
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
                                <td
                                    class="px-4 py-3 text-charcoal-800 capitalize"
                                >
                                    {{ product.classification }}
                                </td>
                            </tr>
                            <tr v-if="product.formula_type">
                                <td
                                    class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                                >
                                    Formula / Texture
                                </td>
                                <td
                                    class="px-4 py-3 text-charcoal-800 capitalize"
                                >
                                    {{ product.formula_type }}
                                </td>
                            </tr>
                            <tr v-if="product.gender">
                                <td
                                    class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                                >
                                    Target Audience
                                </td>
                                <td
                                    class="px-4 py-3 text-charcoal-800 capitalize"
                                >
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
                                <td
                                    class="px-4 py-3 font-mono text-charcoal-800"
                                >
                                    {{ product.barcode }}
                                </td>
                            </tr>
                            <tr v-if="product.sku">
                                <td
                                    class="text-charcoal-600 bg-cream-50/60 px-4 py-3 font-medium"
                                >
                                    SKU
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-charcoal-800"
                                >
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
    </section>

    <!-- Related Products Section -->
    <section
        v-if="relatedProducts.length > 0"
        class="border-t border-cream-200 bg-cream-100/60 py-12 sm:py-16"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-10 max-w-xl text-center">
                <span
                    class="text-xs font-semibold tracking-widest text-sage-700 uppercase"
                >
                    Harmonious Care
                </span>
                <h2
                    class="mt-2 font-serif text-2xl text-charcoal-900 sm:text-3xl"
                >
                    Complete Your Ritual
                </h2>
            </div>

            <ProductGrid :products="relatedProducts" />
        </div>
    </section>
</template>
