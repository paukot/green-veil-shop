<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    forId?: string;
    label?: string | null;
    required?: boolean;
    subtext?: string | null;
    /** Kept for API compatibility; every label now shares one style. */
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
                'mb-2 flex items-baseline gap-1 text-sm font-medium text-ink',
                props.class,
            )
        "
    >
        <slot>{{ label }}</slot>
        <span v-if="required" class="text-clay-600" aria-hidden="true">*</span>
        <span v-if="subtext" class="text-xs font-normal text-ink-subtle">
            {{ subtext.startsWith('(') ? subtext : `(${subtext})` }}
        </span>
    </label>
</template>
