<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import { cn } from '@/lib/utils';

export type ButtonVariant =
    | 'primary'
    | 'accent'
    | 'secondary'
    | 'outline'
    | 'ghost'
    | 'danger'
    | 'link';

interface Props {
    type?: 'button' | 'submit' | 'reset';
    variant?: ButtonVariant;
    /** sm is a 2rem compact control; md and lg are 3rem (the touch-target size). */
    size?: 'sm' | 'md' | 'lg';
    loading?: boolean;
    disabled?: boolean;
    /** Stretch to the full width of the container. */
    block?: boolean;
    href?: string;
    /** Render a plain anchor (external links) instead of an Inertia Link. */
    as?: 'a';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
    block: false,
});

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-rose-900 text-cream-50 hover:bg-rose-800',
    accent: 'bg-clay-600 text-white hover:bg-clay-700',
    secondary: 'bg-surface-sunken text-ink hover:bg-cream-200',
    outline:
        'border border-border-strong bg-transparent text-ink hover:border-ink hover:bg-surface-raised',
    ghost: 'text-ink hover:bg-surface-sunken',
    danger: 'bg-danger-600 text-white hover:bg-danger-700',
    link: 'text-rose-800 underline-offset-4 hover:underline',
};

const sizeClasses = computed(() => {
    if (props.variant === 'link') {
        return 'gap-2 text-sm font-medium';
    }

    return {
        sm: 'h-8 gap-2 px-4 text-xs',
        md: 'h-12 gap-2 px-6 text-xs',
        lg: 'h-12 gap-2 px-8 text-sm',
    }[props.size];
});

const isInactive = computed(() => props.disabled || props.loading);

const tag = computed(() => {
    if (!props.href) {
        return 'button';
    }

    return props.as === 'a' ? 'a' : Link;
});

const classes = computed(() =>
    cn(
        'inline-flex shrink-0 items-center justify-center rounded-full font-semibold tracking-wider whitespace-nowrap uppercase transition-colors select-none',
        props.variant === 'link' && 'tracking-normal normal-case',
        variantClasses[props.variant],
        sizeClasses.value,
        props.block && 'w-full',
        isInactive.value
            ? 'pointer-events-none cursor-not-allowed opacity-50'
            : 'active:translate-y-px',
        props.class,
    ),
);
</script>

<template>
    <component
        :is="tag"
        :href="href"
        :type="href ? undefined : type"
        :disabled="href ? undefined : isInactive"
        :aria-busy="loading || undefined"
        :class="classes"
    >
        <LoadingSpinner v-if="loading" size="sm" />
        <slot name="icon" v-else />
        <slot />
        <slot name="trailing" />
    </component>
</template>
