<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import { cn } from '@/lib/utils';

interface Props {
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    loading?: boolean;
    disabled?: boolean;
    href?: string;
    as?: 'button' | 'a' | typeof Link;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'bg-cream-100 text-charcoal-800 hover:bg-cream-200 border border-cream-200 shadow-xs';
        case 'outline':
            return 'border border-cream-300 bg-white text-charcoal-800 hover:bg-cream-100 hover:text-sage-900 shadow-xs';
        case 'danger':
            return 'bg-red-700 text-white hover:bg-red-800 shadow-xs';
        case 'ghost':
            return 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800';
        case 'primary':
        default:
            return 'bg-sage-800 text-cream-50 hover:bg-sage-900 shadow-xs';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-3.5 py-2 text-xs font-medium tracking-wider uppercase rounded-lg gap-1.5';
        case 'lg':
            return 'px-6 py-3.5 text-xs font-semibold tracking-wider uppercase rounded-full gap-2.5';
        case 'md':
        default:
            return 'px-5 py-2.5 text-xs font-medium tracking-wider uppercase rounded-xl gap-2';
    }
});
</script>

<template>
    <Link
        v-if="href && as !== 'a'"
        :href="href"
        :class="
            cn(
                'inline-flex items-center justify-center transition-all select-none',
                variantClasses,
                sizeClasses,
                disabled || loading
                    ? 'pointer-events-none opacity-50 cursor-not-allowed'
                    : 'active:scale-[0.99]',
                props.class,
            )
        "
    >
        <LoadingSpinner
            v-if="loading"
            :size="size === 'lg' ? 'md' : 'sm'"
            class="-ml-1"
        />
        <slot />
    </Link>

    <a
        v-else-if="href && as === 'a'"
        :href="href"
        :class="
            cn(
                'inline-flex items-center justify-center transition-all select-none',
                variantClasses,
                sizeClasses,
                disabled || loading
                    ? 'pointer-events-none opacity-50 cursor-not-allowed'
                    : 'active:scale-[0.99]',
                props.class,
            )
        "
    >
        <LoadingSpinner
            v-if="loading"
            :size="size === 'lg' ? 'md' : 'sm'"
            class="-ml-1"
        />
        <slot />
    </a>

    <button
        v-else
        :type="type"
        :disabled="disabled || loading"
        :class="
            cn(
                'inline-flex items-center justify-center transition-all select-none',
                variantClasses,
                sizeClasses,
                disabled || loading
                    ? 'opacity-50 cursor-not-allowed'
                    : 'active:scale-[0.99]',
                props.class,
            )
        "
    >
        <LoadingSpinner
            v-if="loading"
            :size="size === 'lg' ? 'md' : 'sm'"
            class="-ml-1"
        />
        <slot />
    </button>
</template>
