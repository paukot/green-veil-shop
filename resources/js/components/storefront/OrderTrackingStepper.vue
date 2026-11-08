<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string; // 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
    paymentStatus: string; // 'unpaid' | 'paid' | 'failed' | 'refunded' | 'partially_refunded'
    placedAt?: string | null;
    paidAt?: string | null;
}>();

type Step = {
    id: string;
    title: string;
    description: string;
    date?: string | null;
    isCompleted: boolean;
    isCurrent: boolean;
};

const isCancelled = computed(() => props.status === 'cancelled');

const steps = computed<Step[]>(() => {
    if (isCancelled.value) return [];

    const isPaid =
        props.paymentStatus === 'paid' ||
        props.paymentStatus === 'partially_refunded';

    const isProcessing =
        props.status === 'processing' ||
        props.status === 'shipped' ||
        props.status === 'delivered';

    const isShipped =
        props.status === 'shipped' || props.status === 'delivered';

    const isDelivered = props.status === 'delivered';

    return [
        {
            id: 'placed',
            title: 'Order Placed',
            description: 'Received by Green Veil',
            date: props.placedAt,
            isCompleted: true,
            isCurrent: false,
        },
        {
            id: 'paid',
            title: 'Payment Confirmed',
            description: isPaid ? 'Authorized & Secured' : 'Pending payment',
            date: props.paidAt,
            isCompleted: isPaid,
            isCurrent: !isPaid,
        },
        {
            id: 'processing',
            title: 'Handcrafted & Packed',
            description: 'Apothecary preparation',
            isCompleted: isProcessing,
            isCurrent:
                isPaid &&
                (props.status === 'pending' || props.status === 'processing'),
        },
        {
            id: 'shipped',
            title: 'Dispatched',
            description: 'In courier transit',
            isCompleted: isShipped,
            isCurrent: props.status === 'shipped',
        },
        {
            id: 'delivered',
            title: 'Delivered',
            description: 'Safely arrived',
            isCompleted: isDelivered,
            isCurrent: props.status === 'delivered',
        },
    ];
});
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-cream-200 bg-white p-6 shadow-xs"
        aria-label="Order Tracking Timeline"
    >
        <div
            class="mb-4 flex items-center justify-between border-b border-cream-100 pb-3"
        >
            <h3 class="font-serif text-base text-sage-900">
                Order Timeline & Tracking
            </h3>
            <span
                v-if="isCancelled"
                class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 uppercase"
            >
                Order Cancelled
            </span>
        </div>

        <!-- Cancelled Notice if applicable -->
        <div
            v-if="isCancelled"
            class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-800"
        >
            This order has been cancelled and will not be prepared or
            dispatched. If payment was captured, a full refund has been
            initiated to your original payment method.
        </div>

        <!-- Timeline Stepper -->
        <div v-else class="relative py-2">
            <ol class="grid grid-cols-1 gap-4 sm:grid-cols-5 sm:gap-2">
                <li
                    v-for="(step, idx) in steps"
                    :key="step.id"
                    class="relative flex items-start gap-3 sm:flex-col sm:items-center sm:text-center"
                >
                    <!-- Connector line (Desktop only) -->
                    <div
                        v-if="idx < steps.length - 1"
                        class="absolute top-4 left-1/2 -z-0 hidden h-0.5 w-full -translate-y-1/2 sm:block"
                        :class="
                            step.isCompleted ? 'bg-emerald-600' : 'bg-cream-200'
                        "
                    />

                    <!-- Icon / Step Circle -->
                    <span
                        class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all"
                        :class="
                            step.isCompleted
                                ? 'bg-emerald-700 text-white shadow-xs ring-4 ring-emerald-50'
                                : step.isCurrent
                                  ? 'border-2 border-sage-700 bg-white text-sage-900 ring-4 ring-sage-100'
                                  : 'text-charcoal-400 border border-cream-300 bg-cream-100'
                        "
                    >
                        <svg
                            v-if="step.isCompleted"
                            class="h-4 w-4"
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
                        <span v-else>{{ idx + 1 }}</span>
                    </span>

                    <!-- Label & Details -->
                    <div class="min-w-0 sm:mt-2">
                        <p
                            class="text-xs font-semibold"
                            :class="
                                step.isCompleted || step.isCurrent
                                    ? 'text-charcoal-900'
                                    : 'text-charcoal-400'
                            "
                        >
                            {{ step.title }}
                        </p>
                        <p class="text-charcoal-500 text-[11px]">
                            {{ step.description }}
                        </p>
                        <p
                            v-if="step.date"
                            class="mt-0.5 font-mono text-[10px] text-sage-700"
                        >
                            {{ step.date }}
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</template>
