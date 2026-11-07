<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { QuantityInput } from '@/components/form';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import ProductBenefitsBadges from '@/components/storefront/product/ProductBenefitsBadges.vue';
import ProductDisclosureTabs from '@/components/storefront/product/ProductDisclosureTabs.vue';
import ProductGallery from '@/components/storefront/product/ProductGallery.vue';
import ProductVariantSelector from '@/components/storefront/product/ProductVariantSelector.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import { useCart } from '@/composables/useCart';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { formatCurrency } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type {
    ProductDetailPageProps,
    ProductVariantItem,
} from '@/types/storefront';

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

const activeImageIndex = ref(0);

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

// Gallery images compilation
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

function selectVariant(variant: ProductVariantItem) {
    selectedVariant.value = variant;
    if (!variant.image) return;

    const foundIdx = allImages.value.findIndex(
        (img) => img.path === variant.image,
    );
    if (foundIdx !== -1) {
        activeImageIndex.value = foundIdx;
    }
}

// Quantity & Cart state
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

const showStickyBar = ref(false);
const mainCtaRef = ref<HTMLElement | null>(null);

let observer: IntersectionObserver | null = null;

onMounted(() => {
    if (typeof IntersectionObserver !== 'undefined' && mainCtaRef.value) {
        observer = new IntersectionObserver(
            ([entry]) => {
                showStickyBar.value =
                    !entry.isIntersecting && entry.boundingClientRect.top < 0;
            },
            { threshold: 0 },
        );
        observer.observe(mainCtaRef.value);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
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
            v-if="allImages[activeImageIndex]"
            property="og:image"
            :content="allImages[activeImageIndex].path"
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
            <ProductGallery
                :images="allImages"
                v-model:active-index="activeImageIndex"
                :product-name="product.name"
                :discount-percentage="product.discount_percentage"
            />

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

                <!-- Pricing Section with EU Unit Pricing -->
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
                        <span v-if="product.volume">
                            Net volume: {{ product.volume }}
                            {{ product.unit || 'ml' }}
                        </span>
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

                <!-- Interactive Variant / Shade Selector & Stock Status -->
                <ProductVariantSelector
                    :variants="product.variants"
                    :selected-variant="selectedVariant"
                    :is-in-stock="isInStock"
                    :track-inventory="
                        selectedVariant?.track_inventory ??
                        product.track_inventory
                    "
                    :stock-quantity="stockQuantity"
                    @select="selectVariant"
                />

                <!-- Short Description -->
                <p
                    v-if="product.short_description"
                    class="text-charcoal-600 text-sm leading-relaxed font-light"
                >
                    {{ product.short_description }}
                </p>

                <!-- Targeted Benefits Badges (Skin types, Effects, Active Ingredients, Claims) -->
                <ProductBenefitsBadges
                    :skin-types="product.skin_types"
                    :effects="product.effects"
                    :ingredients="product.ingredients"
                    :claims="product.claims"
                />

                <!-- Quantity & Add to Cart Action -->
                <div
                    ref="mainCtaRef"
                    class="space-y-4 border-t border-cream-200 pt-4"
                >
                    <div class="flex items-center gap-4">
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

        <!-- Disclosure Tabs & Full Cosmetic Details -->
        <ProductDisclosureTabs
            :product="product"
            :selected-variant="selectedVariant"
        />
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

    <!-- Mobile Sticky Add to Bag Bar -->
    <Transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-y-full"
        enter-to-class="translate-y-0"
        leave-active-class="transform transition ease-in duration-200"
        leave-from-class="translate-y-0"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="showStickyBar"
            class="fixed right-0 bottom-0 left-0 z-30 border-t border-cream-200 bg-white/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-lg backdrop-blur-md lg:hidden"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p
                        class="truncate font-serif text-sm font-medium text-charcoal-900"
                    >
                        {{ product.name }}
                    </p>
                    <p class="text-xs font-semibold text-charcoal-900">
                        {{ formatCurrency(currentPrice) }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="addToCart"
                    :disabled="!isInStock"
                    class="flex min-h-[44px] shrink-0 items-center justify-center gap-1.5 rounded-full px-5 py-2.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors"
                    :class="
                        addedToCartNotice
                            ? 'bg-emerald-700'
                            : isInStock
                              ? 'bg-sage-800 hover:bg-sage-900 active:scale-95'
                              : 'text-charcoal-400 cursor-not-allowed bg-cream-300'
                    "
                >
                    {{
                        addedToCartNotice
                            ? 'Added ✓'
                            : isInStock
                              ? 'Add to Bag'
                              : 'Out of Stock'
                    }}
                </button>
            </div>
        </div>
    </Transition>
</template>
