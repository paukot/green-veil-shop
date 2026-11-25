<script setup lang="ts">
import { XIcon } from '@lucide/vue';
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
    placeholder?: string;
    size?: 'sm' | 'md';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Search products...',
    size: 'md',
});

const model = defineModel<string>({ default: '' });
const emit = defineEmits<{
    (e: 'clear'): void;
}>();

function onClear() {
    model.value = '';
    emit('clear');
}
</script>

<template>
    <div :class="cn('relative w-full', $attrs.class as string)">
        <input
            v-bind="$attrs"
            v-model="model"
            type="search"
            :placeholder="placeholder"
            :class="
                cn(
                    fieldSurfaceClasses,
                    fieldSizeClasses[size],
                    fieldStateClasses(false),
                    'rounded-full pr-12 [&::-webkit-search-cancel-button]:hidden',
                    props.class,
                )
            "
        />

        <button
            v-if="model"
            type="button"
            @click="onClear"
            :class="
                cn(
                    'absolute top-1/2 right-2 flex -translate-y-1/2 items-center justify-center rounded-full text-ink-subtle transition-colors hover:bg-surface-sunken hover:text-ink',
                    size === 'sm' ? 'size-6' : 'size-8',
                )
            "
            aria-label="Clear search"
        >
            <XIcon class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
