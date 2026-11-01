<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import ProductGrid from '@/components/storefront/ProductGrid.vue';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import type { ProductDetailPageProps, SharedProps } from '@/types/storefront';
import { store as cartItemsStore } from '@/actions/App/Http/Controllers/CartItemController';
import { show as categoryShow } from '@/actions/App/Http/Controllers/CategoryController';

const props = defineProps<ProductDetailPageProps>();
const page = usePage<SharedProps>();

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
    if (!props.product.track_inventory) return 99;
    return Math.max(1, props.product.stock_quantity);
});

function decrementQuantity() {
    if (quantity.value > 1) {
        quantity.value--;
    }
}

function incrementQuantity() {
    if (quantity.value < maxQuantity.value) {
        quantity.value++;
    }
}

// Add to Cart
const isAddingToCart = ref(false);
function addToCart() {
    if (!props.product.is_in_stock) return;

    isAddingToCart.value = true;
    router.post(
        cartItemsStore.url(),
        {
            product_id: props.product.id,
            quantity: quantity.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isAddingToCart.value = false;
            },
        },
    );
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-IE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
}
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
            :content="(product.price / 100).toFixed(2)"
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

    <StorefrontLayout>
        <!-- Breadcrumbs Navigation -->
        <nav
            aria-label="Breadcrumb"
            class="border-b border-cream-200 bg-cream-100/60"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <ol
                    class="text-charcoal-600 flex items-center space-x-2 text-xs"
                >
                    <li
                        v-for="(crumb, idx) in breadcrumbs"
                        :key="crumb.url"
                        class="flex items-center"
                    >
                        <Link
                            v-if="idx < breadcrumbs.length - 1"
                            :href="crumb.url"
                            class="transition-colors hover:text-sage-800"
                        >
                            {{ crumb.name }}
                        </Link>
                        <span
                            v-else
                            class="max-w-xs truncate font-semibold text-charcoal-900"
                            aria-current="page"
                        >
                            {{ crumb.name }}
                        </span>
                        <svg
                            v-if="idx < breadcrumbs.length - 1"
                            class="text-charcoal-400 mx-2 h-3 w-3 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </li>
                </ol>
            </div>
        </nav>

        <!-- Product Main Showcase -->
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
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
                    <!-- Category & SKU -->
                    <div
                        class="text-charcoal-500 flex items-center justify-between text-xs"
                    >
                        <Link
                            v-if="product.category"
                            :href="categoryShow.url(product.category.slug)"
                            class="font-semibold tracking-widest text-sage-700 uppercase hover:text-sage-900"
                        >
                            {{ product.category.name }}
                        </Link>
                        <span
                            v-if="product.sku"
                            class="text-charcoal-400 font-mono"
                        >
                            SKU: {{ product.sku }}
                        </span>
                    </div>

                    <!-- Product Name -->
                    <h1
                        class="font-serif text-3xl leading-tight tracking-tight text-charcoal-900 sm:text-4xl"
                    >
                        {{ product.name }}
                    </h1>

                    <!-- Pricing Section -->
                    <div class="flex items-baseline gap-3">
                        <span
                            class="text-2xl font-semibold text-charcoal-900 sm:text-3xl"
                        >
                            {{ formatCurrency(product.price) }}
                        </span>
                        <span
                            v-if="
                                product.compare_at_price &&
                                product.compare_at_price > product.price
                            "
                            class="text-charcoal-400 text-lg line-through"
                        >
                            {{ formatCurrency(product.compare_at_price) }}
                        </span>
                    </div>

                    <!-- Active Promotions Banner -->
                    <div
                        v-if="
                            product.promotions && product.promotions.length > 0
                        "
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
                                >({{ promo.discount_value }}% off)</span
                            >
                            <span v-else class="text-sage-700"
                                >({{
                                    formatCurrency(promo.discount_value)
                                }}
                                off)</span
                            >
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="flex items-center gap-2 text-xs">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="
                                product.is_in_stock
                                    ? 'bg-emerald-500'
                                    : 'bg-rose-500'
                            "
                        />
                        <span
                            v-if="product.is_in_stock"
                            class="font-medium text-charcoal-700"
                        >
                            In Stock
                            <span
                                v-if="
                                    product.track_inventory &&
                                    product.stock_quantity <= 5
                                "
                                class="ml-1 text-amber-700"
                            >
                                (Only {{ product.stock_quantity }} remaining)
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

                    <!-- Quantity & Add to Cart Action -->
                    <div class="space-y-4 border-t border-cream-200 pt-4">
                        <div class="flex items-center gap-4">
                            <!-- Quantity Selector -->
                            <div
                                class="flex items-center rounded-full border border-cream-300 bg-white px-2 py-1"
                            >
                                <button
                                    type="button"
                                    @click="decrementQuantity"
                                    :disabled="
                                        !product.is_in_stock || quantity <= 1
                                    "
                                    class="text-charcoal-600 flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:cursor-not-allowed disabled:opacity-30"
                                    aria-label="Decrease quantity"
                                >
                                    &minus;
                                </button>
                                <span
                                    class="w-10 text-center text-xs font-medium text-charcoal-900 select-none sm:text-sm"
                                >
                                    {{ quantity }}
                                </span>
                                <button
                                    type="button"
                                    @click="incrementQuantity"
                                    :disabled="
                                        !product.is_in_stock ||
                                        quantity >= maxQuantity
                                    "
                                    class="text-charcoal-600 flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:cursor-not-allowed disabled:opacity-30"
                                    aria-label="Increase quantity"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Add to Cart Button -->
                            <button
                                type="button"
                                @click="addToCart"
                                :disabled="
                                    !product.is_in_stock || isAddingToCart
                                "
                                class="flex flex-1 items-center justify-center gap-2 rounded-full px-6 py-3.5 text-xs font-semibold tracking-wider uppercase shadow-sm transition-all sm:text-sm"
                                :class="
                                    product.is_in_stock
                                        ? 'bg-sage-800 text-cream-50 hover:bg-sage-900 hover:shadow-md active:scale-[0.99]'
                                        : 'text-charcoal-400 cursor-not-allowed bg-cream-200'
                                "
                            >
                                <svg
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
                                <span>{{
                                    product.is_in_stock
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
                        class="grid grid-cols-2 gap-4 border-t border-cream-200 pt-6 text-xs text-charcoal-700"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-base text-sage-700">&bull;</span>
                            <span>Pure botanical actives</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base text-sage-700">&bull;</span>
                            <span>Cruelty-free & vegan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base text-sage-700">&bull;</span>
                            <span>Recyclable amber packaging</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base text-sage-700">&bull;</span>
                            <span>Free shipping over €50</span>
                        </div>
                    </div>

                    <!-- Detailed Description -->
                    <div
                        v-if="product.description"
                        class="space-y-3 border-t border-cream-200 pt-6"
                    >
                        <h2
                            class="text-xs font-semibold tracking-wider text-charcoal-900 uppercase"
                        >
                            Botanical Ritual & Description
                        </h2>
                        <div
                            class="space-y-3 text-xs leading-relaxed font-light whitespace-pre-line text-charcoal-700 sm:text-sm"
                        >
                            {{ product.description }}
                        </div>
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
                        >Harmonious Care</span
                    >
                    <h2
                        class="mt-2 font-serif text-2xl text-charcoal-900 sm:text-3xl"
                    >
                        Complete Your Ritual
                    </h2>
                </div>

                <ProductGrid :products="relatedProducts" />
            </div>
        </section>
    </StorefrontLayout>
</template>
