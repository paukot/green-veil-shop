<script setup lang="ts">
import { computed, useId } from 'vue';
import InputError from '@/components/form/primitives/InputError.vue';
import InputLabel from '@/components/form/primitives/InputLabel.vue';
import TextareaInput from '@/components/form/primitives/TextareaInput.vue';
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
    rows?: number;
    variant?: 'default' | 'uppercase';
    rounded?: 'md' | 'lg' | 'xl';
    wrapperClass?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
    variant: 'default',
    rounded: 'xl',
});

const model = defineModel<string | null>();
const autoId = useId();
const inputId = computed(() => props.id || `textarea-${autoId}`);
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

        <TextareaInput
            :id="inputId"
            v-bind="$attrs"
            v-model="model"
            :rows="rows"
            :has-error="Boolean(error)"
            :rounded="rounded"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? errorId : undefined"
            :class="props.class"
        />

        <p v-if="hint && !error" class="text-charcoal-400 mt-1 text-xs">
            {{ hint }}
        </p>

        <InputError :id="errorId" :message="error" />
    </div>
</template>
