<script setup lang="ts">
import { MinusIcon, PlusIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

interface Props {
    min?: number;
    max?: number;
    disabled?: boolean;
    loading?: boolean;
    /** sm is a 2rem compact stepper; md matches 3rem buttons and inputs. */
    size?: 'sm' | 'md';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    min: 1,
    max: 99,
    disabled: false,
    loading: false,
    size: 'md',
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
                'inline-flex shrink-0 items-center rounded-full border border-border-strong bg-surface-raised',
                size === 'sm' ? 'h-8' : 'h-12 px-1',
                disabled || loading ? 'pointer-events-none opacity-50' : '',
                props.class,
            )
        "
        role="group"
        aria-label="Quantity"
    >
        <button
            type="button"
            @click="decrement"
            :disabled="disabled || loading || model <= min"
            :class="
                cn(
                    'flex items-center justify-center rounded-full text-ink-muted transition-colors hover:bg-surface-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-30',
                    size === 'sm' ? 'size-8' : 'size-12',
                )
            "
            aria-label="Decrease quantity"
        >
            <MinusIcon class="size-4" aria-hidden="true" />
        </button>

        <span
            :class="
                cn(
                    'text-center font-semibold text-ink tabular-nums select-none',
                    size === 'sm' ? 'w-6 text-xs' : 'w-8 text-sm',
                )
            "
            aria-live="polite"
        >
            {{ model }}
        </span>

        <button
            type="button"
            @click="increment"
            :disabled="disabled || loading || model >= max"
            :class="
                cn(
                    'flex items-center justify-center rounded-full text-ink-muted transition-colors hover:bg-surface-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-30',
                    size === 'sm' ? 'size-8' : 'size-12',
                )
            "
            aria-label="Increase quantity"
        >
            <PlusIcon class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
