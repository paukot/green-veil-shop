<script setup lang="ts">
import { computed, onUnmounted } from 'vue';
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
import { formatCurrency } from '@/lib/utils';
import type { CheckoutPageProps } from '@/types/storefront';
import { store as checkoutStore } from '@/actions/App/Http/Controllers/CheckoutController';
import { index as cartIndex } from '@/routes/cart';
import { login } from '@/routes';
import { useCart } from '@/composables/useCart';

defineOptions({ layout: StorefrontLayout });

const COUNTRY_OPTIONS: SelectOption[] = [
    { value: 'IE', label: 'Ireland' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'FR', label: 'France' },
    { value: 'DE', label: 'Germany' },
    { value: 'ES', label: 'Spain' },
    { value: 'IT', label: 'Italy' },
    { value: 'NL', label: 'Netherlands' },
];

const props = defineProps<CheckoutPageProps>();

const defaultDelivery =
    props.deliveryOptions.find((d) => d.is_free) ?? props.deliveryOptions[0];
const defaultPayment = props.paymentOptions[0];

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
        country: 'IE',
    },
    billing_same_as_shipping: true,
    billing_address: {
        line1: '',
        line2: '',
        city: '',
        postal_code: '',
        country: 'IE',
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
                        :country-options="COUNTRY_OPTIONS"
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
                                :country-options="COUNTRY_OPTIONS"
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
