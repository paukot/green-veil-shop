<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import type { ProductCardData } from '@/types/storefront';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import { store as cartItemsStore } from '@/actions/App/Http/Controllers/CartItemController';
import { formatCurrency } from '@/lib/utils';

const props = defineProps<{
    product: ProductCardData;
}>();

const isAdding = ref(false);
function addToBag() {
    if (!props.product.is_in_stock || isAdding.value) return;
    isAdding.value = true;
    router.post(
        cartItemsStore.url(),
        { product_id: props.product.id, quantity: 1 },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isAdding.value = false;
            },
        },
    );
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
                class="pointer-events-none absolute top-3 left-3 z-10 flex flex-col gap-1.5"
            >
                <span
                    v-if="product.discount_percentage"
                    class="rounded-full bg-sage-700 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs"
                >
                    -{{ product.discount_percentage }}%
                </span>
                <span
                    v-if="product.variants_count && product.variants_count > 1"
                    class="rounded-full bg-cream-50/95 border border-cream-300 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-charcoal-700 uppercase shadow-xs"
                >
                    +{{ product.variants_count }} options
                </span>
                <span
                    v-if="!product.is_in_stock"
                    class="rounded-full bg-charcoal-700 px-2 py-0.5 text-[10px] font-medium tracking-wider text-cream-100 uppercase"
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
                <div class="flex items-center gap-1.5 text-[11px] font-medium tracking-wider uppercase text-sage-600 truncate">
                    <span v-if="product.brand" class="font-semibold text-charcoal-700">
                        {{ product.brand.name }}
                    </span>
                    <span v-if="product.brand && product.category" class="text-charcoal-300">&bull;</span>
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
                            <span class="text-sm font-semibold text-charcoal-900 sm:text-base">
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
                        <div
                            v-if="product.formatted_unit_price || product.volume"
                            class="text-[11px] text-charcoal-400"
                        >
                            <span v-if="product.volume">{{ product.volume }} {{ product.unit || 'ml' }}</span>
                            <span v-if="product.volume && product.formatted_unit_price"> &middot; </span>
                            <span v-if="product.formatted_unit_price">{{ product.formatted_unit_price }}</span>
                        </div>
                    </div>

                    <!-- Add to Bag Action -->
                    <button
                        v-if="product.is_in_stock"
                        type="button"
                        @click="addToBag"
                        :disabled="isAdding"
                        class="rounded-full bg-sage-50 p-2 text-sage-700 shadow-xs transition-all hover:bg-sage-700 hover:text-white focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :aria-label="`Add ${product.name} to bag`"
                    >
                        <svg
                            v-if="!isAdding"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.75"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        <LoadingSpinner v-else size="sm" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
