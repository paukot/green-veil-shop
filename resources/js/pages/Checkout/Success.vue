<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, usePoll } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import OrderTrackingStepper from '@/components/storefront/OrderTrackingStepper.vue';
import type { OrderDetailData } from '@/types/storefront';
import { index as productsIndex } from '@/routes/products';
import PriceBreakdown from '@/components/storefront/checkout/PriceBreakdown.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Container from '@/components/ui/Container.vue';
import {
    formatCurrency,
    resolveStorageUrl,
    showPlaceholderOnError,
} from '@/lib/utils';
import { useCart } from '@/composables/useCart';
import { CheckIcon, ClockIcon, InfoIcon, PrinterIcon } from '@lucide/vue';

defineOptions({
    layout: StorefrontLayout,
});

const props = defineProps<{
    order: OrderDetailData;
}>();

const cart = useCart();

function handlePrint() {
    window.print();
}

onMounted(() => {
    cart.clearCart();
});

const pollCount = ref(0);
const maxPolls = 10;

const isStripe = computed(() => props.order.paymentOption?.driver === 'stripe');
const isPaid = computed(() => props.order.payment_status === 'paid');
const isPendingPayment = computed(
    () => props.order.payment_status === 'unpaid' && isStripe.value,
);

const { stop } = usePoll(
    2500,
    {
        only: ['order'],
        onFinish: () => {
            pollCount.value++;
            if (
                props.order.payment_status !== 'unpaid' ||
                !isStripe.value ||
                pollCount.value >= maxPolls
            ) {
                stop();
            }
        },
    },
    {
        autoStart: isPendingPayment.value,
    },
);
</script>

<template>
    <Head :title="`Order ${order.number} Confirmed`" />

    <Container size="narrow" class="py-12 lg:py-16">
        <Card padding="lg" class="flex flex-col gap-8">
            <header class="flex flex-col items-center gap-4 text-center">
                <span
                    :class="[
                        'flex size-16 items-center justify-center rounded-full',
                        isPaid
                            ? 'bg-success-50 text-success-700'
                            : isPendingPayment
                              ? 'bg-sage-50 text-sage-700'
                              : 'bg-warning-50 text-warning-700',
                    ]"
                >
                    <CheckIcon
                        v-if="isPaid"
                        class="size-6"
                        aria-hidden="true"
                    />
                    <LoadingSpinner v-else-if="isPendingPayment" size="lg" />
                    <ClockIcon v-else class="size-6" aria-hidden="true" />
                </span>

                <Badge v-if="isPaid" tone="success">Payment confirmed</Badge>
                <Badge v-else-if="isPendingPayment" tone="brand">
                    Confirming payment
                </Badge>
                <Badge
                    v-else-if="order.paymentOption?.driver === 'bank_transfer'"
                    tone="warning"
                >
                    Awaiting bank transfer
                </Badge>
                <Badge v-else tone="brand">Pay on delivery</Badge>

                <h1
                    class="font-serif text-4xl tracking-tight text-ink lg:text-5xl"
                >
                    Thank you, {{ order.customer_name }}
                </h1>
                <p class="flex flex-col gap-1 text-sm text-ink-muted">
                    <span>
                        Order
                        <span class="font-mono font-semibold text-ink">{{
                            order.number
                        }}</span>
                    </span>
                    <span>
                        A receipt is on its way to
                        <span class="font-medium text-ink">{{
                            order.customer_email
                        }}</span>
                    </span>
                </p>
            </header>

            <p
                v-if="isPendingPayment"
                class="rounded-lg bg-sage-50 p-4 text-center text-sm text-sage-800"
                role="status"
            >
                <template v-if="pollCount < maxPolls">
                    Waiting for confirmation from the payment processor…
                </template>
                <template v-else>
                    Your payment is still being verified. We'll email
                    {{ order.customer_email }} as soon as it completes.
                </template>
            </p>

            <OrderTrackingStepper
                :status="order.status"
                :payment-status="order.payment_status"
                :placed-at="order.placed_at"
                :paid-at="order.paid_at"
            />

            <div
                v-if="order.paymentOption?.instructions"
                class="flex items-start gap-3 rounded-lg bg-surface-sunken p-4"
            >
                <InfoIcon
                    class="mt-1 size-4 shrink-0 text-sage-700"
                    aria-hidden="true"
                />
                <div class="flex flex-col gap-1 text-sm">
                    <h2 class="font-medium text-ink">
                        {{ order.paymentOption.name }} instructions
                    </h2>
                    <p class="whitespace-pre-line text-ink-muted">
                        {{ order.paymentOption.instructions }}
                    </p>
                </div>
            </div>

            <section class="flex flex-col gap-4 border-t border-border pt-8">
                <h2 class="font-serif text-2xl text-ink">Your order</h2>
                <ul class="flex flex-col divide-y divide-border">
                    <li
                        v-for="item in order.items"
                        :key="item.id"
                        class="flex items-center gap-4 py-4"
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
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-ink tabular-nums">
                            {{ formatCurrency(item.subtotal) }}
                        </p>
                    </li>
                </ul>
                <PriceBreakdown
                    :subtotal="order.subtotal"
                    :discount-amount="order.discount_amount"
                    :shipping="order.delivery_amount"
                    :total="order.total"
                    :total-label="isPaid ? 'Total paid' : 'Total due'"
                    :note="order.deliveryOption?.name"
                />
            </section>

            <section
                class="grid grid-cols-1 gap-6 border-t border-border pt-8 text-sm sm:grid-cols-2"
            >
                <div class="flex flex-col gap-2">
                    <h3
                        class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                    >
                        Shipping to
                    </h3>
                    <address
                        class="flex flex-col gap-1 text-ink-muted not-italic"
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
                    </address>
                </div>
                <div class="flex flex-col gap-2">
                    <h3
                        class="text-xs font-semibold tracking-wider text-ink-subtle uppercase"
                    >
                        Payment & delivery
                    </h3>
                    <p class="flex flex-col gap-1 text-ink-muted">
                        <span class="font-medium text-ink">
                            {{ order.paymentOption?.name ?? 'Direct payment' }}
                        </span>
                        <span>
                            {{
                                order.deliveryOption?.name ??
                                'Standard delivery'
                            }}
                            <template
                                v-if="
                                    order.deliveryOption
                                        ?.estimated_delivery_label
                                "
                            >
                                ·
                                {{
                                    order.deliveryOption
                                        .estimated_delivery_label
                                }}
                            </template>
                        </span>
                    </p>
                </div>
            </section>

            <div
                class="flex flex-col-reverse justify-center gap-3 border-t border-border pt-8 sm:flex-row print:hidden"
            >
                <Button variant="outline" @click="handlePrint">
                    <template #icon>
                        <PrinterIcon class="size-4" aria-hidden="true" />
                    </template>
                    Print receipt
                </Button>
                <Button :href="productsIndex.url()">Continue shopping</Button>
            </div>
        </Card>
    </Container>
</template>
