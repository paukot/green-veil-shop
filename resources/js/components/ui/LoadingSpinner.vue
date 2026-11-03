<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    size?: 'xs' | 'sm' | 'md' | 'lg';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'h-3 w-3';
        case 'sm':
            return 'h-3.5 w-3.5';
        case 'lg':
            return 'h-6 w-6';
        default:
            return 'h-4 w-4';
    }
});
</script>

<template>
    <svg
        :class="cn('animate-spin text-current', sizeClasses, props.class)"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
        />
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
    </svg>
</template>
