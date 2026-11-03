<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    min?: number;
    max?: number;
    disabled?: boolean;
    loading?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    min: 1,
    max: 99,
    disabled: false,
    loading: false,
});

const model = defineModel<number>({ default: 1 });
const emit = defineEmits<{
    (e: 'change', value: number): void;
}>();

function decrement() {
    if (props.disabled || props.loading || model.value <= props.min) return;
    model.value--;
    emit('change', model.value);
}

function increment() {
    if (props.disabled || props.loading || model.value >= props.max) return;
    model.value++;
    emit('change', model.value);
}
</script>

<template>
    <div
        :class="
            cn(
                'inline-flex items-center rounded-full border border-cream-300 bg-white px-2 py-0.5 shadow-2xs',
                disabled || loading ? 'pointer-events-none opacity-50' : '',
                props.class,
            )
        "
    >
        <button
            type="button"
            @click="decrement"
            :disabled="disabled || loading || model <= min"
            class="text-charcoal-600 flex h-7 w-7 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:cursor-not-allowed disabled:opacity-30"
            aria-label="Decrease quantity"
        >
            &minus;
        </button>

        <span
            class="w-8 text-center text-xs font-semibold text-charcoal-900 select-none"
        >
            {{ model }}
        </span>

        <button
            type="button"
            @click="increment"
            :disabled="disabled || loading || model >= max"
            class="text-charcoal-600 flex h-7 w-7 items-center justify-center rounded-full transition-colors hover:text-charcoal-900 disabled:cursor-not-allowed disabled:opacity-30"
            aria-label="Increase quantity"
        >
            &#43;
        </button>
    </div>
</template>
