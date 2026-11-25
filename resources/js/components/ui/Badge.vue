<script setup lang="ts">
import { cn } from '@/lib/utils';

export type BadgeTone =
    | 'neutral'
    | 'brand'
    | 'accent'
    | 'success'
    | 'warning'
    | 'danger'
    | 'inverse';

const props = withDefaults(
    defineProps<{
        tone?: BadgeTone;
        class?: string;
    }>(),
    {
        tone: 'neutral',
    },
);

const toneClasses: Record<BadgeTone, string> = {
    neutral: 'border-border bg-surface-sunken text-ink-muted',
    brand: 'border-rose-200 bg-rose-50 text-rose-800',
    accent: 'border-clay-200 bg-clay-50 text-clay-700',
    success: 'border-success-200 bg-success-50 text-success-700',
    warning: 'border-warning-200 bg-warning-50 text-warning-700',
    danger: 'border-danger-200 bg-danger-50 text-danger-700',
    inverse: 'border-transparent bg-ink text-cream-50',
};
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex h-6 shrink-0 items-center gap-1 rounded-full border px-2 text-xs font-medium whitespace-nowrap select-none',
                toneClasses[tone],
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
