<script setup lang="ts">
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
    placeholder: 'Search botanicals...',
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
                    'block w-full rounded-full border border-cream-300 bg-white text-charcoal-900 transition-colors focus:border-sage-500 focus:outline-hidden',
                    size === 'sm'
                        ? 'py-2 pr-9 pl-3.5 text-xs focus:ring-1 focus:ring-sage-500'
                        : 'py-2 pr-10 pl-4 text-sm focus:ring-2 focus:ring-sage-500',
                    props.class,
                )
            "
        />

        <button
            v-if="model"
            type="button"
            @click="onClear"
            class="text-charcoal-400 absolute top-1/2 right-3 -translate-y-1/2 hover:text-charcoal-700"
            aria-label="Clear search"
        >
            &times;
        </button>
    </div>
</template>
