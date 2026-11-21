<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import {
    fieldSizeClasses,
    fieldStateClasses,
    fieldSurfaceClasses,
} from '@/lib/fieldStyles';
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
    /** Kept for API compatibility; all selects use the shared field radius. */
    rounded?: 'md' | 'lg' | 'xl';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    rounded: 'lg',
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
                    fieldSurfaceClasses,
                    fieldSizeClasses[size],
                    'appearance-none',
                    size === 'sm' ? 'pr-8' : 'pr-12',
                    fieldStateClasses(hasError),
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
            :class="
                cn(
                    'pointer-events-none absolute inset-y-0 right-0 flex items-center text-ink-subtle',
                    size === 'sm' ? 'pr-2' : 'pr-4',
                )
            "
            aria-hidden="true"
        >
            <ChevronDownIcon class="size-4" aria-hidden="true" />
        </div>
    </div>
</template>
