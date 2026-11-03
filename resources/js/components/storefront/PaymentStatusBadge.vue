<script setup lang="ts">
import { computed } from 'vue';
import { formatStatusLabel, getPaymentStatusClass } from '@/lib/formatters';
import { cn } from '@/lib/utils';

interface Props {
    status: string;
    showPrefix?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showPrefix: true,
});

const label = computed(() => formatStatusLabel(props.status));
const colorClasses = computed(() => getPaymentStatusClass(props.status));
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
        <span v-if="showPrefix" class="mr-1">Payment:</span>
        <span>{{ label }}</span>
    </span>
</template>
