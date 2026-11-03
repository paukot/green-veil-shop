<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import Pagination from '@/components/storefront/Pagination.vue';
import OrderStatusBadge from '@/components/storefront/OrderStatusBadge.vue';
import PaymentStatusBadge from '@/components/storefront/PaymentStatusBadge.vue';
import { formatCurrency, resolveStorageUrl } from '@/lib/utils';
import type { AccountOrdersPageProps } from '@/types/storefront';
import { show as ordersShow } from '@/actions/App/Http/Controllers/OrderController';
import { index as productsIndex } from '@/routes/products';

defineOptions({ layout: AccountLayout });

defineProps<AccountOrdersPageProps>();
</script>

<template>
    <Head title="Order History | Green Veil" />

    <div class="space-y-6">
        <!-- Section Header -->
        <div
            class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="font-serif text-2xl text-sage-900 sm:text-3xl">
                        Order History
                    </h2>
                    <p class="text-charcoal-600 mt-1 text-xs sm:text-sm">
                        Review and track all your previous botanical rituals and
                        shipments.
                    </p>
                </div>
                <div
                    v-if="orders.meta.total > 0"
                    class="text-charcoal-500 text-xs font-medium"
                >
                    Showing {{ orders.meta.total }}
                    {{ orders.meta.total === 1 ? 'order' : 'orders' }}
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="orders.data.length === 0"
            class="rounded-2xl border border-cream-200 bg-white p-12 text-center shadow-xs"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-cream-100 text-sage-600"
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
                        stroke-width="1.5"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                    />
                </svg>
            </div>
            <h3 class="font-serif text-lg text-sage-900">
                No Orders Placed Yet
            </h3>
            <p
                class="text-charcoal-600 mx-auto mt-2 max-w-md text-xs leading-relaxed sm:text-sm"
            >
                You have not placed any orders yet. Discover our handcrafted
                botanical formulations, cold-pressed oils, and skincare elixirs.
            </p>
            <div class="mt-6">
                <Link
                    :href="productsIndex.url()"
                    class="inline-flex items-center justify-center rounded-full bg-sage-800 px-6 py-3 text-xs font-semibold tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900"
                >
                    Explore Botanical
                </Link>
            </div>
        </div>

        <!-- Orders List -->
        <div v-else class="space-y-4">
            <article
                v-for="order in orders.data"
                :key="order.public_id"
                class="overflow-hidden rounded-2xl border border-cream-200 bg-white shadow-xs transition-shadow hover:shadow-sm"
            >
                <div
                    class="border-b border-cream-100 bg-cream-50/60 p-4 sm:flex sm:items-center sm:justify-between sm:px-6"
                >
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs"
                    >
                        <span class="font-mono font-semibold text-sage-900">
                            {{ order.number }}
                        </span>
                        <span class="text-charcoal-400">•</span>
                        <span class="text-charcoal-600">
                            {{ order.placed_at }}
                        </span>
                        <span
                            v-if="order.delivery_name"
                            class="text-charcoal-500 hidden sm:inline"
                        >
                            • {{ order.delivery_name }}
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2 sm:mt-0">
                        <OrderStatusBadge :status="order.status" />
                        <PaymentStatusBadge :status="order.payment_status" />
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-4">
                            <!-- Order Thumbnail -->
                            <div
                                class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-cream-200 bg-cream-50"
                            >
                                <img
                                    v-if="order.first_item_image"
                                    :src="
                                        resolveStorageUrl(
                                            order.first_item_image,
                                        )
                                    "
                                    :alt="order.number"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                                <div
                                    v-else
                                    class="text-sage-400 flex h-full w-full items-center justify-center"
                                >
                                    <svg
                                        class="h-7 w-7"
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

                            <div>
                                <p
                                    class="text-charcoal-500 text-xs font-medium"
                                >
                                    {{ order.items_count }}
                                    {{
                                        order.items_count === 1
                                            ? 'item'
                                            : 'items'
                                    }}
                                </p>
                                <p
                                    class="mt-0.5 font-serif text-lg font-semibold text-sage-900"
                                >
                                    {{ formatCurrency(order.total) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link
                                :href="ordersShow.url(order.public_id)"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-cream-300 bg-white px-4 py-2.5 text-xs font-semibold tracking-wider text-charcoal-800 uppercase transition-colors hover:bg-cream-100 hover:text-sage-900 sm:w-auto"
                            >
                                <span>View Details</span>
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Pagination Component -->
            <Pagination :links="orders.meta.links" />
        </div>
    </div>
</template>
