<script setup lang="ts">
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import {
    formatStatusLabel,
    getOrderStatusTone,
    getPaymentStatusTone,
} from '@/lib/formatters';

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
const tone = computed(() =>
    props.type === 'payment'
        ? getPaymentStatusTone(props.status)
        : getOrderStatusTone(props.status),
);
</script>

<template>
    <Badge :tone="tone" :class="props.class">
        <span v-if="prefix">{{ prefix }}</span>
        <span>{{ label }}</span>
    </Badge>
</template>
