<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import { index as cartIndex } from '@/routes/cart';
import { show as checkoutShow } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as productsIndex } from '@/routes/products';
import { show as productShow } from '@/actions/App/Http/Controllers/ProductController';
import CartItemSkeleton from '@/components/storefront/skeletons/CartItemSkeleton.vue';

const cart = useCart();

const FREE_SHIPPING_THRESHOLD = 50;

const freeShippingProgress = computed(() => {
    return cart.freeShippingProgress(FREE_SHIPPING_THRESHOLD);
});

const qualifiesForFreeShipping = computed(() => {
    return cart.qualifiesForFreeShipping(FREE_SHIPPING_THRESHOLD);
});

const amountUntilFreeShipping = computed(() => {
    return cart.amountUntilFreeShipping(FREE_SHIPPING_THRESHOLD);
});

watch(cart.isDrawerOpen, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
});

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && cart.isDrawerOpen.value) {
        cart.closeDrawer();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

function navigateToCheckout() {
    cart.closeDrawer();
    router.visit(checkoutShow.url());
}

function navigateToCart() {
    cart.closeDrawer();
    router.visit(cartIndex.url());
}

function navigateToProducts() {
    cart.closeDrawer();
    router.visit(productsIndex.url());
}
</script>

<template>
    <Teleport to="body">
        <div>
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="cart.isDrawerOpen.value"
                    class="fixed inset-0 z-50 bg-charcoal-900/50 backdrop-blur-xs"
                    @click="cart.closeDrawer"
                    aria-hidden="true"
                />
            </Transition>

            <!-- Slideover Panel -->
            <Transition
                enter-active-class="transform transition duration-300 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transform transition duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="cart.isDrawerOpen.value"
                    class="fixed top-0 right-0 z-50 flex h-full w-full max-w-md flex-col bg-white shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Shopping Bag Slideout"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-cream-200 px-6 py-4"
                    >
                        <div class="flex items-center gap-2">
                            <h2 class="font-serif text-lg text-sage-900">
                                Your Shopping Bag
                            </h2>
                            <span
                                v-if="cart.count.value > 0"
                                class="flex h-5 items-center justify-center rounded-full bg-sage-100 px-2 text-xs font-semibold text-sage-800"
                            >
                                {{ cart.count.value }}
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="cart.closeDrawer"
                            class="text-charcoal-500 -mr-2 flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full p-2 hover:bg-cream-100 hover:text-charcoal-900 focus:outline-none"
                            aria-label="Close bag drawer"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Free Delivery Progress Bar -->
                    <div
                        class="border-b border-cream-100 bg-cream-50/70 px-6 py-3"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span
                                v-if="qualifiesForFreeShipping"
                                class="font-medium text-emerald-800"
                            >
                                🌿 Free Botanical Delivery unlocked!
                            </span>
                            <span v-else class="text-charcoal-700">
                                Add
                                <strong class="font-semibold text-sage-900">
                                    {{
                                        formatCurrency(amountUntilFreeShipping)
                                    }}
                                </strong>
                                for complimentary delivery
                            </span>
                            <span
                                class="text-charcoal-500 font-mono text-[11px]"
                            >
                                {{ freeShippingProgress }}%
                            </span>
                        </div>
                        <div
                            class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-cream-200"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500 ease-out"
                                :class="
                                    qualifiesForFreeShipping
                                        ? 'bg-emerald-600'
                                        : 'bg-sage-600'
                                "
                                :style="{ width: `${freeShippingProgress}%` }"
                            />
                        </div>
                    </div>

                    <!-- Body: Items List, Skeleton, or Empty State -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <!-- Skeleton Loading State -->
                        <div
                            v-if="
                                !cart.isInitialized.value ||
                                (cart.isSyncing.value && cart.isEmpty.value)
                            "
                        >
                            <CartItemSkeleton :count="3" />
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else-if="cart.isEmpty.value"
                            class="flex h-full flex-col items-center justify-center text-center"
                        >
                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sage-50 text-2xl"
                            >
                                🍃
                            </div>
                            <h3 class="font-serif text-lg text-charcoal-900">
                                Your bag is currently empty
                            </h3>
                            <p class="text-charcoal-500 mt-1 max-w-xs text-xs">
                                Discover conscious botanical care handcrafted
                                for your daily ritual.
                            </p>
                            <button
                                type="button"
                                @click="navigateToProducts"
                                class="mt-6 rounded-full bg-sage-800 px-6 py-2.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900"
                            >
                                Explore Formulas
                            </button>
                        </div>

                        <!-- Items List -->
                        <div v-else class="divide-y divide-cream-100">
                            <div
                                v-for="item in cart.items.value"
                                :key="item.product_id"
                                class="flex gap-4 py-4 first:pt-0 last:pb-0"
                            >
                                <!-- Thumbnail -->
                                <div
                                    class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                                >
                                    <img
                                        v-if="item.main_image"
                                        :src="
                                            resolveStorageUrl(item.main_image)
                                        "
                                        :alt="item.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="text-charcoal-400 flex h-full w-full items-center justify-center text-xl"
                                    >
                                        🌿
                                    </div>
                                </div>

                                <!-- Details -->
                                <div
                                    class="flex flex-1 flex-col justify-between"
                                >
                                    <div>
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <Link
                                                :href="
                                                    productShow.url(item.slug)
                                                "
                                                @click="cart.closeDrawer"
                                                class="font-serif text-sm font-normal text-charcoal-900 hover:text-sage-800"
                                            >
                                                {{ item.name }}
                                            </Link>
                                            <span
                                                class="text-xs font-semibold text-charcoal-900"
                                            >
                                                {{
                                                    formatCurrency(
                                                        item.price *
                                                            item.quantity,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="item.volume"
                                            class="text-charcoal-500 text-[11px]"
                                        >
                                            {{ item.volume }}
                                            {{ item.unit || 'ml' }}
                                        </p>
                                    </div>

                                    <!-- Stepper and Actions -->
                                    <div
                                        class="mt-2 flex items-center justify-between"
                                    >
                                        <!-- Quantity Stepper -->
                                        <div
                                            class="inline-flex items-center rounded-lg border border-cream-200 bg-cream-50/80 p-0.5"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    cart.updateQuantity(
                                                        item.product_id,
                                                        item.quantity - 1,
                                                    )
                                                "
                                                class="flex h-6 w-6 items-center justify-center rounded text-charcoal-700 hover:bg-white"
                                                aria-label="Decrease quantity"
                                            >
                                                -
                                            </button>
                                            <span
                                                class="w-6 text-center text-xs font-medium text-charcoal-900"
                                            >
                                                {{ item.quantity }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="
                                                    cart.updateQuantity(
                                                        item.product_id,
                                                        item.quantity + 1,
                                                    )
                                                "
                                                class="flex h-6 w-6 items-center justify-center rounded text-charcoal-700 hover:bg-white"
                                                aria-label="Increase quantity"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <!-- Remove button -->
                                        <button
                                            type="button"
                                            @click="
                                                cart.removeItem(item.product_id)
                                            "
                                            class="text-charcoal-400 hover:text-red-700"
                                            aria-label="Remove item"
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
                                                    stroke-width="1.5"
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: Summary & Checkout CTA -->
                    <div
                        v-if="!cart.isEmpty.value"
                        class="space-y-3 border-t border-cream-200 bg-cream-50/60 p-6"
                    >
                        <!-- Subtotal -->
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-charcoal-600">Subtotal</span>
                            <span
                                class="font-serif text-lg font-semibold text-charcoal-900"
                            >
                                {{ formatCurrency(cart.subtotal.value) }}
                            </span>
                        </div>
                        <p class="text-charcoal-500 text-[11px]">
                            Shipping and taxes calculated at checkout.
                        </p>

                        <!-- Primary Checkout Button -->
                        <button
                            type="button"
                            @click="navigateToCheckout"
                            class="flex min-h-[48px] w-full items-center justify-center rounded-full bg-sage-800 px-6 py-3 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        >
                            Proceed to Checkout →
                        </button>

                        <!-- Secondary View Bag Link -->
                        <div class="text-center">
                            <button
                                type="button"
                                @click="navigateToCart"
                                class="text-xs font-medium text-sage-800 hover:underline"
                            >
                                View full bag & promotions
                            </button>
                        </div>
                    </div>
                </aside>
            </Transition>
        </div>
    </Teleport>
</template>
