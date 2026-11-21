<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import Pagination from '@/components/storefront/Pagination.vue';
import OrderStatusBadge from '@/components/storefront/OrderStatusBadge.vue';
import PaymentStatusBadge from '@/components/storefront/PaymentStatusBadge.vue';
import OrderCardSkeleton from '@/components/storefront/skeletons/OrderCardSkeleton.vue';
import { usePageLoading } from '@/composables/usePageLoading';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import type { AccountOrdersPageProps } from '@/types/storefront';
import { show as ordersShow } from '@/actions/App/Http/Controllers/OrderController';
import { index as productsIndex } from '@/routes/products';
import { ChevronRightIcon, FileTextIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

defineOptions({ layout: AccountLayout });

defineProps<AccountOrdersPageProps>();

const { isLoading } = usePageLoading();
</script>

<template>
    <Head title="Order History" />

    <section class="flex flex-col gap-6">
        <div class="flex items-baseline justify-between gap-4">
            <h2 class="font-serif text-3xl text-ink">Orders</h2>
            <p v-if="orders.meta.total > 0" class="text-sm text-ink-subtle">
                {{ orders.meta.total }}
                {{ orders.meta.total === 1 ? 'order' : 'orders' }}
            </p>
        </div>

        <OrderCardSkeleton v-if="isLoading" :count="orders.data.length || 3" />

        <EmptyState
            v-else-if="orders.data.length === 0"
            title="No orders yet"
            description="When you place an order it will appear here, with tracking and receipts."
        >
            <template #icon>
                <FileTextIcon aria-hidden="true" />
            </template>
            <template #actions>
                <Button :href="productsIndex.url()">Start shopping</Button>
            </template>
        </EmptyState>

        <div v-else class="flex flex-col gap-4">
            <Card
                v-for="order in orders.data"
                :key="order.public_id"
                as="article"
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6"
            >
                <div
                    class="size-16 shrink-0 overflow-hidden rounded-lg bg-surface-sunken"
                >
                    <img
                        v-if="order.first_item_image"
                        :src="resolveStorageUrl(order.first_item_image)"
                        :alt="order.number"
                        class="size-full object-cover"
                        loading="lazy"
                        @error="showPlaceholderOnError"
                    />
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-mono text-sm font-semibold text-ink">
                            {{ order.number }}
                        </p>
                        <OrderStatusBadge :status="order.status" />
                        <PaymentStatusBadge :status="order.payment_status" />
                    </div>
                    <p
                        class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-muted"
                    >
                        <span>{{ order.placed_at }}</span>
                        <span>
                            {{ order.items_count }}
                            {{ order.items_count === 1 ? 'item' : 'items' }}
                        </span>
                        <span v-if="order.delivery_name">{{
                            order.delivery_name
                        }}</span>
                    </p>
                </div>

                <div
                    class="flex items-center justify-between gap-4 sm:justify-end"
                >
                    <p class="text-lg font-semibold text-ink tabular-nums">
                        {{ formatCurrency(order.total) }}
                    </p>
                    <Button
                        :href="ordersShow.url(order.public_id)"
                        variant="outline"
                        size="sm"
                    >
                        Details
                        <template #trailing>
                            <ChevronRightIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </template>
                    </Button>
                </div>
            </Card>

            <Pagination
                :links="orders.meta.links"
                :only="['orders']"
                class="pt-4"
            />
        </div>
    </section>
</template>
