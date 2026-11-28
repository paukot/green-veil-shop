<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    /** Accessible name; icon-only buttons have no visible text. */
    label: string;
    /** sm is 2rem, md is 3rem (the touch-target size). */
    size?: 'sm' | 'md';
    /** ghost for toolbars, outline on surfaces, solid for primary, overlay on images. */
    variant?: 'ghost' | 'outline' | 'solid' | 'overlay';
    type?: 'button' | 'submit';
    href?: string;
    disabled?: boolean;
    pressed?: boolean;
    /** Small count bubble, e.g. items in the bag. Hidden when empty or zero. */
    badge?: number | string | null;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    variant: 'ghost',
    type: 'button',
    disabled: false,
    pressed: undefined,
    badge: null,
});

const variantClasses = {
    ghost: 'text-ink hover:bg-surface-sunken',
    outline:
        'border border-border-strong bg-surface-raised text-ink hover:border-ink',
    solid: 'bg-rose-900 text-cream-50 hover:bg-rose-800',
    overlay:
        'bg-surface-raised/90 text-ink shadow-sm backdrop-blur hover:bg-surface-raised',
} as const;

const showBadge = computed(
    () => props.badge !== null && props.badge !== '' && props.badge !== 0,
);
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        :type="href ? undefined : type"
        :disabled="href ? undefined : disabled"
        :aria-label="label"
        :aria-pressed="pressed"
        :aria-disabled="href && disabled ? true : undefined"
        :tabindex="href && disabled ? -1 : undefined"
        :title="label"
        :class="
            cn(
                'relative inline-flex shrink-0 items-center justify-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                size === 'sm' ? 'size-8' : 'size-12',
                variantClasses[variant],
                props.class,
            )
        "
    >
        <slot />
        <span
            v-if="showBadge"
            data-testid="icon-button-badge"
            class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-clay-600 px-1 text-xs leading-4 font-semibold text-white"
            aria-hidden="true"
        >
            {{ badge }}
        </span>
    </component>
</template>
