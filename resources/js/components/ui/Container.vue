<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        /** default for pages, narrow for reading and single-column flows. */
        size?: 'default' | 'narrow' | 'compact';
        as?: string;
        class?: string;
    }>(),
    {
        size: 'default',
        as: 'div',
    },
);

const sizeClasses = {
    default: 'max-w-7xl',
    narrow: 'max-w-3xl',
    compact: 'max-w-md',
} as const;
</script>

<template>
    <component
        :is="as"
        :class="
            cn(
                'mx-auto w-full px-4 sm:px-6 lg:px-8',
                sizeClasses[size],
                props.class,
            )
        "
    >
        <slot />
    </component>
</template>
