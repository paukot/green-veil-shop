<script setup lang="ts">
import { useSlots } from 'vue';
import {
    fieldSizeClasses,
    fieldStateClasses,
    fieldSurfaceClasses,
} from '@/lib/fieldStyles';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    type?: string;
    hasError?: boolean;
    size?: 'sm' | 'md' | 'lg';
    /** `full` gives a pill (search); every other value uses the shared field radius. */
    rounded?: 'md' | 'lg' | 'xl' | 'full';
    prefixText?: string | null;
    suffixText?: string | null;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    size: 'md',
    rounded: 'lg',
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
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-ink-subtle"
        >
            <slot name="prefix">{{ prefixText }}</slot>
        </div>

        <input
            v-bind="$attrs"
            v-model="model"
            :type="type"
            :class="
                cn(
                    fieldSurfaceClasses,
                    fieldSizeClasses[size],
                    rounded === 'full' && 'rounded-full',
                    hasPrefix() && 'pl-12',
                    hasSuffix() && 'pr-12',
                    fieldStateClasses(hasError),
                    props.class,
                )
            "
        />

        <div
            v-if="hasSuffix()"
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-ink-subtle"
        >
            <slot name="suffix">{{ suffixText }}</slot>
        </div>
    </div>
</template>
