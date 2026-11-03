<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import OrderStatusBadge from '@/components/storefront/OrderStatusBadge.vue';
import PaymentStatusBadge from '@/components/storefront/PaymentStatusBadge.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type { AccountOrderDetailProps } from '@/types/storefront';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { retry as checkoutRetry } from '@/actions/App/Http/Controllers/CheckoutResultController';

defineOptions({ layout: AccountLayout });

const props = defineProps<AccountOrderDetailProps>();

const isUnpaid = computed(
    () =>
        props.order.payment_status === 'unpaid' &&
        props.order.status !== 'cancelled',
);

const isStripe = computed(() => props.paymentOption?.driver === 'stripe');
</script>

<template>
    <Head :title="`Order ${order.number} | Green Veil`" />

    <div class="space-y-6">
        <!-- Navigation back -->
        <div>
            <Link
                :href="ordersIndex.url()"
                class="inline-flex items-center gap-2 text-xs font-medium text-sage-700 transition-colors hover:text-sage-900"
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
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                <span>Back to all orders</span>
            </Link>
        </div>

        <!-- Header Card -->
        <div
            class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2
                            class="font-serif text-2xl text-sage-900 sm:text-3xl"
                        >
                            Order {{ order.number }}
                        </h2>
                        <OrderStatusBadge
                            :status="order.status"
                            class="px-3 text-xs"
                        />
                        <PaymentStatusBadge
                            :status="order.payment_status"
                            class="px-3 text-xs"
                        />
                    </div>
                    <p class="text-charcoal-600 mt-2 text-xs sm:text-sm">
                        Placed on
                        <span class="font-medium text-charcoal-900">{{
                            order.placed_at
                        }}</span>
                        <span v-if="order.paid_at" class="text-charcoal-500">
                            • Paid on {{ order.paid_at }}
                        </span>
                    </p>
                </div>

                <!-- Retry Payment CTA if pending Stripe payment -->
                <div v-if="isUnpaid && isStripe" class="shrink-0">
                    <Link
                        :href="checkoutRetry.url(order.public_id)"
                        method="post"
                        as="button"
                        class="inline-flex w-full items-center justify-center rounded-full bg-sage-800 px-6 py-3 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 sm:w-auto"
                    >
                        Complete Payment
                    </Link>
                </div>
            </div>
        </div>

        <!-- Unpaid / Offline Payment Instructions Alert -->
        <div
            v-if="isUnpaid && paymentOption?.instructions"
            class="rounded-2xl border border-amber-200 bg-amber-50/60 p-6 shadow-xs"
        >
            <div class="flex items-start gap-3">
                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div>
                    <h3
                        class="text-xs font-semibold tracking-wider text-amber-900 uppercase"
                    >
                        Payment Instructions: {{ paymentOption.name }}
                    </h3>
                    <p
                        class="mt-1 text-xs leading-relaxed whitespace-pre-line text-amber-800"
                    >
                        {{ paymentOption.instructions }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div
            class="overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs"
        >
            <div class="border-b border-cream-100 bg-cream-50/60 px-6 py-4">
                <h3 class="font-serif text-base text-sage-900">
                    Botanical Items ({{ items.length }})
                </h3>
            </div>

            <div class="divide-y divide-cream-100 p-6">
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-center gap-4 py-4 first:pt-0 last:pb-0"
                >
                    <div
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                    >
                        <img
                            v-if="item.product_image"
                            :src="resolveStorageUrl(item.product_image)"
                            :alt="item.product_name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="text-sage-400 flex h-full w-full items-center justify-center"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.25"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h4
                            class="truncate font-serif text-sm font-medium text-sage-900"
                        >
                            {{ item.product_name }}
                        </h4>
                        <p class="text-charcoal-500 text-xs">
                            SKU: {{ item.product_sku ?? 'N/A' }}
                        </p>
                        <p class="text-charcoal-600 mt-1 text-xs">
                            Quantity: {{ item.quantity }} ×
                            {{ formatCurrency(item.unit_price) }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p
                            class="font-serif text-sm font-semibold text-sage-900"
                        >
                            {{ formatCurrency(item.subtotal) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Totals Breakdown -->
            <div class="border-t border-cream-100 bg-cream-50/40 p-6">
                <div class="ml-auto max-w-xs space-y-2 text-xs">
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
                        <span>Promotion Discount</span>
                        <span
                            >-{{ formatCurrency(order.discount_amount) }}</span
                        >
                    </div>
                    <div class="text-charcoal-600 flex justify-between">
                        <span>Delivery</span>
                        <span class="font-medium text-charcoal-900">
                            {{
                                order.delivery_amount > 0
                                    ? formatCurrency(order.delivery_amount)
                                    : 'Free'
                            }}
                        </span>
                    </div>
                    <div
                        class="text-charcoal-500 flex justify-between text-[11px]"
                    >
                        <span>Tax (Included VAT)</span>
                        <span>{{ formatCurrency(order.tax_amount) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-cream-200 pt-3 text-sm font-semibold text-sage-900"
                    >
                        <span class="font-serif">Total</span>
                        <span class="text-base">{{
                            formatCurrency(order.total)
                        }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Grid: Shipping & Payment Information -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Shipping Details -->
            <div
                class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs"
            >
                <h3
                    class="text-charcoal-500 text-xs font-semibold tracking-wider uppercase"
                >
                    Delivery Destination
                </h3>
                <div class="mt-3 space-y-1 text-xs text-charcoal-700">
                    <p class="text-sm font-medium text-charcoal-900">
                        {{ order.customer_name }}
                    </p>
                    <p>{{ order.shipping_address.line1 }}</p>
                    <p v-if="order.shipping_address.line2">
                        {{ order.shipping_address.line2 }}
                    </p>
                    <p>
                        {{ order.shipping_address.city }},
                        {{ order.shipping_address.postal_code }}
                    </p>
                    <p>{{ order.shipping_address.country }}</p>
                    <p
                        v-if="order.customer_phone"
                        class="text-charcoal-500 pt-1"
                    >
                        Phone: {{ order.customer_phone }}
                    </p>
                </div>

                <div
                    v-if="deliveryOption"
                    class="mt-4 border-t border-cream-100 pt-3 text-xs"
                >
                    <span class="font-medium text-charcoal-900">Method:</span>
                    <span class="text-charcoal-600 ml-1">
                        {{ deliveryOption.name }}
                        <template
                            v-if="deliveryOption.estimated_delivery_label"
                        >
                            ({{ deliveryOption.estimated_delivery_label }})
                        </template>
                    </span>
                </div>
            </div>

            <!-- Payment Details -->
            <div
                class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs"
            >
                <h3
                    class="text-charcoal-500 text-xs font-semibold tracking-wider uppercase"
                >
                    Payment Summary
                </h3>
                <div class="mt-3 space-y-2 text-xs text-charcoal-700">
                    <div>
                        <span class="font-medium text-charcoal-900"
                            >Method:</span
                        >
                        <span class="text-charcoal-600 ml-1">{{
                            paymentOption?.name ?? 'Standard Payment'
                        }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-charcoal-900"
                            >Status:</span
                        >
                        <PaymentStatusBadge
                            :status="order.payment_status"
                            :show-prefix="false"
                            class="ml-1 px-2 text-[10px]"
                        />
                    </div>
                    <div v-if="order.notes" class="pt-2">
                        <span class="font-medium text-charcoal-900"
                            >Order Notes:</span
                        >
                        <p class="text-charcoal-600 mt-0.5 italic">
                            "{{ order.notes }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
