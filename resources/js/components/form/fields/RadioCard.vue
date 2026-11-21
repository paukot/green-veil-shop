<script setup lang="ts">
import { computed } from 'vue';
import RadioInput from '@/components/form/primitives/RadioInput.vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    value: string | number;
    name?: string;
    title?: string | null;
    subtitle?: string | null;
    description?: string | null;
    align?: 'center' | 'start';
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    align: 'center',
    disabled: false,
});

const model = defineModel<string | number>();
const isSelected = computed(() => model.value === props.value);
</script>

<template>
    <label
        :class="
            cn(
                'flex cursor-pointer justify-between gap-4 rounded-lg border p-4 transition-colors',
                align === 'center' ? 'items-center' : 'items-start',
                isSelected
                    ? 'border-sage-700 bg-sage-50 ring-1 ring-sage-700'
                    : 'border-border-strong bg-surface-raised hover:border-cream-400',
                disabled && 'cursor-not-allowed opacity-50',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'flex gap-3',
                    align === 'center' ? 'items-center' : 'items-start',
                )
            "
        >
            <RadioInput
                v-bind="$attrs"
                v-model="model"
                :value="value"
                :name="name"
                :disabled="disabled"
                :class="align === 'start' ? 'mt-1' : ''"
            />
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <slot name="title">
                        <span class="text-sm font-medium text-ink">
                            {{ title }}
                        </span>
                    </slot>
                    <slot name="badge" />
                </div>

                <slot name="subtitle">
                    <span
                        v-if="subtitle"
                        class="mt-1 block text-xs text-ink-muted"
                    >
                        {{ subtitle }}
                    </span>
                </slot>

                <slot name="description">
                    <span
                        v-if="description"
                        class="mt-1 block text-xs text-ink-subtle"
                    >
                        {{ description }}
                    </span>
                </slot>
            </div>
        </div>

        <div v-if="$slots.trailing" class="shrink-0 text-right">
            <slot name="trailing" />
        </div>
    </label>
</template>
