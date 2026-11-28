<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ShoppingBagIcon, Trash2Icon } from '@lucide/vue';
import { QuantityInput } from '@/components/form';
import FreeShippingProgress from '@/components/storefront/cart/FreeShippingProgress.vue';
import CartItemSkeleton from '@/components/storefront/skeletons/CartItemSkeleton.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Drawer from '@/components/ui/Drawer.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IconButton from '@/components/ui/IconButton.vue';
import { useCart } from '@/composables/useCart';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import { index as cartIndex } from '@/routes/cart';
import { show as checkoutShow } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as productsIndex } from '@/routes/products';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';

const cart = useCart();

const FREE_SHIPPING_THRESHOLD = 50;

const freeShippingProgress = computed(() =>
    cart.freeShippingProgress(FREE_SHIPPING_THRESHOLD),
);
const qualifiesForFreeShipping = computed(() =>
    cart.qualifiesForFreeShipping(FREE_SHIPPING_THRESHOLD),
);
const amountUntilFreeShipping = computed(() =>
    cart.amountUntilFreeShipping(FREE_SHIPPING_THRESHOLD),
);

const page = usePage();

const isLoading = computed(
    () => !cart.isInitialized.value && cart.isEmpty.value,
);

/** Items saved to the account on another device that this browser has not loaded yet. */
const serverOnlyCount = computed(() =>
    cart.isEmpty.value ? (page.props.cart?.count ?? 0) : 0,
);

function visit(url: string) {
    cart.closeDrawer();
    router.visit(url);
}
</script>

<template>
    <Drawer
        :open="cart.isDrawerOpen.value"
        title="Your bag"
        @close="cart.closeDrawer()"
    >
        <template #title-extra>
            <Badge v-if="cart.count.value > 0">{{ cart.count.value }}</Badge>
        </template>

        <div
            v-if="!cart.isEmpty.value"
            class="border-b border-border px-6 py-4"
        >
            <FreeShippingProgress
                :progress="freeShippingProgress"
                :qualifies="qualifiesForFreeShipping"
                :amount-remaining="amountUntilFreeShipping"
            />
        </div>

        <div v-if="isLoading" class="p-6">
            <CartItemSkeleton :count="3" />
        </div>

        <div v-else-if="serverOnlyCount > 0" class="p-6">
            <EmptyState
                :title="`You have ${serverOnlyCount} ${serverOnlyCount === 1 ? 'item' : 'items'} in your bag`"
                description="Your bag was saved to your account. Open it to see everything."
                class="border-0 px-0"
            >
                <template #icon>
                    <ShoppingBagIcon aria-hidden="true" />
                </template>
                <template #actions>
                    <Button @click="visit(cartIndex.url())">View bag</Button>
                </template>
            </EmptyState>
        </div>

        <div v-else-if="cart.isEmpty.value" class="p-6">
            <EmptyState
                title="Your bag is empty"
                description="Add a few beauty favourites to get started."
                class="border-0 px-0"
            >
                <template #icon>
                    <ShoppingBagIcon aria-hidden="true" />
                </template>
                <template #actions>
                    <Button @click="visit(productsIndex.url())">
                        Start shopping
                    </Button>
                </template>
            </EmptyState>
        </div>

        <ul
            v-else
            class="flex flex-col divide-y divide-border px-6"
            aria-label="Bag items"
        >
            <li
                v-for="item in cart.items.value"
                :key="item.product_id"
                class="flex gap-4 py-4"
            >
                <Link
                    :href="productShow.url(item.slug)"
                    aria-hidden="true"
                    tabindex="-1"
                    class="size-16 shrink-0 overflow-hidden rounded-lg bg-surface-sunken"
                    @click="cart.closeDrawer()"
                >
                    <img
                        v-if="item.main_image"
                        :src="resolveStorageUrl(item.main_image)"
                        alt=""
                        class="size-full object-cover"
                        loading="lazy"
                        @error="showPlaceholderOnError"
                    />
                </Link>

                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <Link
                            :href="productShow.url(item.slug)"
                            class="line-clamp-2 text-sm font-medium text-ink hover:text-rose-800"
                            @click="cart.closeDrawer()"
                        >
                            {{ item.name }}
                        </Link>
                        <p class="text-sm font-semibold text-ink tabular-nums">
                            {{ formatCurrency(item.price * item.quantity) }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <QuantityInput
                            size="sm"
                            :aria-label="`Quantity for ${item.name}`"
                            :model-value="item.quantity"
                            :min="1"
                            :max="
                                item.track_inventory ? item.stock_quantity : 99
                            "
                            @change="
                                (qty) =>
                                    cart.updateQuantity(item.product_id, qty)
                            "
                        />
                        <IconButton
                            :label="`Remove ${item.name}`"
                            size="sm"
                            class="hover:text-danger-600"
                            @click="cart.removeItem(item.product_id)"
                        >
                            <Trash2Icon class="size-4" aria-hidden="true" />
                        </IconButton>
                    </div>
                </div>
            </li>
        </ul>

        <template v-if="!cart.isEmpty.value" #footer>
            <div class="flex flex-col gap-4">
                <div class="flex items-baseline justify-between gap-4">
                    <p class="font-serif text-xl text-ink">Subtotal</p>
                    <p
                        class="text-xl font-semibold text-ink tabular-nums"
                        data-testid="mini-cart-subtotal"
                    >
                        {{ formatCurrency(cart.subtotal.value) }}
                    </p>
                </div>
                <p class="text-xs text-ink-subtle">
                    Delivery and offers are applied at checkout.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <Button variant="outline" @click="visit(cartIndex.url())">
                        View bag
                    </Button>
                    <Button @click="visit(checkoutShow.url())">Checkout</Button>
                </div>
            </div>
        </template>
    </Drawer>
</template>
