<script setup lang="ts">
import { computed } from 'vue';
import {
    formatStatusLabel,
    getOrderStatusClass,
    getPaymentStatusClass,
} from '@/lib/formatters';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        status: string;
        type?: 'order' | 'payment';
        prefix?: string;
        class?: string;
    }>(),
    {
        type: 'order',
        prefix: '',
    },
);

const label = computed(() => formatStatusLabel(props.status));
const colorClasses = computed(() =>
    props.type === 'payment'
        ? getPaymentStatusClass(props.status)
        : getOrderStatusClass(props.status),
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-medium select-none',
                colorClasses,
                props.class,
            )
        "
    >
        <span v-if="prefix" class="mr-1">{{ prefix }}</span>
        <span>{{ label }}</span>
    </span>
</template>
