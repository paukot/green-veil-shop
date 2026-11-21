<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { LoaderCircleIcon } from '@lucide/vue';

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
    <LoaderCircleIcon
        :class="cn('animate-spin text-current', sizeClasses, props.class)"
        aria-hidden="true"
    />
</template>
