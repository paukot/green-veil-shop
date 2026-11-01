<script setup lang="ts">
import { computed, useId } from 'vue';
import InputError from '@/components/form/primitives/InputError.vue';
import InputLabel from '@/components/form/primitives/InputLabel.vue';
import TextInput from '@/components/form/primitives/TextInput.vue';
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
    type?: string;
    variant?: 'default' | 'uppercase';
    size?: 'sm' | 'md' | 'lg';
    rounded?: 'md' | 'lg' | 'xl' | 'full';
    prefixText?: string | null;
    suffixText?: string | null;
    wrapperClass?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    variant: 'default',
    size: 'md',
    rounded: 'xl',
});

const model = defineModel<string | number | null>();
const autoId = useId();
const inputId = computed(() => props.id || `field-${autoId}`);
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

        <TextInput
            :id="inputId"
            v-bind="$attrs"
            v-model="model"
            :type="type"
            :has-error="Boolean(error)"
            :size="size"
            :rounded="rounded"
            :prefix-text="prefixText"
            :suffix-text="suffixText"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? errorId : undefined"
            :class="props.class"
        >
            <template v-if="$slots.prefix" #prefix>
                <slot name="prefix" />
            </template>
            <template v-if="$slots.suffix" #suffix>
                <slot name="suffix" />
            </template>
        </TextInput>

        <p v-if="hint && !error" class="text-charcoal-400 mt-1 text-xs">
            {{ hint }}
        </p>

        <InputError :id="errorId" :message="error" />
    </div>
</template>
