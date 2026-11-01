<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    forId?: string;
    label?: string | null;
    required?: boolean;
    subtext?: string | null;
    variant?: 'default' | 'uppercase';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <label
        :for="forId"
        :class="
            cn(
                'block text-xs font-medium text-charcoal-700',
                variant === 'uppercase' && 'mb-1.5 tracking-wider uppercase',
                variant === 'default' && 'mb-1',
                props.class,
            )
        "
    >
        <slot>{{ label }}</slot>
        <span
            v-if="required"
            class="ml-0.5 font-serif text-rose-500"
            aria-hidden="true"
            >*</span
        >
        <span
            v-if="subtext"
            class="text-charcoal-400 ml-1 font-sans text-xs font-normal"
        >
            {{ subtext.startsWith('(') ? subtext : `(${subtext})` }}
        </span>
    </label>
</template>
