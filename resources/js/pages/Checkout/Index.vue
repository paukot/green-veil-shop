<script setup lang="ts">
import { computed, onUnmounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckboxField,
    RadioCard,
    RadioCardGroup,
    SelectField,
    TextField,
    TextareaField,
} from '@/components/form';
import type { SelectOption } from '@/components/form';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import Breadcrumbs from '@/components/storefront/Breadcrumbs.vue';
import Button from '@/components/ui/Button.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
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

                    <div class="space-y-4">
                        <TextField
                            id="shipping_line1"
                            v-model="form.shipping_address.line1"
                            label="Address Line 1"
                            required
                            placeholder="42 Willowbrook Lane"
                            :error="form.errors['shipping_address.line1']"
                        />

                        <TextField
                            id="shipping_line2"
                            v-model="form.shipping_address.line2"
                            label="Apartment, suite, unit"
                            subtext="(optional)"
                            placeholder="Apt 3B"
                            :error="form.errors['shipping_address.line2']"
                        />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <TextField
                                id="shipping_city"
                                v-model="form.shipping_address.city"
                                label="City"
                                required
                                placeholder="Dublin"
                                :error="form.errors['shipping_address.city']"
                            />

                            <TextField
                                id="shipping_postal_code"
                                v-model="form.shipping_address.postal_code"
                                label="Postal Code / Eircode"
                                required
                                placeholder="D02 X285"
                                :error="
                                    form.errors['shipping_address.postal_code']
                                "
                            />

                            <SelectField
                                id="shipping_country"
                                v-model="form.shipping_address.country"
                                label="Country"
                                required
                                :options="COUNTRY_OPTIONS"
                                :error="form.errors['shipping_address.country']"
                            />
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

                            <TextField
                                id="billing_line1"
                                v-model="form.billing_address.line1"
                                label="Address Line 1"
                                required
                                :error="form.errors['billing_address.line1']"
                            />

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <TextField
                                    id="billing_city"
                                    v-model="form.billing_address.city"
                                    label="City"
                                    :error="form.errors['billing_address.city']"
                                />

                                <TextField
                                    id="billing_postal_code"
                                    v-model="form.billing_address.postal_code"
                                    label="Postal Code"
                                    :error="
                                        form.errors[
                                            'billing_address.postal_code'
                                        ]
                                    "
                                />

                                <SelectField
                                    id="billing_country"
                                    v-model="form.billing_address.country"
                                    label="Country"
                                    :options="COUNTRY_OPTIONS"
                                    :error="
                                        form.errors['billing_address.country']
                                    "
                                />
                            </div>
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
            <div class="lg:col-span-5">
                <div
                    class="sticky top-24 overflow-hidden rounded-3xl border border-cream-200 bg-white shadow-xs"
                >
                    <!-- Header with item count -->
                    <div
                        class="flex items-center justify-between border-b border-cream-100 bg-cream-50/60 px-6 py-5 sm:px-7"
                    >
                        <div>
                            <h2
                                class="font-serif text-lg font-medium text-charcoal-900"
                            >
                                Order Summary
                            </h2>
                            <p class="text-charcoal-500 text-[11px]">
                                Botanical formulations snapshot
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-cream-200/80 px-2.5 py-0.5 text-xs font-medium text-charcoal-700"
                        >
                            {{ items.reduce((acc, i) => acc + i.quantity, 0) }}
                            {{
                                items.reduce(
                                    (acc, i) => acc + i.quantity,
                                    0,
                                ) === 1
                                    ? 'item'
                                    : 'items'
                            }}
                        </span>
                    </div>

                    <div class="space-y-6 p-6 sm:p-7">
                        <!-- Items Snapshot -->
                        <div
                            class="max-h-60 space-y-3.5 divide-y divide-cream-100 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in items"
                                :key="item.id"
                                class="flex items-center gap-3.5 pt-3.5 first:pt-0"
                            >
                                <div
                                    class="relative h-13 w-13 shrink-0 rounded-xl border border-cream-200 bg-cream-50 p-0.5"
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
                                        class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-charcoal-800 text-[10px] font-bold text-cream-50 shadow-xs ring-2 ring-white"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-xs font-medium text-charcoal-900 sm:text-sm"
                                    >
                                        {{ item.product.name }}
                                    </p>
                                    <p class="text-charcoal-400 text-[11px]">
                                        {{ formatCurrency(item.product.price) }}
                                        each
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-xs font-semibold text-charcoal-900 sm:text-sm"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Applied Promotion Tag -->
                        <div
                            v-if="appliedPromotion"
                            class="flex items-center justify-between rounded-2xl border border-sage-200 bg-sage-50/70 px-3.5 py-2.5 text-xs"
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
                                <span class="font-medium text-sage-900">
                                    {{ appliedPromotion.name }}
                                </span>
                                <span
                                    v-if="appliedPromotion.code"
                                    class="rounded bg-sage-200/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wider text-sage-800 uppercase"
                                >
                                    {{ appliedPromotion.code }}
                                </span>
                            </div>
                            <span class="font-semibold text-emerald-800">
                                {{ appliedPromotion.formatted_discount }}
                            </span>
                        </div>

                        <!-- Calculations Table -->
                        <div
                            class="space-y-3 border-t border-cream-100 pt-4 text-xs sm:text-sm"
                        >
                            <div
                                class="text-charcoal-600 flex items-center justify-between"
                            >
                                <span>Subtotal</span>
                                <span class="font-medium text-charcoal-900">{{
                                    formatCurrency(summary.subtotal)
                                }}</span>
                            </div>

                            <div
                                v-if="discountAmount > 0"
                                class="flex items-center justify-between font-medium text-emerald-800"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span>Promotion Savings</span>
                                </span>
                                <span class="font-semibold"
                                    >-{{ formatCurrency(discountAmount) }}</span
                                >
                            </div>

                            <div
                                class="text-charcoal-600 flex items-center justify-between"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span>Shipping</span>
                                    <span
                                        v-if="selectedDelivery"
                                        class="text-charcoal-400 text-[11px]"
                                        >({{ selectedDelivery.name }})</span
                                    >
                                </span>
                                <span
                                    v-if="deliveryCost === 0"
                                    class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 uppercase"
                                >
                                    Free
                                </span>
                                <span
                                    v-else
                                    class="font-medium text-charcoal-900"
                                >
                                    {{ formatCurrency(deliveryCost) }}
                                </span>
                            </div>

                            <!-- Total -->
                            <div class="border-t border-cream-200 pt-4">
                                <div
                                    class="flex items-baseline justify-between"
                                >
                                    <div>
                                        <span
                                            class="font-serif text-lg font-medium text-charcoal-900"
                                            >Grand Total</span
                                        >
                                        <span
                                            class="text-charcoal-400 block text-[11px]"
                                            >Taxes & duties included</span
                                        >
                                    </div>
                                    <div class="text-right">
                                        <span
                                            v-if="discountAmount > 0"
                                            class="text-charcoal-400 mr-2 text-xs line-through"
                                        >
                                            {{
                                                formatCurrency(
                                                    summary.subtotal +
                                                        deliveryCost,
                                                )
                                            }}
                                        </span>
                                        <span
                                            class="text-charcoal-950 font-sans text-2xl font-bold tracking-tight sm:text-3xl"
                                        >
                                            {{
                                                formatCurrency(calculatedTotal)
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <div class="pt-1">
                            <Button
                                type="submit"
                                size="lg"
                                class="w-full rounded-full py-4 text-xs font-semibold tracking-wider uppercase shadow-sm transition-all hover:shadow-md active:scale-[0.99]"
                                :loading="form.processing"
                            >
                                <span v-if="form.processing"
                                    >Securing Your Order...</span
                                >
                                <span
                                    v-else-if="
                                        selectedPayment?.driver === 'stripe'
                                    "
                                    >Proceed to Stripe Payment</span
                                >
                                <span v-else>Confirm & Place Order</span>
                            </Button>
                        </div>

                        <!-- Trust signals -->
                        <div
                            class="text-charcoal-500 space-y-2 rounded-2xl bg-cream-50/70 p-3.5 text-[11px]"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg
                                    class="h-4 w-4 shrink-0 text-sage-700"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                    />
                                </svg>
                                <span>256-Bit SSL Encrypted & Secure</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <svg
                                    class="h-4 w-4 shrink-0 text-sage-700"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V3.75"
                                    />
                                </svg>
                                <span
                                    >Pure botanical artisanal formulations</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
