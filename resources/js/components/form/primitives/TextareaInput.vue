<script setup lang="ts">
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    hasError?: boolean;
    rounded?: 'md' | 'lg' | 'xl';
    rows?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rounded: 'xl',
    rows: 3,
});

const model = defineModel<string | null>();
</script>

<template>
    <textarea
        v-bind="$attrs"
        v-model="model"
        :rows="rows"
        :class="
            cn(
                'placeholder-charcoal-400 block w-full border bg-cream-50/50 px-4 py-2.5 text-sm text-charcoal-900 transition-colors focus:bg-white focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                rounded === 'md' && 'rounded-md',
                rounded === 'lg' && 'rounded-lg',
                rounded === 'xl' && 'rounded-xl',
                hasError
                    ? 'border-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                    : 'border-cream-300 focus:border-sage-600 focus:ring-1 focus:ring-sage-600',
                props.class,
            )
        "
    ></textarea>
</template>
