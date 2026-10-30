<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CheckoutPageProps } from '@/types/storefront';

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

const calculatedTotal = computed(() => {
    return Math.max(0, props.summary.subtotal + deliveryCost.value);
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-IE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
}

const generalErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

function submit() {
    form.post('/checkout', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Secure Checkout | Green Veil" />

    <StorefrontLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <!-- Breadcrumbs / Steps -->
            <nav class="text-charcoal-500 mb-6 flex items-center gap-2 text-xs">
                <Link href="/cart" class="hover:text-sage-800"
                    >Shopping Bag</Link
                >
                <span>/</span>
                <span class="font-medium text-charcoal-900">Checkout</span>
            </nav>

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
                                    href="/login"
                                    class="font-medium text-sage-800 underline"
                                    >Log in</Link
                                >
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    for="customer_name"
                                    class="block text-xs font-medium text-charcoal-700"
                                >
                                    Full Name
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="customer_name"
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    placeholder="Lady Catherine"
                                    class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                />
                                <p
                                    v-if="form.errors.customer_name"
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{ form.errors.customer_name }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="customer_email"
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        Email Address
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="customer_email"
                                        v-model="form.customer_email"
                                        type="email"
                                        required
                                        placeholder="catherine@botanicals.ie"
                                        class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    />
                                    <p
                                        v-if="form.errors.customer_email"
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{ form.errors.customer_email }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="customer_phone"
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        Phone Number
                                        <span
                                            class="text-charcoal-400 font-normal"
                                            >(for delivery SMS)</span
                                        >
                                    </label>
                                    <input
                                        id="customer_phone"
                                        v-model="form.customer_phone"
                                        type="tel"
                                        placeholder="+353 87 123 4567"
                                        class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    />
                                    <p
                                        v-if="form.errors.customer_phone"
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{ form.errors.customer_phone }}
                                    </p>
                                </div>
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
                            <div>
                                <label
                                    for="shipping_line1"
                                    class="block text-xs font-medium text-charcoal-700"
                                >
                                    Address Line 1
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="shipping_line1"
                                    v-model="form.shipping_address.line1"
                                    type="text"
                                    required
                                    placeholder="42 Willowbrook Lane"
                                    class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                />
                                <p
                                    v-if="form.errors['shipping_address.line1']"
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{ form.errors['shipping_address.line1'] }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="shipping_line2"
                                    class="block text-xs font-medium text-charcoal-700"
                                >
                                    Apartment, suite, unit
                                    <span class="text-charcoal-400 font-normal"
                                        >(optional)</span
                                    >
                                </label>
                                <input
                                    id="shipping_line2"
                                    v-model="form.shipping_address.line2"
                                    type="text"
                                    placeholder="Apt 3B"
                                    class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label
                                        for="shipping_city"
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        City
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="shipping_city"
                                        v-model="form.shipping_address.city"
                                        type="text"
                                        required
                                        placeholder="Dublin"
                                        class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    />
                                    <p
                                        v-if="
                                            form.errors['shipping_address.city']
                                        "
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{
                                            form.errors['shipping_address.city']
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="shipping_postal_code"
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        Postal Code / Eircode
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="shipping_postal_code"
                                        v-model="
                                            form.shipping_address.postal_code
                                        "
                                        type="text"
                                        required
                                        placeholder="D02 X285"
                                        class="placeholder-charcoal-400 mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                'shipping_address.postal_code'
                                            ]
                                        "
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{
                                            form.errors[
                                                'shipping_address.postal_code'
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="shipping_country"
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        Country
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="shipping_country"
                                        v-model="form.shipping_address.country"
                                        class="mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    >
                                        <option value="IE">Ireland</option>
                                        <option value="GB">
                                            United Kingdom
                                        </option>
                                        <option value="FR">France</option>
                                        <option value="DE">Germany</option>
                                        <option value="ES">Spain</option>
                                        <option value="IT">Italy</option>
                                        <option value="NL">Netherlands</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Billing toggle -->
                        <div class="mt-6 border-t border-cream-100 pt-5">
                            <label
                                class="flex cursor-pointer items-center gap-3 select-none"
                            >
                                <input
                                    v-model="form.billing_same_as_shipping"
                                    type="checkbox"
                                    class="h-4 w-4 rounded-sm border-cream-300 text-sage-800 focus:ring-sage-600"
                                />
                                <span
                                    class="text-xs font-medium text-charcoal-700"
                                    >Billing address matches shipping
                                    address</span
                                >
                            </label>

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
                                <div>
                                    <label
                                        class="block text-xs font-medium text-charcoal-700"
                                    >
                                        Address Line 1
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.billing_address.line1"
                                        type="text"
                                        class="mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                    />
                                    <p
                                        v-if="
                                            form.errors['billing_address.line1']
                                        "
                                        class="mt-1 text-xs text-rose-600"
                                    >
                                        {{
                                            form.errors['billing_address.line1']
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-charcoal-700"
                                            >City</label
                                        >
                                        <input
                                            v-model="form.billing_address.city"
                                            type="text"
                                            class="mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-charcoal-700"
                                            >Postal Code</label
                                        >
                                        <input
                                            v-model="
                                                form.billing_address.postal_code
                                            "
                                            type="text"
                                            class="mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-charcoal-700"
                                            >Country</label
                                        >
                                        <select
                                            v-model="
                                                form.billing_address.country
                                            "
                                            class="mt-1 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                                        >
                                            <option value="IE">Ireland</option>
                                            <option value="GB">
                                                United Kingdom
                                            </option>
                                            <option value="FR">France</option>
                                            <option value="DE">Germany</option>
                                        </select>
                                    </div>
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

                        <div class="space-y-3">
                            <label
                                v-for="option in deliveryOptions"
                                :key="option.id"
                                :class="[
                                    'flex cursor-pointer items-center justify-between rounded-2xl border p-4 transition-all',
                                    form.delivery_option_id === option.id
                                        ? 'border-sage-700 bg-sage-50/40 shadow-xs'
                                        : 'border-cream-200 bg-white hover:border-cream-300',
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="form.delivery_option_id"
                                        type="radio"
                                        name="delivery_option"
                                        :value="option.id"
                                        class="h-4 w-4 border-cream-300 text-sage-800 focus:ring-sage-600"
                                    />
                                    <div>
                                        <span
                                            class="block text-sm font-medium text-charcoal-900"
                                        >
                                            {{ option.name }}
                                        </span>
                                        <span
                                            v-if="
                                                option.estimated_delivery_label
                                            "
                                            class="text-charcoal-500 block text-xs"
                                        >
                                            {{
                                                option.estimated_delivery_label
                                            }}
                                        </span>
                                        <span
                                            v-if="option.description"
                                            class="text-charcoal-400 mt-0.5 block text-xs"
                                        >
                                            {{ option.description }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
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
                                </div>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.delivery_option_id"
                            class="mt-2 text-xs text-rose-600"
                        >
                            {{ form.errors.delivery_option_id }}
                        </p>
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

                        <div class="space-y-3">
                            <label
                                v-for="option in paymentOptions"
                                :key="option.id"
                                :class="[
                                    'flex cursor-pointer items-start justify-between rounded-2xl border p-4 transition-all',
                                    form.payment_option_id === option.id
                                        ? 'border-sage-700 bg-sage-50/40 shadow-xs'
                                        : 'border-cream-200 bg-white hover:border-cream-300',
                                ]"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        v-model="form.payment_option_id"
                                        type="radio"
                                        name="payment_option"
                                        :value="option.id"
                                        class="mt-1 h-4 w-4 border-cream-300 text-sage-800 focus:ring-sage-600"
                                    />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-medium text-charcoal-900"
                                            >
                                                {{ option.name }}
                                            </span>
                                            <span
                                                v-if="
                                                    option.driver === 'stripe'
                                                "
                                                class="rounded-md bg-sage-100 px-2 py-0.5 text-[10px] font-medium text-sage-800"
                                            >
                                                Card / Apple Pay
                                            </span>
                                        </div>
                                        <span
                                            v-if="option.description"
                                            class="text-charcoal-500 mt-1 block text-xs"
                                        >
                                            {{ option.description }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.payment_option_id"
                            class="mt-2 text-xs text-rose-600"
                        >
                            {{ form.errors.payment_option_id }}
                        </p>
                    </section>

                    <!-- Step 5: Optional Notes -->
                    <section
                        class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                    >
                        <label
                            for="order_notes"
                            class="mb-2 block font-serif text-base text-charcoal-900"
                        >
                            Order Notes & Botanical Requests
                            <span
                                class="text-charcoal-400 font-sans text-xs font-normal"
                                >(optional)</span
                            >
                        </label>
                        <textarea
                            id="order_notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Special delivery notes or sensitive skin instructions..."
                            class="placeholder-charcoal-400 block w-full rounded-xl border border-cream-300 bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 focus:border-sage-600 focus:bg-white focus:ring-1 focus:ring-sage-600 focus:outline-hidden"
                        ></textarea>
                    </section>
                </div>

                <!-- Right Sidebar: Order Summary (5 cols) -->
                <div class="lg:col-span-5">
                    <div
                        class="sticky top-24 space-y-6 rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
                    >
                        <h2
                            class="border-b border-cream-100 pb-4 font-serif text-xl text-charcoal-900"
                        >
                            Order Summary
                        </h2>

                        <!-- Items Snapshot -->
                        <div
                            class="max-h-60 space-y-3 divide-y divide-cream-100 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in items"
                                :key="item.id"
                                class="flex items-center gap-3 pt-3 first:pt-0"
                            >
                                <div
                                    class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                                >
                                    <img
                                        v-if="item.product.main_image"
                                        :src="item.product.main_image"
                                        :alt="item.product.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="text-charcoal-300 flex h-full w-full items-center justify-center"
                                    >
                                        🍃
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 text-xs">
                                    <p
                                        class="truncate font-medium text-charcoal-900"
                                    >
                                        {{ item.product.name }}
                                    </p>
                                    <p class="text-charcoal-400">
                                        Qty: {{ item.quantity }} &times;
                                        {{ formatCurrency(item.product.price) }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-semibold text-charcoal-900"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Calculations -->
                        <div
                            class="space-y-2 border-t border-cream-100 pt-4 text-xs"
                        >
                            <div class="text-charcoal-600 flex justify-between">
                                <span>Subtotal</span>
                                <span class="font-medium text-charcoal-900">{{
                                    formatCurrency(summary.subtotal)
                                }}</span>
                            </div>

                            <div class="text-charcoal-600 flex justify-between">
                                <span
                                    >Delivery ({{
                                        selectedDelivery?.name ?? 'Standard'
                                    }})</span
                                >
                                <span
                                    v-if="deliveryCost === 0"
                                    class="font-semibold text-emerald-800"
                                    >FREE</span
                                >
                                <span
                                    v-else
                                    class="font-medium text-charcoal-900"
                                    >{{ formatCurrency(deliveryCost) }}</span
                                >
                            </div>

                            <div
                                class="text-charcoal-500 flex justify-between text-[11px]"
                            >
                                <span>Estimated Taxes & Duties</span>
                                <span>Included</span>
                            </div>

                            <div
                                class="flex items-baseline justify-between border-t border-cream-200 pt-4"
                            >
                                <span
                                    class="font-serif text-base text-charcoal-900"
                                    >Grand Total</span
                                >
                                <span
                                    class="text-2xl font-semibold text-charcoal-900"
                                >
                                    {{ formatCurrency(calculatedTotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Submit CTA Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-full bg-sage-800 px-6 py-4 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin text-cream-100"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
                            </svg>
                            <span v-if="form.processing"
                                >Securing Your Order...</span
                            >
                            <span
                                v-else-if="selectedPayment?.driver === 'stripe'"
                                >Proceed to Stripe Payment</span
                            >
                            <span v-else>Confirm & Place Order</span>
                        </button>

                        <!-- Trust signals -->
                        <div
                            class="text-charcoal-500 space-y-2 pt-2 text-[11px]"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    class="h-3.5 w-3.5 shrink-0 text-sage-700"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                    />
                                </svg>
                                <span>SSL Encrypted Checkout</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg
                                    class="h-3.5 w-3.5 shrink-0 text-sage-700"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V3.75"
                                    />
                                </svg>
                                <span>Small-batch artisanal formulations</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </StorefrontLayout>
</template>
