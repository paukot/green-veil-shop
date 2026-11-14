<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioCard,
    RadioCardGroup,
    TextField,
    TextareaField,
} from '@/components/form';
import type { SelectOption } from '@/components/form';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import AddressFormFields from '@/components/storefront/checkout/AddressFormFields.vue';
import CheckoutOrderSummary from '@/components/storefront/checkout/CheckoutOrderSummary.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type {
    AppliedPromotionData,
    AvailablePromotionData,
    CheckoutPageProps,
    SavedAddressItem,
} from '@/types/storefront';
import { store as checkoutStore } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as cartIndex } from '@/routes/cart';
import { login } from '@/routes';
import { useCart } from '@/composables/useCart';

defineOptions({ layout: StorefrontLayout });

const mobileSummaryOpen = ref(false);

const props = defineProps<CheckoutPageProps>();

const countryOptions = computed<SelectOption[]>(() => {
    if (props.countries && props.countries.length > 0) {
        return props.countries;
    }
    return [{ value: 'IE', label: 'Ireland' }];
});

const defaultDelivery =
    props.deliveryOptions.find((d) => d.is_free) ?? props.deliveryOptions[0];
const defaultPayment = props.paymentOptions[0];
const defaultCountry = props.countries?.[0]?.value ?? 'IE';

const defaultSavedAddress = computed<SavedAddressItem | null>(() => {
    if (!props.savedAddresses || props.savedAddresses.length === 0) {
        return null;
    }
    return (
        props.savedAddresses.find((a) => a.is_default) ??
        props.savedAddresses[0]
    );
});

const form = useForm({
    customer_name:
        defaultSavedAddress.value?.recipient_name ?? props.customer?.name ?? '',
    customer_email: props.customer?.email ?? '',
    customer_phone: defaultSavedAddress.value?.phone ?? '',
    delivery_option_id: defaultDelivery ? defaultDelivery.id : 0,
    payment_option_id: defaultPayment ? defaultPayment.id : 0,
    shipping_address: {
        line1: defaultSavedAddress.value?.line1 ?? '',
        line2: defaultSavedAddress.value?.line2 ?? '',
        city: defaultSavedAddress.value?.city ?? '',
        postal_code: defaultSavedAddress.value?.postal_code ?? '',
        country: defaultSavedAddress.value?.country ?? defaultCountry,
    },
    billing_same_as_shipping: true,
    billing_address: {
        line1: defaultSavedAddress.value?.line1 ?? '',
        line2: defaultSavedAddress.value?.line2 ?? '',
        city: defaultSavedAddress.value?.city ?? '',
        postal_code: defaultSavedAddress.value?.postal_code ?? '',
        country: defaultSavedAddress.value?.country ?? defaultCountry,
    },
    notes: '',
    promotion_id: props.appliedPromotion?.id ?? null,
    selected_address_id: defaultSavedAddress.value?.id ?? null,
    save_address_to_profile: false,
    address_label: '',
});

function selectSavedAddress(address: SavedAddressItem) {
    form.selected_address_id = address.id;
    form.customer_name = address.recipient_name;
    if (address.phone) {
        form.customer_phone = address.phone;
    }
    form.shipping_address = {
        line1: address.line1,
        line2: address.line2 ?? '',
        city: address.city,
        postal_code: address.postal_code,
        country: address.country,
    };
    if (form.billing_same_as_shipping) {
        form.billing_address = { ...form.shipping_address };
    }
    form.save_address_to_profile = false;
    form.address_label = '';
}

function selectNewAddress() {
    form.selected_address_id = null;
    form.shipping_address = {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        country: defaultCountry,
    };
    if (form.billing_same_as_shipping) {
        form.billing_address = { ...form.shipping_address };
    }
}

const selectedDelivery = computed(() => {
    return (
        props.deliveryOptions.find((d) => d.id === form.delivery_option_id) ??
        null
    );
});

const selectedPayment = computed(() => {
    return (
        props.paymentOptions.find((p) => p.id === form.payment_option_id) ??
        null
    );
});

const deliveryCost = computed(() => {
    if (!selectedDelivery.value) return 0;
    return selectedDelivery.value.is_free ? 0 : selectedDelivery.value.price;
});

const isApplyingPromo = ref(false);
const promoError = ref<string | null>(null);
const promoSuccess = ref<string | null>(null);
const mobilePromoCode = ref('');

const cart = useCart();

onMounted(() => {
    cart.initFromServer(
        props.items as any,
        props.appliedPromotion,
        props.availablePromotions,
    );
});

const activePromo = computed<AppliedPromotionData | null>(() => {
    return cart.appliedPromotion.value ?? props.appliedPromotion ?? null;
});

const availablePromotions = computed<AvailablePromotionData[]>(() => {
    return cart.availablePromotions.value.length > 0
        ? cart.availablePromotions.value
        : (props.availablePromotions ?? []);
});

const discountAmount = computed(() => {
    return activePromo.value
        ? activePromo.value.discount_amount
        : (props.summary.discount_amount ?? 0);
});

const calculatedTotal = computed(() => {
    return Math.max(
        0,
        props.summary.subtotal - discountAmount.value + deliveryCost.value,
    );
});

const generalErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

async function handleApplyPromo(code: string) {
    if (!code || isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const res = await cart.applyPromotion({ code });
    isApplyingPromo.value = false;

    if (res.success) {
        promoSuccess.value = res.message || 'Promotion applied!';
        mobilePromoCode.value = '';
        form.promotion_id = cart.appliedPromotion.value?.id ?? null;
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
    } else {
        promoError.value = res.error || 'Invalid promotion code';
    }
}

async function handleApplyPromoOffer(promoId: number) {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    const res = await cart.applyPromotion({ promotion_id: promoId });
    isApplyingPromo.value = false;

    if (res.success) {
        promoSuccess.value = res.message || 'Promotion applied!';
        form.promotion_id = cart.appliedPromotion.value?.id ?? null;
        setTimeout(() => {
            promoSuccess.value = null;
        }, 3500);
    } else {
        promoError.value = res.error || 'Failed to apply promotion';
    }
}

async function handleRemovePromo() {
    if (isApplyingPromo.value) return;
    promoError.value = null;
    promoSuccess.value = null;
    isApplyingPromo.value = true;

    await cart.removePromotion();
    isApplyingPromo.value = false;
    form.promotion_id = null;
}

const removeLocationListener = router.on('location', () => {
    cart.clearCart();
});

onUnmounted(() => {
    removeLocationListener();
});

function submit() {
    form.post(checkoutStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            cart.clearCart();
        },
    });
}
</script>

<template>
    <Head title="Secure Checkout | Green Veil" />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <!-- Breadcrumbs -->
        <Breadcrumbs
            inline
            :items="[
                { name: 'Shopping Bag', url: cartIndex.url() },
                { name: 'Checkout', url: '' },
            ]"
            class="mb-6"
        />

        <h1
            class="mb-8 font-serif text-3xl tracking-tight text-charcoal-900 sm:text-4xl"
        >
            Conscious Checkout
        </h1>

        <form
            @submit.prevent="submit"
            class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-12"
        >
            <!-- Left Details Form (7 cols) -->
            <div class="space-y-8 lg:col-span-7">
                <!-- Mobile Collapsible Order Summary Bar (Top on mobile) -->
                <div
                    class="overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs lg:hidden"
                >
                    <button
                        type="button"
                        @click="mobileSummaryOpen = !mobileSummaryOpen"
                        class="flex min-h-[44px] w-full items-center justify-between bg-cream-50/80 px-4 py-3.5 text-xs transition-colors hover:bg-cream-100/80"
                    >
                        <span
                            class="flex items-center gap-2 font-medium text-sage-900"
                        >
                            <svg
                                class="h-4 w-4 text-sage-700"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"
                                />
                            </svg>
                            <span>{{
                                mobileSummaryOpen
                                    ? 'Hide order summary'
                                    : 'Show order summary'
                            }}</span>
                            <svg
                                class="h-3.5 w-3.5 transition-transform"
                                :class="mobileSummaryOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </span>
                        <span class="text-sm font-bold text-charcoal-900">
                            {{ formatCurrency(calculatedTotal) }}
                        </span>
                    </button>

                    <div
                        v-show="mobileSummaryOpen"
                        class="border-t border-cream-100 p-4"
                    >
                        <!-- Items mini list -->
                        <div
                            class="max-h-60 divide-y divide-cream-100 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in items"
                                :key="item.product_id"
                                class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
                            >
                                <div
                                    class="relative h-12 w-12 shrink-0 rounded-xl border border-cream-200 bg-cream-100"
                                >
                                    <img
                                        v-if="item.product.main_image"
                                        :src="
                                            resolveStorageUrl(
                                                item.product.main_image,
                                            )
                                        "
                                        :alt="item.product.name"
                                        class="h-full w-full rounded-[10px] object-cover"
                                    />
                                    <div
                                        v-else
                                        class="text-charcoal-300 flex h-full w-full items-center justify-center rounded-[10px]"
                                    >
                                        🍃
                                    </div>
                                    <span
                                        class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-charcoal-800 text-[9px] font-bold text-cream-50 shadow-xs"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-xs font-medium text-charcoal-900"
                                    >
                                        {{ item.product.name }}
                                    </p>
                                    <p class="text-charcoal-400 text-[11px]">
                                        {{ formatCurrency(item.product.price) }}
                                        each
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-xs font-semibold text-charcoal-900"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Mobile Promotions Section -->
                        <div class="border-t border-cream-100 pt-3">
                            <!-- Active Promotion Banner if applied -->
                            <div
                                v-if="activePromo"
                                class="mb-3 flex items-center justify-between rounded-xl border border-sage-200 bg-sage-50/70 p-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sage-800 text-cream-50"
                                    >
                                        <svg
                                            class="h-3 w-3"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </span>
                                    <div>
                                        <span
                                            class="font-medium text-sage-900"
                                            >{{ activePromo.name }}</span
                                        >
                                        <span
                                            v-if="activePromo.code"
                                            class="ml-1 rounded bg-sage-200/70 px-1.5 py-0.5 font-mono text-[9px] font-semibold text-sage-800 uppercase"
                                        >
                                            {{ activePromo.code }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-semibold text-emerald-800"
                                    >
                                        {{ activePromo.formatted_discount }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="handleRemovePromo"
                                        class="text-[11px] font-medium text-rose-600 hover:text-rose-800"
                                        title="Remove promo code"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <!-- Mobile Promo Input -->
                            <div v-else class="mb-3 space-y-1.5">
                                <div class="flex gap-2">
                                    <input
                                        v-model="mobilePromoCode"
                                        type="text"
                                        placeholder="Promo code"
                                        class="placeholder-charcoal-400 w-full rounded-xl border border-cream-300 bg-cream-50/50 px-3 py-2 text-xs uppercase placeholder:normal-case focus:border-sage-500 focus:bg-white focus:outline-none"
                                        :disabled="isApplyingPromo"
                                        @keydown.enter.prevent="
                                            handleApplyPromo(mobilePromoCode)
                                        "
                                    />
                                    <button
                                        type="button"
                                        @click="
                                            handleApplyPromo(mobilePromoCode)
                                        "
                                        :disabled="
                                            isApplyingPromo ||
                                            !mobilePromoCode.trim()
                                        "
                                        class="shrink-0 rounded-xl bg-sage-800 px-3.5 py-2 text-xs font-semibold text-cream-50 uppercase transition-colors hover:bg-sage-900 disabled:opacity-50"
                                    >
                                        {{ isApplyingPromo ? '...' : 'Apply' }}
                                    </button>
                                </div>
                                <p
                                    v-if="promoError"
                                    class="text-[11px] text-rose-600"
                                >
                                    {{ promoError }}
                                </p>
                                <p
                                    v-if="promoSuccess"
                                    class="text-[11px] font-medium text-emerald-700"
                                >
                                    {{ promoSuccess }}
                                </p>
                            </div>

                            <!-- Mobile Available Store Offers -->
                            <div
                                v-if="
                                    availablePromotions &&
                                    availablePromotions.length > 0
                                "
                                class="mb-3 space-y-2 border-t border-cream-100 pt-2.5"
                            >
                                <div
                                    class="text-[10px] font-semibold tracking-wider text-charcoal-700 uppercase"
                                >
                                    Available Store Offers
                                </div>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="offer in availablePromotions"
                                        :key="offer.id"
                                        class="flex items-center justify-between gap-2 rounded-xl border p-2 text-xs transition-all"
                                        :class="
                                            offer.is_applied
                                                ? 'border-sage-300 bg-sage-50/70'
                                                : offer.is_eligible
                                                  ? 'border-cream-200 bg-cream-50/40'
                                                  : 'border-cream-100 opacity-75'
                                        "
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <span
                                                    class="truncate text-xs font-medium text-charcoal-900"
                                                    >{{ offer.name }}</span
                                                >
                                                <span
                                                    v-if="offer.code"
                                                    class="rounded bg-cream-200/70 px-1 py-0.5 font-mono text-[9px] text-charcoal-700 uppercase"
                                                    >{{ offer.code }}</span
                                                >
                                            </div>
                                            <div
                                                class="text-charcoal-500 text-[10px]"
                                            >
                                                <template
                                                    v-if="
                                                        offer.discount_type ===
                                                        'percentage'
                                                    "
                                                >
                                                    {{ offer.discount_value }}%
                                                    off
                                                </template>
                                                <template v-else>
                                                    €{{ offer.discount_value }}
                                                    off
                                                </template>
                                                <span
                                                    v-if="
                                                        offer.minimum_order_amount
                                                    "
                                                >
                                                    • over €{{
                                                        offer.minimum_order_amount
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="shrink-0">
                                            <span
                                                v-if="offer.is_applied"
                                                class="rounded-full bg-sage-100 px-2 py-0.5 text-[10px] font-semibold text-sage-800"
                                            >
                                                Applied
                                            </span>
                                            <button
                                                v-else-if="offer.is_eligible"
                                                type="button"
                                                @click="
                                                    handleApplyPromoOffer(
                                                        offer.id,
                                                    )
                                                "
                                                :disabled="isApplyingPromo"
                                                class="rounded-full bg-sage-800 px-2.5 py-0.5 text-[10px] font-medium text-cream-50"
                                            >
                                                {{
                                                    activePromo
                                                        ? 'Switch'
                                                        : 'Apply'
                                                }}
                                            </button>
                                            <span
                                                v-else
                                                class="text-charcoal-500 rounded-full bg-cream-200/70 px-2 py-0.5 text-[9px]"
                                            >
                                                Locked
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown -->
                        <div
                            class="space-y-1.5 border-t border-cream-100 pt-3 text-xs"
                        >
                            <div class="text-charcoal-600 flex justify-between">
                                <span>Subtotal</span>
                                <span class="font-medium text-charcoal-900">{{
                                    formatCurrency(summary.subtotal)
                                }}</span>
                            </div>
                            <div
                                v-if="discountAmount > 0"
                                class="flex justify-between font-medium text-emerald-800"
                            >
                                <span>Promotion Savings</span>
                                <span
                                    >-{{ formatCurrency(discountAmount) }}</span
                                >
                            </div>
                            <div class="text-charcoal-600 flex justify-between">
                                <span>Shipping</span>
                                <span>{{
                                    deliveryCost === 0
                                        ? 'Free'
                                        : formatCurrency(deliveryCost)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- General / Payment Errors -->
                <div
                    v-if="generalErrors.payment || generalErrors.cart"
                    class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-800"
                >
                    <p v-if="generalErrors.payment" class="font-medium">
                        {{ generalErrors.payment }}
                    </p>
                    <p v-if="generalErrors.cart" class="font-medium">
                        {{ generalErrors.cart }}
                    </p>
                </div>

                <!-- Step 1: Contact Details -->
                <section
                    class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                >
                    <div
                        class="mb-6 flex items-center justify-between border-b border-cream-100 pb-4"
                    >
                        <h2 class="font-serif text-lg text-charcoal-900">
                            1. Contact Information
                        </h2>
                        <span
                            v-if="!customer"
                            class="text-charcoal-500 text-xs"
                        >
                            Have an account?
                            <Link
                                :href="login.url()"
                                class="font-medium text-sage-800 underline"
                                >Log in</Link
                            >
                        </span>
                    </div>

                    <div class="space-y-4">
                        <TextField
                            id="customer_name"
                            v-model="form.customer_name"
                            label="Full Name"
                            required
                            placeholder="Lady Catherine"
                            :error="form.errors.customer_name"
                        />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                id="customer_email"
                                v-model="form.customer_email"
                                type="email"
                                label="Email Address"
                                required
                                placeholder="catherine@botanicals.ie"
                                :error="form.errors.customer_email"
                            />

                            <TextField
                                id="customer_phone"
                                v-model="form.customer_phone"
                                type="tel"
                                label="Phone Number"
                                subtext="(for delivery SMS)"
                                placeholder="+353 87 123 4567"
                                :error="form.errors.customer_phone"
                            />
                        </div>
                    </div>
                </section>

                <!-- Step 2: Delivery Address -->
                <section
                    class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                >
                    <div class="mb-6 border-b border-cream-100 pb-4">
                        <h2 class="font-serif text-lg text-charcoal-900">
                            2. Shipping Address
                        </h2>
                    </div>

                    <!-- Saved Addresses Selector (for authenticated users with addresses) -->
                    <div
                        v-if="savedAddresses && savedAddresses.length > 0"
                        class="mb-6 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <label
                                class="text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
                            >
                                Select a Saved Delivery Address
                            </label>
                            <span class="text-charcoal-500 text-[11px]">
                                {{ savedAddresses.length }} of 3 saved
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <button
                                v-for="address in savedAddresses"
                                :key="address.id"
                                type="button"
                                @click="selectSavedAddress(address)"
                                class="relative flex min-h-[110px] flex-col justify-between rounded-2xl border p-4 text-left transition-all"
                                :class="
                                    form.selected_address_id === address.id
                                        ? 'border-sage-600 bg-sage-50/70 shadow-xs ring-1 ring-sage-600'
                                        : 'border-cream-200 bg-white hover:border-cream-300'
                                "
                            >
                                <div>
                                    <div
                                        class="mb-1.5 flex items-center justify-between gap-1.5"
                                    >
                                        <span
                                            class="inline-flex items-center rounded-full bg-cream-200/80 px-2 py-0.5 text-[10px] font-semibold text-charcoal-800"
                                        >
                                            {{ address.label }}
                                        </span>
                                        <span
                                            v-if="address.is_default"
                                            class="rounded-full bg-sage-200/90 px-2 py-0.5 text-[9px] font-semibold text-sage-900"
                                        >
                                            Default
                                        </span>
                                    </div>
                                    <p
                                        class="font-serif text-xs font-medium text-charcoal-900"
                                    >
                                        {{ address.recipient_name }}
                                    </p>
                                    <p
                                        class="text-charcoal-600 mt-1 line-clamp-2 text-[11px]"
                                    >
                                        {{ address.formatted }}
                                    </p>
                                </div>
                                <p
                                    v-if="address.phone"
                                    class="text-charcoal-500 mt-2 text-[10px]"
                                >
                                    📞 {{ address.phone }}
                                </p>
                            </button>

                            <!-- Enter New Address Card -->
                            <button
                                type="button"
                                @click="selectNewAddress"
                                class="flex min-h-[110px] flex-col items-center justify-center rounded-2xl border border-dashed p-4 text-center transition-all"
                                :class="
                                    form.selected_address_id === null
                                        ? 'border-sage-600 bg-sage-50/40 ring-1 ring-sage-600'
                                        : 'hover:border-cream-400 border-cream-300 bg-cream-50/30 hover:bg-cream-50/70'
                                "
                            >
                                <span
                                    class="mb-1 flex h-6 w-6 items-center justify-center rounded-full bg-cream-200 text-sm font-semibold text-charcoal-700"
                                >
                                    +
                                </span>
                                <span
                                    class="text-xs font-medium text-charcoal-900"
                                >
                                    Use a new address
                                </span>
                                <span
                                    class="text-charcoal-500 mt-0.5 text-[10px]"
                                >
                                    Enter details below
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Selected Address Summary Card (when a saved address is chosen) -->
                    <div
                        v-if="form.selected_address_id !== null"
                        class="rounded-2xl border border-sage-200 bg-sage-50/50 p-4.5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span
                                    class="text-[10px] font-semibold tracking-wider text-sage-800 uppercase"
                                >
                                    Selected Shipping Address
                                </span>
                                <p
                                    class="font-serif text-sm font-medium text-charcoal-900"
                                >
                                    {{ form.customer_name }}
                                </p>
                                <p class="mt-0.5 text-xs text-charcoal-700">
                                    {{ form.shipping_address.line1
                                    }}<template
                                        v-if="form.shipping_address.line2"
                                        >,
                                        {{
                                            form.shipping_address.line2
                                        }}</template
                                    >, {{ form.shipping_address.city }},
                                    {{ form.shipping_address.postal_code }} ({{
                                        form.shipping_address.country
                                    }})
                                </p>
                                <p
                                    v-if="form.customer_phone"
                                    class="text-charcoal-600 mt-1 text-[11px]"
                                >
                                    Delivery SMS phone:
                                    <span
                                        class="font-medium text-charcoal-800"
                                        >{{ form.customer_phone }}</span
                                    >
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="selectNewAddress"
                                class="rounded-xl border border-sage-300 bg-white px-3 py-1.5 text-xs font-medium text-sage-800 shadow-2xs transition-colors hover:bg-sage-50"
                            >
                                Enter Different Address
                            </button>
                        </div>
                    </div>

                    <!-- Address Input Form (when entering a new address or no saved addresses exist) -->
                    <div v-else class="space-y-4">
                        <AddressFormFields
                            id-prefix="shipping"
                            error-prefix="shipping_address"
                            v-model="form.shipping_address"
                            :country-options="countryOptions"
                            :errors="form.errors"
                        />

                        <!-- Option to save to customer profile -->
                        <div
                            v-if="customer && canSaveAddress"
                            class="space-y-3 rounded-2xl border border-cream-200 bg-cream-50/60 p-4"
                        >
                            <CheckboxField
                                id="save_address_to_profile"
                                v-model="form.save_address_to_profile"
                                label="Save this shipping address to my profile"
                                subtext="Save up to 3 addresses with phone numbers for 1-click checkout next time"
                            />

                            <div
                                v-if="form.save_address_to_profile"
                                class="max-w-xs pt-1"
                            >
                                <TextField
                                    id="address_label"
                                    v-model="form.address_label"
                                    label="Address Label"
                                    placeholder="e.g. Home, Office, Studio"
                                    subtext="(optional, defaults to 'Home')"
                                    :error="form.errors.address_label"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Billing toggle -->
                    <div class="mt-6 border-t border-cream-100 pt-5">
                        <CheckboxField
                            id="billing_same_as_shipping"
                            v-model="form.billing_same_as_shipping"
                            label="Billing address matches shipping address"
                        />

                        <!-- Different billing address fields -->
                        <div
                            v-if="!form.billing_same_as_shipping"
                            class="mt-4 space-y-4 pt-2"
                        >
                            <h3
                                class="text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
                            >
                                Billing Address
                            </h3>

                            <AddressFormFields
                                id-prefix="billing"
                                error-prefix="billing_address"
                                v-model="form.billing_address"
                                :country-options="countryOptions"
                                :errors="form.errors"
                            />
                        </div>
                    </div>
                </section>

                <!-- Step 3: Delivery Options -->
                <section
                    class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                >
                    <div class="mb-6 border-b border-cream-100 pb-4">
                        <h2 class="font-serif text-lg text-charcoal-900">
                            3. Delivery Method
                        </h2>
                    </div>

                    <RadioCardGroup :error="form.errors.delivery_option_id">
                        <RadioCard
                            v-for="option in deliveryOptions"
                            :key="option.id"
                            v-model="form.delivery_option_id"
                            :value="option.id"
                            name="delivery_option"
                            :title="option.name"
                            :subtitle="option.estimated_delivery_label"
                            :description="option.description"
                        >
                            <template #trailing>
                                <span
                                    v-if="option.is_free"
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800"
                                >
                                    FREE
                                </span>
                                <span
                                    v-else
                                    class="text-sm font-semibold text-charcoal-900"
                                >
                                    {{ formatCurrency(option.price) }}
                                </span>
                            </template>
                        </RadioCard>
                    </RadioCardGroup>
                </section>

                <!-- Step 4: Payment Options -->
                <section
                    class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                >
                    <div class="mb-6 border-b border-cream-100 pb-4">
                        <h2 class="font-serif text-lg text-charcoal-900">
                            4. Payment Method
                        </h2>
                    </div>

                    <RadioCardGroup :error="form.errors.payment_option_id">
                        <RadioCard
                            v-for="option in paymentOptions"
                            :key="option.id"
                            v-model="form.payment_option_id"
                            :value="option.id"
                            name="payment_option"
                            align="start"
                            :title="option.name"
                            :description="option.description"
                        >
                            <template #badge>
                                <span
                                    v-if="option.driver === 'stripe'"
                                    class="rounded-md bg-sage-100 px-2 py-0.5 text-[10px] font-medium text-sage-800"
                                >
                                    Card / Apple Pay
                                </span>
                            </template>
                        </RadioCard>
                    </RadioCardGroup>
                </section>

                <!-- Step 5: Optional Notes -->
                <section
                    class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                >
                    <TextareaField
                        id="order_notes"
                        v-model="form.notes"
                        label="Order Notes & Botanical Requests"
                        subtext="(optional)"
                        :rows="2"
                        placeholder="Special delivery notes or sensitive skin instructions..."
                        :error="form.errors.notes"
                    />
                </section>
            </div>

            <!-- Right Sidebar: Order Summary (5 cols) -->
            <CheckoutOrderSummary
                :items="items"
                :summary="summary"
                :applied-promotion="activePromo"
                :available-promotions="availablePromotions"
                :selected-delivery="selectedDelivery"
                :selected-payment="selectedPayment"
                :delivery-cost="deliveryCost"
                :discount-amount="discountAmount"
                :calculated-total="calculatedTotal"
                :processing="form.processing"
                :is-applying-promo="isApplyingPromo"
                :promo-error="promoError"
                :promo-success="promoSuccess"
                @apply-promo="handleApplyPromo"
                @apply-offer="handleApplyPromoOffer"
                @remove-promo="handleRemovePromo"
            />
        </form>
    </div>
</template>
