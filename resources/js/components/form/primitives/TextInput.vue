<script setup lang="ts">
import { useSlots } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    type?: string;
    hasError?: boolean;
    size?: 'sm' | 'md' | 'lg';
    rounded?: 'md' | 'lg' | 'xl' | 'full';
    prefixText?: string | null;
    suffixText?: string | null;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    size: 'md',
    rounded: 'xl',
});

const model = defineModel<string | number | null>();
const slots = useSlots();

const hasPrefix = () => Boolean(props.prefixText || slots.prefix);
const hasSuffix = () => Boolean(props.suffixText || slots.suffix);
</script>

<template>
    <div :class="cn('relative w-full', $attrs.class as string)">
        <div
            v-if="hasPrefix()"
            class="text-charcoal-400 pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs"
        >
            <slot name="prefix">{{ prefixText }}</slot>
        </div>

        <input
            v-bind="$attrs"
            v-model="model"
            :type="type"
            :class="
                cn(
                    'placeholder-charcoal-400 block w-full border bg-cream-50/50 text-charcoal-900 transition-colors focus:bg-white focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                    // Rounded
                    rounded === 'md' && 'rounded-md',
                    rounded === 'lg' && 'rounded-lg',
                    rounded === 'xl' && 'rounded-xl',
                    rounded === 'full' && 'rounded-full',
                    // Sizing
                    size === 'sm' &&
                        'min-h-[44px] px-3 py-2 text-base sm:min-h-0 sm:py-1.5 sm:text-xs',
                    size === 'md' &&
                        'min-h-[44px] px-4 py-2.5 text-base sm:min-h-0 sm:text-sm',
                    size === 'lg' && 'min-h-[44px] px-4.5 py-3 text-base',
                    // Padding offsets for prefix/suffix
                    hasPrefix() && size === 'sm' && 'pl-6',
                    hasPrefix() && size === 'md' && 'pl-8',
                    hasPrefix() && size === 'lg' && 'pl-9',
                    hasSuffix() && size === 'sm' && 'pr-6',
                    hasSuffix() && size === 'md' && 'pr-8',
                    hasSuffix() && size === 'lg' && 'pr-9',
                    // Error vs Normal state
                    hasError
                        ? 'border-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                        : 'border-cream-300 focus:border-sage-600 focus:ring-1 focus:ring-sage-600',
                    props.class,
                )
            "
        />

        <div
            v-if="hasSuffix()"
            class="text-charcoal-400 pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs"
        >
            <slot name="suffix">{{ suffixText }}</slot>
        </div>
    </div>
</template>
