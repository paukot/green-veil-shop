<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        /** md: 1rem → 1.5rem, lg: 1.5rem → 2rem, none for edge-to-edge content. */
        padding?: 'none' | 'md' | 'lg';
        tone?: 'raised' | 'sunken';
        as?: string;
        class?: string;
    }>(),
    {
        padding: 'md',
        tone: 'raised',
        as: 'div',
    },
);

const paddingClasses = {
    none: '',
    md: 'p-4 sm:p-6',
    lg: 'p-6 sm:p-8',
} as const;
</script>

<template>
    <component
        :is="as"
        :class="
            cn(
                'rounded-2xl border border-border',
                tone === 'raised' ? 'bg-surface-raised' : 'bg-surface-sunken',
                paddingClasses[padding],
                props.class,
            )
        "
    >
        <slot />
    </component>
</template>
