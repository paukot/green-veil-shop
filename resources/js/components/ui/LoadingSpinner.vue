<script setup lang="ts">
import { LoaderCircleIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        size?: 'xs' | 'sm' | 'md' | 'lg';
        class?: string;
    }>(),
    {
        size: 'md',
    },
);

const sizeClasses = {
    xs: 'size-3',
    sm: 'size-4',
    md: 'size-4',
    lg: 'size-6',
} as const;
</script>

<template>
    <LoaderCircleIcon
        :class="cn('animate-spin text-current', sizeClasses[size], props.class)"
        aria-hidden="true"
    />
</template>
