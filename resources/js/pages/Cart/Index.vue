<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { show as checkoutShow } from '@/actions/App/Http/Controllers/CheckoutController';
import CartEmptyState from '@/components/storefront/cart/CartEmptyState.vue';
import CartItemList from '@/components/storefront/cart/CartItemList.vue';
import CartPromotionCard from '@/components/storefront/cart/CartPromotionCard.vue';
import CartSummaryCard from '@/components/storefront/cart/CartSummaryCard.vue';
import SavedForLaterList from '@/components/storefront/cart/SavedForLaterList.vue';
import { useCart } from '@/composables/useCart';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CartPageProps } from '@/types/storefront';
import { RefreshCwIcon, ShieldCheckIcon } from '@lucide/vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps<CartPageProps>();

const cart = useCart();
const isNavigatingToCheckout = ref(false);

const promoError = ref<string | null>(null);
const promoSuccess = ref<string | null>(null);
const isApplyingPromo = ref(false);

onMounted(() => {
    cart.initFromServer(
        props.items,
        props.appliedPromotion,
        props.availablePromotions,
    );
});

const availablePromotions = computed(() => {
    return cart.availablePromotions.value.length > 0
        ? cart.availablePromotions.value
        : (props.availablePromotions ?? []);
});

const appliedPromotion = computed(() => {
    return cart.appliedPromotion.value ?? props.appliedPromotion ?? null;
});

async function handleApplyPromoCode(code: string) {
    if (!code || isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const result = await cart.applyPromotion({ code });
    isApplyingPromo.value = false;

    if (result.success) {
        promoSuccess.value = result.message || 'Promotion applied!';
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
        return;
    }
    promoError.value = result.error || 'Failed to apply promotion';
}

async function handleApplyPromoOffer(promoId: number) {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const result = await cart.applyPromotion({ promotion_id: promoId });
    isApplyingPromo.value = false;

    if (result.success) {
        promoSuccess.value = result.message || 'Promotion applied!';
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
        return;
    }
    promoError.value = result.error || 'Failed to apply promotion';
}

async function handleRemovePromo() {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    await cart.removePromotion();
    isApplyingPromo.value = false;
}

const freeShippingThreshold = computed(() => {
    return props.summary.free_shipping_threshold > 0
        ? props.summary.free_shipping_threshold
        : 50;
});

const freeShippingProgress = computed(() => {
    return cart.freeShippingProgress(freeShippingThreshold.value);
});

const qualifiesForFreeShipping = computed(() => {
    return cart.qualifiesForFreeShipping(freeShippingThreshold.value);
});

const amountUntilFreeShipping = computed(() => {
    return cart.amountUntilFreeShipping(freeShippingThreshold.value);
});

const computedDeliveryOptions = computed(() => {
    return props.deliveryOptions.map((option) => ({
        ...option,
        is_free: Boolean(
            option.free_threshold &&
            cart.subtotal.value >= option.free_threshold,
        ),
    }));
});

function dismissWarnings() {
    cart.syncWarnings.value = [];
}

async function proceedToCheckout() {
    if (cart.isEmpty.value || isNavigatingToCheckout.value) return;
    isNavigatingToCheckout.value = true;
    try {
        await cart.flushSync();
        router.visit(checkoutShow.url());
    } catch {
        isNavigatingToCheckout.value = false;
    }
}
</script>

<template>
    <Head title="Your Shopping Bag | Green Veil" />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
        <div
            v-if="!cart.isEmpty.value"
            class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-12"
        >
            <!-- Cart Items List (8 cols) -->
            <div class="space-y-6 lg:col-span-8">
                <CartItemList
                    :items="cart.items.value"
                    :warnings="cart.syncWarnings.value"
                    :free-shipping-progress="freeShippingProgress"
                    :qualifies-for-free-shipping="qualifiesForFreeShipping"
                    :amount-until-free-shipping="amountUntilFreeShipping"
                    @dismiss-warnings="dismissWarnings"
                    @update-quantity="cart.updateQuantity"
                    @remove-item="cart.removeItem"
                    @save-for-later="cart.saveForLater"
                />

                <SavedForLaterList
                    :items="cart.savedItems.value"
                    @move-to-cart="cart.moveToCart"
                    @remove-item="cart.removeSavedItem"
                />
            </div>

            <!-- Order Summary Sidebar (4 cols) -->
            <div class="space-y-6 lg:sticky lg:top-32 lg:col-span-4">
                <CartSummaryCard
                    :item-count="cart.count.value"
                    :subtotal="cart.subtotal.value"
                    :discount-amount="cart.discountAmount.value"
                    :total-after-discount="cart.totalAfterDiscount.value"
                    :applied-promotion="appliedPromotion"
                    :qualifies-for-free-shipping="qualifiesForFreeShipping"
                    :delivery-options="computedDeliveryOptions"
                    :is-syncing="cart.isSyncing.value"
                    :is-navigating-to-checkout="isNavigatingToCheckout"
                    :is-empty="cart.isEmpty.value"
                    @checkout="proceedToCheckout"
                />

                <CartPromotionCard
                    :applied-promotion="appliedPromotion"
                    :available-promotions="availablePromotions"
                    :is-applying="isApplyingPromo"
                    :error="promoError"
                    :success="promoSuccess"
                    @apply-code="handleApplyPromoCode"
                    @apply-offer="handleApplyPromoOffer"
                    @remove="handleRemovePromo"
                />

                <!-- Trust Signals -->
                <div
                    class="text-charcoal-600 space-y-2 rounded-xl border border-cream-200 bg-cream-100/60 p-4 text-xs"
                >
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon
                            class="h-4 w-4 shrink-0 text-sage-700"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span>Encrypted 256-bit secure checkout</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <RefreshCwIcon
                            class="h-4 w-4 shrink-0 text-sage-700"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span>30-day conscious returns & exchanges</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Cart State -->
        <div v-else class="space-y-10">
            <CartEmptyState />
            <div
                v-if="cart.savedItems.value.length > 0"
                class="mx-auto max-w-4xl"
            >
                <SavedForLaterList
                    :items="cart.savedItems.value"
                    @move-to-cart="cart.moveToCart"
                    @remove-item="cart.removeSavedItem"
                />
            </div>
        </div>
    </section>
</template>
