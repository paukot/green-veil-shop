<script setup lang="ts">
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    hasError?: boolean;
    class?: string;
}

const props = defineProps<Props>();
const model = defineModel<string | number | boolean>();
</script>

<template>
    <input
        v-bind="$attrs"
        v-model="model"
        type="radio"
        :class="
            cn(
                'h-4 w-4 border-cream-300 text-sage-800 transition-colors focus:ring-1 focus:ring-sage-600 focus:ring-offset-0 focus:outline-hidden disabled:opacity-50',
                hasError && 'border-rose-400 focus:ring-rose-500',
                props.class,
            )
        "
    />
</template>
