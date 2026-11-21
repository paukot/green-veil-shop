<script setup lang="ts">
import { computed } from 'vue';
import { cn, formatCurrency } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        amount: number;
        /** Original price; shown struck through when higher than `amount`. */
        compareAt?: number | null;
        size?: 'sm' | 'md' | 'lg';
        class?: string;
    }>(),
    {
        compareAt: null,
        size: 'md',
    },
);

const isDiscounted = computed(
    () => props.compareAt !== null && props.compareAt > props.amount,
);

const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-3xl',
} as const;
</script>

<template>
    <p :class="cn('flex flex-wrap items-baseline gap-2', props.class)">
        <span
            :class="
                cn(
                    'font-semibold tabular-nums',
                    sizeClasses[size],
                    isDiscounted ? 'text-clay-700' : 'text-ink',
                )
            "
        >
            {{ formatCurrency(amount) }}
        </span>
        <span v-if="isDiscounted" class="sr-only">was</span>
        <s
            v-if="isDiscounted && compareAt !== null"
            :class="
                cn(
                    'text-ink-subtle tabular-nums',
                    size === 'lg' ? 'text-base' : 'text-xs',
                )
            "
        >
            {{ formatCurrency(compareAt) }}
        </s>
    </p>
</template>
