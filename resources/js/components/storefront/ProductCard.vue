<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import type { ProductCardData } from '@/types/storefront';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { formatCurrency } from '@/lib/utils';
import { useCart } from '@/composables/useCart';

const props = defineProps<{
    product: ProductCardData;
}>();

const cart = useCart();
const justAdded = ref(false);

const cartItem = computed(() => {
    return cart.items.value.find(
        (item) => item.product_id === props.product.id,
    );
});

const quantityInCart = computed(() => cartItem.value?.quantity ?? 0);

const isMaxStockReached = computed(() => {
    if (!props.product.track_inventory) return false;
    const max = props.product.stock_quantity ?? 99;
    return quantityInCart.value >= max;
});

function addToBag() {
    if (!props.product.is_in_stock) return;
    cart.addItem(props.product, 1);
    justAdded.value = true;
    setTimeout(() => {
        justAdded.value = false;
    }, 800);
}

function incrementInBag() {
    if (!props.product.is_in_stock || isMaxStockReached.value) return;
    cart.updateQuantity(props.product.id, quantityInCart.value + 1);
}

function decrementFromBag() {
    if (quantityInCart.value <= 1) {
        cart.removeItem(props.product.id);
    } else {
        cart.updateQuantity(props.product.id, quantityInCart.value - 1);
    }
}

const formattedPrice = computed(() => formatCurrency(props.product.price));

const formattedComparePrice = computed(() => {
    if (!props.product.compare_at_price) return null;
    return formatCurrency(props.product.compare_at_price);
});
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-2xl border border-cream-200 bg-white transition-all duration-300 hover:shadow-md"
    >
        <!-- Image Area -->
        <div class="relative aspect-square w-full overflow-hidden bg-cream-100">
            <Link
                :href="productShow.url(product.slug)"
                prefetch
                class="block h-full w-full"
            >
                <img
                    :src="product.main_image"
                    :alt="product.name"
                    class="h-full w-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-105"
                    loading="lazy"
                />
            </Link>

            <!-- Badges -->
            <div
                class="pointer-events-none absolute top-3 left-3 z-10 flex flex-col items-start gap-1"
            >
                <span
                    v-if="product.discount_percentage"
                    class="w-fit rounded-full bg-sage-700 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs"
                >
                    -{{ product.discount_percentage }}%
                </span>
                <span
                    v-if="product.variants_count && product.variants_count > 1"
                    class="w-fit border border-cream-300 bg-cream-50/95 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-charcoal-700 uppercase shadow-xs rounded-full"
                >
                    +{{ product.variants_count }} options
                </span>
                <span
                    v-if="!product.is_in_stock"
                    class="w-fit rounded-full bg-charcoal-700 px-2 py-0.5 text-[10px] font-medium tracking-wider text-cream-100 uppercase"
                >
                    Out of Stock
                </span>
            </div>

            <!-- Favourite Toggle -->
            <FavouriteButton
                :product-id="product.id"
                :product-name="product.name"
                class="absolute top-3 right-3 z-10"
            />
        </div>

        <!-- Product Info -->
        <div class="flex grow flex-col justify-between space-y-3 p-5">
            <div class="space-y-1">
                <!-- Brand & Category Label -->
                <div
                    class="flex items-center gap-1.5 truncate text-[11px] font-medium tracking-wider text-sage-600 uppercase"
                >
                    <span
                        v-if="product.brand"
                        class="font-semibold text-charcoal-700"
                    >
                        {{ product.brand.name }}
                    </span>
                </div>

                <div
                    class="flex items-center gap-1.5 truncate text-[9px] font-medium tracking-wider text-sage-600 uppercase"
                >
                    <span v-if="product.category">
                        {{ product.category.name }}
                    </span>
                </div>

                <Link
                    :href="productShow.url(product.slug)"
                    prefetch
                    class="line-clamp-1 font-serif text-base text-charcoal-900 transition-colors group-hover:text-sage-800 sm:text-lg"
                >
                    {{ product.name }}
                </Link>

                <p
                    v-if="product.short_description"
                    class="text-charcoal-500 line-clamp-2 text-xs leading-relaxed font-light"
                >
                    {{ product.short_description }}
                </p>
            </div>

            <div class="space-y-1.5 border-t border-cream-100 pt-2.5">
                <!-- Pricing & Unit Price -->
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span
                                class="text-sm font-semibold text-charcoal-900 sm:text-base"
                            >
                                {{ formattedPrice }}
                            </span>
                            <span
                                v-if="formattedComparePrice"
                                class="text-charcoal-400 text-xs line-through"
                            >
                                {{ formattedComparePrice }}
                            </span>
                        </div>
                        <!-- EU Directive 98/6/EC Unit Price & Volume -->
                        <div class="text-charcoal-400 text-[11px]">
                            <span v-if="product.volume"
                                >{{ product.volume }}
                                {{ product.unit || 'ml' }}</span
                            >
                        </div>

                        <div
                            v-if="
                                product.formatted_unit_price || product.volume
                            "
                            class="text-charcoal-400 text-[11px]"
                        >
                            <span
                                v-if="
                                    product.volume &&
                                    product.formatted_unit_price
                                "
                            >
                            </span>
                            <span v-if="product.formatted_unit_price">{{
                                product.formatted_unit_price
                            }}</span>
                        </div>
                    </div>

                    <!-- Add / Remove Cart Actions -->
                    <div v-if="product.is_in_stock" class="shrink-0">
                        <!-- When not in bag: Add button -->
                        <button
                            v-if="quantityInCart === 0"
                            type="button"
                            @click="addToBag"
                            class="flex h-8 w-8 items-center justify-center rounded-full shadow-xs transition-all focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            :class="
                                justAdded
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-sage-50 text-sage-700 hover:bg-sage-700 hover:text-white'
                            "
                            :aria-label="`Add ${product.name} to bag`"
                            title="Add to bag"
                        >
                            <svg
                                v-if="!justAdded"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.5v15m7.5-7.5h-15"
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
                                    stroke-width="2.5"
                                    d="M4.5 12.75l6 6 9-13.5"
                                />
                            </svg>
                        </button>

                        <!-- When already in bag: Add / Remove Stepper -->
                        <div
                            v-else
                            class="flex items-center rounded-full border border-sage-200 bg-sage-50/90 p-0.5 shadow-xs transition-all"
                        >
                            <!-- Decrement / Remove -->
                            <button
                                type="button"
                                @click="decrementFromBag"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-sage-800 transition-colors focus:outline-none"
                                :class="
                                    quantityInCart === 1
                                        ? 'hover:bg-rose-100 hover:text-rose-700'
                                        : 'hover:bg-sage-200'
                                "
                                :aria-label="
                                    quantityInCart === 1
                                        ? `Remove ${product.name} from bag`
                                        : `Decrease ${product.name} quantity`
                                "
                                :title="
                                    quantityInCart === 1
                                        ? 'Remove from bag'
                                        : 'Decrease quantity'
                                "
                            >
                                <!-- Trash icon if 1, minus icon if > 1 -->
                                <svg
                                    v-if="quantityInCart === 1"
                                    class="h-3.5 w-3.5 text-rose-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19.5 12h-15"
                                    />
                                </svg>
                            </button>

                            <!-- Quantity in Bag -->
                            <span
                                class="min-w-6 px-1.5 text-center font-mono text-xs font-semibold text-charcoal-900"
                            >
                                {{ quantityInCart }}
                            </span>

                            <!-- Increment / Add -->
                            <button
                                type="button"
                                @click="incrementInBag"
                                :disabled="isMaxStockReached"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-sage-800 transition-colors hover:bg-sage-200 hover:text-charcoal-900 focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                                :aria-label="`Add another ${product.name} to bag`"
                                :title="
                                    isMaxStockReached
                                        ? 'Maximum available stock reached'
                                        : 'Add one more'
                                "
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.5v15m7.5-7.5h-15"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
