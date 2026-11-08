<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
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
import type { CheckoutPageProps } from '@/types/storefront';
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

const form = useForm({
    customer_name: props.customer?.name ?? '',
    customer_email: props.customer?.email ?? '',
    customer_phone: '',
    delivery_option_id: defaultDelivery ? defaultDelivery.id : 0,
    payment_option_id: defaultPayment ? defaultPayment.id : 0,
    shipping_address: {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        country: defaultCountry,
    },
    billing_same_as_shipping: true,
    billing_address: {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        country: defaultCountry,
    },
    notes: '',
    promotion_id: props.appliedPromotion?.id ?? null,
});

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

const discountAmount = computed(() => {
    return props.appliedPromotion
        ? props.appliedPromotion.discount_amount
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

const cart = useCart();

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

                    <AddressFormFields
                        id-prefix="shipping"
                        error-prefix="shipping_address"
                        v-model="form.shipping_address"
                        :country-options="countryOptions"
                        :errors="form.errors"
                    />

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
                :applied-promotion="appliedPromotion"
                :selected-delivery="selectedDelivery"
                :selected-payment="selectedPayment"
                :delivery-cost="deliveryCost"
                :discount-amount="discountAmount"
                :calculated-total="calculatedTotal"
                :processing="form.processing"
            />
        </form>
    </div>
</template>
