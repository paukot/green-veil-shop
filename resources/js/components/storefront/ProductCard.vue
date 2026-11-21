<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import FavouriteButton from '@/components/storefront/FavouriteButton.vue';
import type { ProductCardData } from '@/types/storefront';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import Badge from '@/components/ui/Badge.vue';
import IconButton from '@/components/ui/IconButton.vue';
import Price from '@/components/ui/Price.vue';
import { showPlaceholderOnError } from '@/lib/utils';
import { useCart } from '@/composables/useCart';
import { CheckIcon, MinusIcon, PlusIcon, Trash2Icon } from '@lucide/vue';

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
    if (justAddedTimer) clearTimeout(justAddedTimer);
    justAddedTimer = setTimeout(() => {
        justAdded.value = false;
    }, 800);
}

let justAddedTimer: ReturnType<typeof setTimeout> | null = null;
onUnmounted(() => {
    if (justAddedTimer) clearTimeout(justAddedTimer);
});

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
</script>

<template>
    <article class="group relative flex flex-col gap-4">
        <!-- Image -->
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-surface-sunken"
        >
            <Link
                :href="productShow.url(product.slug)"
                prefetch
                class="block size-full"
                tabindex="-1"
                aria-hidden="true"
            >
                <img
                    :src="product.main_image"
                    :alt="product.name"
                    class="size-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                    loading="lazy"
                    @error="showPlaceholderOnError"
                />
            </Link>

            <div
                class="pointer-events-none absolute top-3 left-3 flex flex-col items-start gap-2"
            >
                <Badge
                    v-if="product.discount_percentage"
                    tone="inverse"
                    class="bg-clay-700"
                >
                    −{{ product.discount_percentage }}%
                </Badge>
                <Badge v-if="!product.is_in_stock" tone="neutral">
                    Sold out
                </Badge>
                <Badge
                    v-else-if="
                        product.variants_count && product.variants_count > 1
                    "
                    tone="neutral"
                    class="bg-surface-raised/90"
                >
                    {{ product.variants_count }} shades
                </Badge>
            </div>

            <FavouriteButton
                :product-id="product.id"
                :product-name="product.name"
                class="absolute top-3 right-3"
            />
        </div>

        <!-- Details -->
        <div class="flex flex-1 flex-col gap-2">
            <p
                v-if="product.brand || product.category"
                class="truncate text-xs font-semibold tracking-wider text-ink-subtle uppercase"
            >
                {{ product.brand?.name ?? product.category?.name }}
            </p>

            <h3 class="font-serif text-lg leading-6 text-ink">
                <Link
                    :href="productShow.url(product.slug)"
                    prefetch
                    class="line-clamp-2 transition-colors hover:text-sage-800"
                >
                    {{ product.name }}
                </Link>
            </h3>

            <div class="mt-auto flex items-end justify-between gap-2 pt-2">
                <div class="flex min-w-0 flex-col gap-1">
                    <Price
                        :amount="product.price"
                        :compare-at="product.compare_at_price"
                    />
                    <p
                        v-if="product.volume || product.formatted_unit_price"
                        class="truncate text-xs text-ink-subtle"
                    >
                        <span v-if="product.volume">
                            {{ product.volume }} {{ product.unit || 'ml' }}
                        </span>
                        <span
                            v-if="
                                product.volume && product.formatted_unit_price
                            "
                        >
                            ·
                        </span>
                        <span v-if="product.formatted_unit_price">
                            {{ product.formatted_unit_price }}
                        </span>
                    </p>
                </div>

                <template v-if="product.is_in_stock">
                    <IconButton
                        v-if="quantityInCart === 0"
                        :label="`Add ${product.name} to bag`"
                        :variant="justAdded ? 'solid' : 'outline'"
                        @click="addToBag"
                    >
                        <CheckIcon
                            v-if="justAdded"
                            class="size-4"
                            aria-hidden="true"
                        />
                        <PlusIcon v-else class="size-4" aria-hidden="true" />
                    </IconButton>

                    <div
                        v-else
                        class="flex h-12 shrink-0 items-center rounded-full bg-sage-900 text-cream-50"
                        role="group"
                        :aria-label="`${product.name} in bag`"
                    >
                        <button
                            type="button"
                            class="flex size-12 items-center justify-center rounded-full transition-colors hover:bg-sage-800"
                            :aria-label="
                                quantityInCart === 1
                                    ? `Remove ${product.name} from bag`
                                    : `Decrease ${product.name} quantity`
                            "
                            @click="decrementFromBag"
                        >
                            <Trash2Icon
                                v-if="quantityInCart === 1"
                                class="size-4"
                                aria-hidden="true"
                            />
                            <MinusIcon
                                v-else
                                class="size-4"
                                aria-hidden="true"
                            />
                        </button>
                        <span
                            class="w-6 text-center text-sm font-semibold tabular-nums"
                            aria-live="polite"
                        >
                            {{ quantityInCart }}
                        </span>
                        <button
                            type="button"
                            class="flex size-12 items-center justify-center rounded-full transition-colors hover:bg-sage-800 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="isMaxStockReached"
                            :aria-label="`Add another ${product.name} to bag`"
                            @click="incrementInBag"
                        >
                            <PlusIcon class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </article>
</template>
