<script setup lang="ts">
import { computed, useId } from 'vue';
import CheckboxInput from '@/components/form/primitives/CheckboxInput.vue';
import InputError from '@/components/form/primitives/InputError.vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    id?: string;
    label?: string | null;
    error?: string | null;
    description?: string | null;
    wrapperClass?: string;
    class?: string;
}

const props = defineProps<Props>();
const model = defineModel<boolean>();
const autoId = useId();
const inputId = computed(() => props.id || `checkbox-${autoId}`);
const errorId = computed(() => `${inputId.value}-error`);
</script>

<template>
    <div :class="cn('w-full', wrapperClass)">
        <label
            :for="inputId"
            class="flex cursor-pointer items-start gap-3 select-none"
        >
            <div class="flex h-6 items-center">
                <CheckboxInput
                    :id="inputId"
                    v-bind="$attrs"
                    v-model="model"
                    :has-error="Boolean(error)"
                    :aria-invalid="Boolean(error)"
                    :aria-describedby="error ? errorId : undefined"
                    :class="props.class"
                />
            </div>
            <div>
                <span class="block text-sm leading-6 font-medium text-ink">
                    <slot>{{ label }}</slot>
                </span>
                <p v-if="description" class="text-xs text-ink-subtle">
                    {{ description }}
                </p>
            </div>
        </label>

        <InputError :id="errorId" :message="error" class="ml-8" />
    </div>
</template>
