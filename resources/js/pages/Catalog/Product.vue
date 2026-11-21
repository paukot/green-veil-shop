<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, WhenVisible } from '@inertiajs/vue3';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';
import { QuantityInput } from '@/components/form';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import ProductBenefitsBadges from '@/components/storefront/product/ProductBenefitsBadges.vue';
import ProductDisclosureTabs from '@/components/storefront/product/ProductDisclosureTabs.vue';
import ProductGallery from '@/components/storefront/product/ProductGallery.vue';
import ProductVariantSelector from '@/components/storefront/product/ProductVariantSelector.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import ProductGridSkeleton from '@/components/storefront/skeletons/ProductGridSkeleton.vue';
import { useCart } from '@/composables/useCart';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { formatDiscount } from '@/lib/formatters';
import { formatCurrency } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type {
    ProductDetailPageProps,
    ProductVariantItem,
} from '@/types/storefront';
import {
    CheckIcon,
    LeafIcon,
    RabbitIcon,
    RecycleIcon,
    Share2Icon,
    ShoppingBagIcon,
    TruckIcon,
} from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Container from '@/components/ui/Container.vue';
import IconButton from '@/components/ui/IconButton.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Price from '@/components/ui/Price.vue';
import Section from '@/components/ui/Section.vue';

defineOptions({
    layout: StorefrontLayout,
});

const promises = [
    { icon: LeafIcon, label: 'Pure botanical actives' },
    { icon: RabbitIcon, label: 'Cruelty-free & vegan' },
    { icon: RecycleIcon, label: 'Recyclable packaging' },
    { icon: TruckIcon, label: 'Free delivery over €50' },
];

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
        true,
    );

    addedToCartNotice.value = true;
    setTimeout(() => {
        addedToCartNotice.value = false;
    }, 2000);
}

const shareCopied = ref(false);

async function shareProduct() {
    if (typeof navigator !== 'undefined' && navigator.share) {
        try {
            await navigator.share({
                title: `${props.product.name} | Green Veil`,
                text:
                    props.product.short_description ||
                    `Discover ${props.product.name} at Green Veil Botanical Apothecary.`,
                url: window.location.href,
            });
            return;
        } catch {
            // fallback to clipboard
        }
    }

    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        await navigator.clipboard.writeText(window.location.href);
        shareCopied.value = true;
        setTimeout(() => {
            shareCopied.value = false;
        }, 2200);
    }
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
    <Head :title="`${product.name}`">
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

    <Breadcrumbs :items="breadcrumbs" />

    <Container class="py-8 pb-24 lg:py-12 lg:pb-16">
        <div
            class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-16"
        >
            <ProductGallery
                class="min-w-0 lg:col-span-7"
                :images="allImages"
                v-model:active-index="activeImageIndex"
                :product-name="product.name"
                :discount-percentage="product.discount_percentage"
            />

            <div
                class="flex min-w-0 flex-col gap-8 lg:sticky lg:top-sticky lg:col-span-5"
            >
                <!-- Brand, title and price -->
                <div class="flex flex-col gap-4">
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold tracking-wider uppercase"
                    >
                        <Link
                            v-if="product.brand"
                            :href="
                                productsIndex.url({
                                    query: { brands: product.brand.slug },
                                })
                            "
                            class="text-clay-600 hover:underline"
                        >
                            {{ product.brand.name }}
                        </Link>
                        <Link
                            v-if="product.category"
                            :href="categoryShow.url(product.category.slug)"
                            class="text-ink-subtle hover:text-ink"
                        >
                            {{ product.category.name }}
                        </Link>
                    </div>

                    <h1
                        class="font-serif text-4xl tracking-tight text-balance text-ink lg:text-5xl"
                    >
                        {{ product.name }}
                    </h1>

                    <p v-if="product.series" class="text-sm text-ink-muted">
                        {{ product.series }} collection
                    </p>

                    <div class="flex flex-col gap-1">
                        <Price
                            :amount="currentPrice"
                            :compare-at="currentComparePrice"
                            size="lg"
                        />
                        <p
                            v-if="
                                product.formatted_unit_price || product.volume
                            "
                            class="text-xs text-ink-subtle"
                        >
                            <span v-if="product.volume">
                                {{ product.volume }} {{ product.unit || 'ml' }}
                            </span>
                            <span
                                v-if="
                                    product.volume &&
                                    product.formatted_unit_price
                                "
                            >
                                ·
                            </span>
                            <span v-if="product.formatted_unit_price">
                                {{ product.formatted_unit_price }}
                            </span>
                            <span v-if="product.sku">
                                · SKU {{ selectedVariant?.sku || product.sku }}
                            </span>
                        </p>
                    </div>

                    <p
                        v-if="product.short_description"
                        class="text-base text-pretty text-ink-muted"
                    >
                        {{ product.short_description }}
                    </p>
                </div>

                <!-- Offers -->
                <ul
                    v-if="product.promotions && product.promotions.length > 0"
                    class="flex flex-col gap-2 rounded-2xl bg-clay-50 p-4"
                >
                    <li
                        v-for="promo in product.promotions"
                        :key="promo.id"
                        class="flex flex-wrap items-center gap-2 text-sm text-ink"
                    >
                        <Badge
                            v-if="promo.code"
                            tone="accent"
                            class="font-mono"
                        >
                            {{ promo.code }}
                        </Badge>
                        <span class="font-medium">{{ promo.name }}</span>
                        <span class="text-ink-muted">
                            {{
                                formatDiscount(
                                    promo.discount_type,
                                    promo.discount_value,
                                )
                            }}
                            off
                        </span>
                    </li>
                </ul>

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

                <!-- Purchase -->
                <div ref="mainCtaRef" class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <QuantityInput
                            v-model="quantity"
                            :max="maxQuantity"
                            :disabled="!isInStock"
                        />
                        <Button
                            class="min-w-0 flex-1"
                            :variant="
                                addedToCartNotice ? 'secondary' : 'primary'
                            "
                            :disabled="!isInStock"
                            @click="addToCart"
                        >
                            <template #icon>
                                <CheckIcon
                                    v-if="addedToCartNotice"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                <ShoppingBagIcon
                                    v-else
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </template>
                            {{
                                addedToCartNotice
                                    ? 'Added to bag'
                                    : isInStock
                                      ? 'Add to bag'
                                      : 'Sold out'
                            }}
                        </Button>
                        <FavouriteButton
                            :product-id="product.id"
                            :product-name="product.name"
                            variant="icon"
                            class="border border-border-strong"
                        />
                        <IconButton
                            :label="
                                shareCopied ? 'Link copied' : 'Share product'
                            "
                            variant="outline"
                            class="hidden sm:inline-flex"
                            @click="shareProduct"
                        >
                            <CheckIcon
                                v-if="shareCopied"
                                class="size-4 text-success-700"
                                aria-hidden="true"
                            />
                            <Share2Icon
                                v-else
                                class="size-4"
                                aria-hidden="true"
                            />
                        </IconButton>
                    </div>
                </div>

                <ProductBenefitsBadges
                    :skin-types="product.skin_types"
                    :effects="product.effects"
                    :ingredients="product.ingredients"
                    :claims="product.claims"
                />

                <ul class="grid grid-cols-2 gap-4 border-t border-border pt-8">
                    <li
                        v-for="promise in promises"
                        :key="promise.label"
                        class="flex items-center gap-3 text-sm text-ink-muted"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-800"
                        >
                            <component
                                :is="promise.icon"
                                class="size-4"
                                aria-hidden="true"
                            />
                        </span>
                        {{ promise.label }}
                    </li>
                </ul>
            </div>
        </div>

        <ProductDisclosureTabs
            :product="product"
            :selected-variant="selectedVariant"
        />
    </Container>

    <Section
        v-if="!relatedProducts || relatedProducts.length > 0"
        tone="sunken"
    >
        <div class="flex flex-col gap-8 lg:gap-12">
            <PageHeader
                level="section"
                eyebrow="Pairs well with"
                title="Complete your ritual"
            />
            <WhenVisible data="relatedProducts">
                <template #fallback>
                    <ProductGridSkeleton :count="4" />
                </template>
                <ProductGrid
                    v-if="relatedProducts && relatedProducts.length > 0"
                    :products="relatedProducts"
                />
            </WhenVisible>
        </div>
    </Section>

    <!-- Mobile buy bar -->
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-y-full"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="showStickyBar"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-surface-raised/95 pb-[env(safe-area-inset-bottom)] shadow-lg backdrop-blur-md lg:hidden"
        >
            <Container class="flex h-16 items-center justify-between gap-4">
                <div class="flex min-w-0 flex-col">
                    <p class="truncate text-sm font-medium text-ink">
                        {{ product.name }}
                    </p>
                    <p class="text-sm font-semibold text-ink tabular-nums">
                        {{ formatCurrency(currentPrice) }}
                    </p>
                </div>
                <Button
                    size="sm"
                    :variant="addedToCartNotice ? 'secondary' : 'primary'"
                    :disabled="!isInStock"
                    @click="addToCart"
                >
                    {{
                        addedToCartNotice
                            ? 'Added'
                            : isInStock
                              ? 'Add to bag'
                              : 'Sold out'
                    }}
                </Button>
            </Container>
        </div>
    </Transition>
</template>
