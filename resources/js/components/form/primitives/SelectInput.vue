<script setup lang="ts">
import { cn } from '@/lib/utils';

export interface SelectOption {
    value: string | number;
    label: string;
    disabled?: boolean;
}

defineOptions({
    inheritAttrs: false,
});

interface Props {
    options?: SelectOption[];
    placeholder?: string | null;
    hasError?: boolean;
    size?: 'sm' | 'md';
    rounded?: 'md' | 'lg' | 'xl';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    rounded: 'xl',
});

const model = defineModel<string | number | null>();
</script>

<template>
    <div :class="cn('relative w-full', $attrs.class as string)">
        <select
            v-bind="$attrs"
            v-model="model"
            :class="
                cn(
                    'block w-full appearance-none border bg-cream-50/50 text-charcoal-900 transition-colors focus:bg-white focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                    rounded === 'md' && 'rounded-md',
                    rounded === 'lg' && 'rounded-lg',
                    rounded === 'xl' && 'rounded-xl',
                    size === 'sm' && 'py-2 pr-8 pl-3 text-base sm:py-1.5 sm:text-xs min-h-[44px] sm:min-h-0',
                    size === 'md' && 'py-2.5 pr-10 pl-4 text-base sm:text-sm min-h-[44px] sm:min-h-0',
                    hasError
                        ? 'border-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                        : 'border-cream-300 focus:border-sage-600 focus:ring-1 focus:ring-sage-600',
                    props.class,
                )
            "
        >
            <option v-if="placeholder" value="" disabled selected>
                {{ placeholder }}
            </option>
            <template v-if="options && options.length > 0">
                <option
                    v-for="opt in options"
                    :key="opt.value"
                    :value="opt.value"
                    :disabled="opt.disabled"
                >
                    {{ opt.label }}
                </option>
            </template>
            <slot v-else />
        </select>

        <div
            class="text-charcoal-500 pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5"
            aria-hidden="true"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </div>
    </div>
</template>
