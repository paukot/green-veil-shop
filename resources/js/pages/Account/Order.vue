<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import OrderStatusBadge from '@/components/storefront/OrderStatusBadge.vue';
import PaymentStatusBadge from '@/components/storefront/PaymentStatusBadge.vue';
import OrderTrackingStepper from '@/components/storefront/OrderTrackingStepper.vue';
import { useCart } from '@/composables/useCart';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import type { AccountOrderDetailProps } from '@/types/storefront';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/OrderController';
import { retry as checkoutRetry } from '@/actions/App/Http/Controllers/CheckoutResultController';
import {
    CheckIcon,
    ChevronLeftIcon,
    InfoIcon,
    PrinterIcon,
    RefreshCwIcon,
    XIcon,
} from '@lucide/vue';
import PriceBreakdown from '@/components/storefront/checkout/PriceBreakdown.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import IconButton from '@/components/ui/IconButton.vue';

defineOptions({ layout: AccountLayout });

const props = defineProps<AccountOrderDetailProps>();
const cart = useCart();
const reorderSuccessMessage = ref(false);

const isUnpaid = computed(
    () =>
        props.order.payment_status === 'unpaid' &&
        props.order.status !== 'cancelled',
);

const isStripe = computed(() => props.paymentOption?.driver === 'stripe');

let reorderTimer: ReturnType<typeof setTimeout> | null = null;
onUnmounted(() => {
    if (reorderTimer) clearTimeout(reorderTimer);
});

function handleReorder() {
    let addedCount = 0;
    for (const item of props.items) {
        if (item.product_id) {
            cart.addItem(
                {
                    id: item.product_id,
                    name: item.product_name,
                    slug: item.product_slug || '',
                    price: item.unit_price,
                    main_image: item.product_image,
                    sku: item.product_sku,
                },
                item.quantity,
            );
            addedCount++;
        }
    }
    if (addedCount > 0) {
        reorderSuccessMessage.value = true;
        cart.openDrawer();
        if (reorderTimer) clearTimeout(reorderTimer);
        reorderTimer = setTimeout(() => {
            reorderSuccessMessage.value = false;
        }, 4000);
    }
}

function handlePrint() {
    window.print();
}
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <div class="flex flex-col gap-6">
        <Button
            :href="ordersIndex.url()"
            variant="link"
            class="self-start print:hidden"
        >
            <template #icon>
                <ChevronLeftIcon class="size-4" aria-hidden="true" />
            </template>
            All orders
        </Button>

        <div
            v-if="reorderSuccessMessage"
            class="flex items-center gap-3 rounded-2xl bg-success-50 p-4 text-sm text-success-700 print:hidden"
            role="status"
        >
            <CheckIcon class="size-4 shrink-0" aria-hidden="true" />
            <p class="flex-1 font-medium">
                Items from this order were added to your bag.
            </p>
            <IconButton
                label="Dismiss"
                size="sm"
                class="text-success-700 hover:bg-success-100"
                @click="reorderSuccessMessage = false"
            >
                <XIcon class="size-4" aria-hidden="true" />
            </IconButton>
        </div>

        <Card
            padding="lg"
            class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-serif text-3xl text-ink">
                        Order {{ order.number }}
                    </h2>
                    <OrderStatusBadge :status="order.status" />
                    <PaymentStatusBadge :status="order.payment_status" />
                </div>
                <p
                    class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-muted"
                >
                    <span>Placed {{ order.placed_at }}</span>
                    <span v-if="order.paid_at">Paid {{ order.paid_at }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <Button variant="outline" size="sm" @click="handlePrint">
                    <template #icon>
                        <PrinterIcon class="size-4" aria-hidden="true" />
                    </template>
                    Print
                </Button>
                <Button
                    v-if="order.status !== 'cancelled' && items.length > 0"
                    variant="outline"
                    size="sm"
                    @click="handleReorder"
                >
                    <template #icon>
                        <RefreshCwIcon class="size-4" aria-hidden="true" />
                    </template>
                    Reorder
                </Button>
                <Link
                    v-if="isUnpaid && isStripe"
                    :href="checkoutRetry.url(order.public_id)"
                    method="post"
                    as="button"
                    class="inline-flex h-8 items-center rounded-full bg-sage-900 px-4 text-xs font-semibold tracking-wider text-cream-50 uppercase transition-colors hover:bg-sage-800"
                >
                    Complete payment
                </Link>
            </div>
        </Card>

        <OrderTrackingStepper
            :status="order.status"
            :payment-status="order.payment_status"
            :placed-at="order.placed_at"
            :paid-at="order.paid_at"
        />

        <div
            v-if="isUnpaid && paymentOption?.instructions"
            class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-6"
        >
            <InfoIcon
                class="mt-1 size-4 shrink-0 text-warning-700"
                aria-hidden="true"
            />
            <div class="flex flex-col gap-1 text-sm">
                <h3 class="font-medium text-warning-800">
                    How to pay: {{ paymentOption.name }}
                </h3>
                <p class="whitespace-pre-line text-warning-700">
                    {{ paymentOption.instructions }}
                </p>
            </div>
        </div>

        <Card padding="lg" class="flex flex-col gap-6">
            <h3 class="font-serif text-2xl text-ink">
                Items
                <span class="font-sans text-sm text-ink-subtle">{{
                    items.length
                }}</span>
            </h3>
            <ul class="flex flex-col divide-y divide-border">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-center gap-4 py-4 first:pt-0"
                >
                    <div
                        class="size-16 shrink-0 overflow-hidden rounded-lg bg-surface-sunken"
                    >
                        <img
                            v-if="item.product_image"
                            :src="resolveStorageUrl(item.product_image)"
                            :alt="item.product_name"
                            class="size-full object-cover"
                            @error="showPlaceholderOnError"
                        />
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ item.product_name }}
                        </p>
                        <p class="text-xs text-ink-subtle tabular-nums">
                            {{ item.quantity }} ×
                            {{ formatCurrency(item.unit_price) }}
                            <template v-if="item.product_sku"
                                >· {{ item.product_sku }}</template
                            >
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-ink tabular-nums">
                        {{ formatCurrency(item.subtotal) }}
                    </p>
                </li>
            </ul>
            <div class="border-t border-border pt-6 sm:ml-auto sm:w-80">
                <PriceBreakdown
                    :subtotal="order.subtotal"
                    :discount-amount="order.discount_amount"
                    :shipping="order.delivery_amount"
                    :total="order.total"
                    :note="`Includes ${formatCurrency(order.tax_amount)} VAT`"
                />
            </div>
        </Card>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <Card class="flex flex-col gap-3">
                <h3
                    class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                >
                    Delivery
                </h3>
                <address
                    class="flex flex-col gap-1 text-sm text-ink-muted not-italic"
                >
                    <span class="font-medium text-ink">{{
                        order.customer_name
                    }}</span>
                    <span>{{ order.shipping_address.line1 }}</span>
                    <span v-if="order.shipping_address.line2">
                        {{ order.shipping_address.line2 }}
                    </span>
                    <span>
                        {{ order.shipping_address.city }},
                        {{ order.shipping_address.postal_code }}
                    </span>
                    <span>{{ order.shipping_address.country }}</span>
                    <span v-if="order.customer_phone">{{
                        order.customer_phone
                    }}</span>
                </address>
                <p
                    v-if="deliveryOption"
                    class="border-t border-border pt-3 text-sm text-ink-muted"
                >
                    {{ deliveryOption.name }}
                    <template v-if="deliveryOption.estimated_delivery_label">
                        · {{ deliveryOption.estimated_delivery_label }}
                    </template>
                </p>
            </Card>

            <Card class="flex flex-col gap-3">
                <h3
                    class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                >
                    Payment
                </h3>
                <p class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-medium text-ink">
                        {{ paymentOption?.name ?? 'Standard payment' }}
                    </span>
                    <PaymentStatusBadge :status="order.payment_status" />
                </p>
                <div
                    v-if="order.notes"
                    class="flex flex-col gap-1 border-t border-border pt-3 text-sm"
                >
                    <span class="font-medium text-ink">Order notes</span>
                    <p class="text-ink-muted italic">“{{ order.notes }}”</p>
                </div>
            </Card>
        </div>
    </div>
</template>
