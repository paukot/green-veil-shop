<script setup lang="ts">
import { computed, useId } from 'vue';
import InputError from '@/components/form/primitives/InputError.vue';
import InputLabel from '@/components/form/primitives/InputLabel.vue';
import SelectInput from '@/components/form/primitives/SelectInput.vue';
import type { SelectOption } from '@/components/form/primitives/SelectInput.vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    id?: string;
    label?: string | null;
    error?: string | null;
    required?: boolean;
    subtext?: string | null;
    hint?: string | null;
    options?: SelectOption[];
    placeholder?: string | null;
    variant?: 'default' | 'uppercase';
    size?: 'sm' | 'md';
    rounded?: 'md' | 'lg' | 'xl';
    wrapperClass?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'md',
    rounded: 'xl',
});

const model = defineModel<string | number | null>();
const autoId = useId();
const inputId = computed(() => props.id || `select-${autoId}`);
const errorId = computed(() => `${inputId.value}-error`);
</script>

<template>
    <div :class="cn('w-full', wrapperClass)">
        <div
            v-if="label || $slots['label-right']"
            class="flex items-center justify-between"
        >
            <InputLabel
                :for-id="inputId"
                :label="label"
                :required="required"
                :subtext="subtext"
                :variant="variant"
            >
                <slot name="label">{{ label }}</slot>
            </InputLabel>
            <slot name="label-right" />
        </div>

        <SelectInput
            :id="inputId"
            v-bind="$attrs"
            v-model="model"
            :options="options"
            :placeholder="placeholder"
            :has-error="Boolean(error)"
            :size="size"
            :rounded="rounded"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? errorId : undefined"
            :class="props.class"
        >
            <slot />
        </SelectInput>

        <p v-if="hint && !error" class="text-charcoal-400 mt-1 text-xs">
            {{ hint }}
        </p>

        <InputError :id="errorId" :message="error" />
    </div>
</template>
