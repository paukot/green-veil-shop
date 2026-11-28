<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';
import { computed } from 'vue';
import { CheckIcon } from '@lucide/vue';

const props = defineProps<{
    status: string; // 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
    paymentStatus: string; // 'unpaid' | 'paid' | 'failed' | 'refunded' | 'partially_refunded'
    placedAt?: string | null;
    paidAt?: string | null;
}>();

type Step = {
    id: string;
    title: string;
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
            date: props.placedAt,
            isCompleted: true,
            isCurrent: false,
        },
        {
            id: 'paid',
            title: isPaid ? 'Payment Confirmed' : 'Pending payment',
            date: props.paidAt,
            isCompleted: isPaid,
            isCurrent: !isPaid,
        },
        {
            id: 'processing',
            title: 'Handcrafted & Packed',
            isCompleted: isProcessing,
            isCurrent:
                isPaid &&
                (props.status === 'pending' || props.status === 'processing'),
        },
        {
            id: 'shipped',
            title: 'Dispatched',
            isCompleted: isShipped,
            isCurrent: props.status === 'shipped',
        },
        {
            id: 'delivered',
            title: 'Delivered',
            isCompleted: isDelivered,
            isCurrent: props.status === 'delivered',
        },
    ];
});
</script>

<template>
    <section
        class="flex flex-col gap-6 rounded-2xl border border-border p-6"
        aria-label="Order progress"
    >
        <div class="flex items-center justify-between gap-4">
            <h3 class="font-serif text-xl text-ink">Order progress</h3>
            <Badge v-if="isCancelled" tone="danger"> Cancelled </Badge>
        </div>

        <p
            v-if="isCancelled"
            class="rounded-lg bg-danger-50 p-4 text-sm text-danger-700"
        >
            This order was cancelled and won't be dispatched. If a payment was
            taken, a full refund has been issued to the original payment method.
        </p>

        <ol
            v-else
            aria-label="Order steps"
            class="grid grid-cols-1 gap-6 sm:grid-cols-5 sm:gap-2"
        >
            <li
                v-for="(step, idx) in steps"
                :key="step.id"
                class="relative flex items-start gap-4 sm:flex-col sm:items-center sm:gap-3 sm:text-center"
                :aria-current="step.isCurrent ? 'step' : undefined"
            >
                <div
                    v-if="idx < steps.length - 1"
                    :class="[
                        'absolute top-4 left-1/2 hidden h-px w-full sm:block',
                        step.isCompleted ? 'bg-rose-700' : 'bg-border-strong',
                    ]"
                    aria-hidden="true"
                />
                <span
                    :class="[
                        'relative flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                        step.isCompleted
                            ? 'bg-rose-800 text-cream-50'
                            : step.isCurrent
                              ? 'border-2 border-rose-800 bg-surface-raised text-rose-900'
                              : 'border border-border-strong bg-surface-sunken text-ink-subtle',
                    ]"
                >
                    <CheckIcon
                        v-if="step.isCompleted"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <span v-else>{{ idx + 1 }}</span>
                </span>
                <div class="flex min-w-0 flex-col gap-1">
                    <p
                        :class="[
                            'text-sm font-medium',
                            step.isCompleted || step.isCurrent
                                ? 'text-ink'
                                : 'text-ink-subtle',
                        ]"
                    >
                        {{ step.title }}
                    </p>
                    <p
                        v-if="step.date"
                        class="text-xs text-rose-700 tabular-nums"
                    >
                        {{ step.date }}
                    </p>
                </div>
            </li>
        </ol>
    </section>
</template>
