<script setup lang="ts">
import { computed } from 'vue';
import { formatStatusLabel, getOrderStatusClass } from '@/lib/formatters';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: string;
    class?: string;
}>();

const label = computed(() => formatStatusLabel(props.status));
const colorClasses = computed(() => getOrderStatusClass(props.status));
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
        {{ label }}
    </span>
</template>
