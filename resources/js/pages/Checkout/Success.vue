<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CheckoutSuccessPageProps } from '@/types/storefront';

const props = defineProps<CheckoutSuccessPageProps>();

const pollCount = ref(0);
const maxPolls = 10;
let pollTimer: ReturnType<typeof setTimeout> | null = null;

const isStripe = computed(() => props.paymentOption?.driver === 'stripe');
const isPaid = computed(() => props.order.payment_status === 'paid');
const isPendingPayment = computed(
    () => props.order.payment_status === 'unpaid' && isStripe.value,
);

function pollOrderStatus() {
    if (!isPendingPayment.value || pollCount.value >= maxPolls) {
        return;
    }

    pollTimer = setTimeout(() => {
        pollCount.value++;
        router.reload({
            only: ['order'],
            onSuccess: () => {
                if (isPendingPayment.value && pollCount.value < maxPolls) {
                    pollOrderStatus();
                }
            },
        });
    }, 2500);
}

onMounted(() => {
    if (isPendingPayment.value) {
        pollOrderStatus();
    }
});

onUnmounted(() => {
    if (pollTimer) {
        clearTimeout(pollTimer);
    }
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-IE', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
}
</script>

<template>
    <Head :title="`Order ${order.number} Confirmed | Green Veil`" />

    <StorefrontLayout>
        <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <!-- Confirmation Card -->
            <div
                class="rounded-3xl border border-cream-200 bg-white p-6 shadow-xs sm:p-10"
            >
                <!-- Header Icon & Title -->
                <div class="text-center">
                    <div
                        v-if="isPaid"
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
                    >
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <div
                        v-else-if="isPendingPayment"
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sage-50 text-sage-700"
                    >
                        <svg
                            class="h-8 w-8 animate-spin text-sage-600"
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
                    </div>
                    <div
                        v-else
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-700"
                    >
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                    </div>

                    <div
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold tracking-wider uppercase"
                    >
                        <span
                            v-if="isPaid"
                            class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800"
                        >
                            Payment Confirmed
                        </span>
                        <span
                            v-else-if="isPendingPayment"
                            class="rounded-full bg-sage-100 px-3 py-1 text-sage-800"
                        >
                            Confirming Payment with Stripe...
                        </span>
                        <span
                            v-else-if="
                                paymentOption?.driver === 'bank_transfer'
                            "
                            class="rounded-full bg-amber-100 px-3 py-1 text-amber-800"
                        >
                            Awaiting Bank Transfer
                        </span>
                        <span
                            v-else
                            class="rounded-full bg-sage-100 px-3 py-1 text-sage-800"
                        >
                            Order Placed (Payment on Delivery)
                        </span>
                    </div>

                    <h1
                        class="mt-3 font-serif text-3xl text-charcoal-900 sm:text-4xl"
                    >
                        Thank You, {{ order.customer_name }}
                    </h1>
                    <p class="text-charcoal-500 mt-2 text-xs sm:text-sm">
                        Order reference:
                        <span
                            class="font-mono font-semibold text-charcoal-900"
                            >{{ order.number }}</span
                        >
                    </p>
                    <p class="text-charcoal-400 mt-1 text-xs">
                        A confirmation receipt will be sent to
                        <span class="font-medium text-charcoal-700">{{
                            order.customer_email
                        }}</span>
                    </p>
                </div>

                <!-- Async Stripe Confirmation Banner -->
                <div
                    v-if="isPendingPayment"
                    class="mt-6 rounded-2xl border border-sage-200 bg-sage-50/60 p-4 text-center text-xs text-sage-800"
                >
                    <p v-if="pollCount < maxPolls">
                        🌿 We are waiting for Stripe's payment confirmation
                        webhook. This screen will update automatically once
                        verified.
                    </p>
                    <p v-else>
                        🌿 Your payment is being verified in the background. A
                        formal receipt will be emailed to
                        {{ order.customer_email }} as soon as Stripe completes
                        processing.
                    </p>
                </div>

                <!-- Payment Instructions for Offline Drivers -->
                <div
                    v-if="paymentOption?.instructions"
                    class="mt-8 rounded-2xl border border-sage-200 bg-sage-50/70 p-5 text-xs text-charcoal-800"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-lg">🌿</span>
                        <div>
                            <h3 class="font-semibold text-sage-900">
                                {{ paymentOption.name }} Instructions
                            </h3>
                            <p
                                class="mt-1 whitespace-pre-line text-charcoal-700"
                            >
                                {{ paymentOption.instructions }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order Details & Breakdown -->
                <div class="mt-8 border-t border-cream-100 pt-8">
                    <h2 class="mb-4 font-serif text-lg text-charcoal-900">
                        Items in Your Order
                    </h2>

                    <div class="divide-y divide-cream-100">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center justify-between py-3.5 text-xs"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                                >
                                    <img
                                        v-if="item.product_image"
                                        :src="item.product_image"
                                        :alt="item.product_name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="text-charcoal-300 flex h-full w-full items-center justify-center"
                                    >
                                        🍃
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-charcoal-900">
                                        {{ item.product_name }}
                                    </p>
                                    <p class="text-charcoal-400">
                                        Qty: {{ item.quantity }} &times;
                                        {{ formatCurrency(item.unit_price) }}
                                    </p>
                                </div>
                            </div>
                            <span class="font-semibold text-charcoal-900">
                                {{ formatCurrency(item.subtotal) }}
                            </span>
                        </div>
                    </div>

                    <!-- Totals summary -->
                    <div
                        class="mt-6 space-y-2 border-t border-cream-100 pt-4 text-xs"
                    >
                        <div class="text-charcoal-600 flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-medium text-charcoal-900">{{
                                formatCurrency(order.subtotal)
                            }}</span>
                        </div>
                        <div
                            v-if="order.discount_amount > 0"
                            class="flex justify-between font-medium text-emerald-700"
                        >
                            <span>Botanical Promotion Discount</span>
                            <span
                                >-{{
                                    formatCurrency(order.discount_amount)
                                }}</span
                            >
                        </div>
                        <div class="text-charcoal-600 flex justify-between">
                            <span
                                >Delivery ({{
                                    deliveryOption?.name ?? 'Standard Delivery'
                                }})</span
                            >
                            <span
                                v-if="order.delivery_amount === 0"
                                class="font-semibold text-emerald-800"
                                >FREE</span
                            >
                            <span
                                v-else
                                class="font-medium text-charcoal-900"
                                >{{
                                    formatCurrency(order.delivery_amount)
                                }}</span
                            >
                        </div>
                        <div
                            class="flex items-baseline justify-between border-t border-cream-200 pt-4"
                        >
                            <span class="font-serif text-base text-charcoal-900"
                                >Total Paid / Due</span
                            >
                            <span
                                class="text-2xl font-semibold text-charcoal-900"
                            >
                                {{ formatCurrency(order.total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Shipping & Payment Method Grid -->
                <div
                    class="mt-8 grid grid-cols-1 gap-6 border-t border-cream-100 pt-8 text-xs sm:grid-cols-2"
                >
                    <div>
                        <h3
                            class="text-charcoal-500 mb-2 font-semibold tracking-wider uppercase"
                        >
                            Shipping Destination
                        </h3>
                        <p class="font-medium text-charcoal-900">
                            {{ order.customer_name }}
                        </p>
                        <p class="text-charcoal-600">
                            {{ order.shipping_address.line1 }}
                        </p>
                        <p
                            v-if="order.shipping_address.line2"
                            class="text-charcoal-600"
                        >
                            {{ order.shipping_address.line2 }}
                        </p>
                        <p class="text-charcoal-600">
                            {{ order.shipping_address.city }},
                            {{ order.shipping_address.postal_code }}
                        </p>
                        <p class="text-charcoal-600">
                            {{ order.shipping_address.country }}
                        </p>
                    </div>

                    <div>
                        <h3
                            class="text-charcoal-500 mb-2 font-semibold tracking-wider uppercase"
                        >
                            Payment & Delivery Option
                        </h3>
                        <p class="font-medium text-charcoal-900">
                            Payment:
                            {{ paymentOption?.name ?? 'Direct Payment' }}
                        </p>
                        <p class="text-charcoal-600 mt-1">
                            Delivery: {{ deliveryOption?.name ?? 'Standard' }}
                            <span
                                v-if="deliveryOption?.estimated_delivery_label"
                                class="text-charcoal-400"
                            >
                                ({{ deliveryOption.estimated_delivery_label }})
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Back to Shop CTA -->
                <div class="mt-10 text-center">
                    <Link
                        href="/products"
                        class="inline-block rounded-full bg-sage-800 px-8 py-3.5 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-sm transition-all hover:bg-sage-900 hover:shadow-md"
                    >
                        Continue Exploring Botanicals
                    </Link>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
