<script setup lang="ts">
import { useId } from 'vue';
import InputError from '@/components/form/primitives/InputError.vue';
import { cn } from '@/lib/utils';

interface Props {
    label?: string | null;
    error?: string | null;
    wrapperClass?: string;
    class?: string;
}

const props = defineProps<Props>();
const labelId = useId();
</script>

<template>
    <div :class="cn('w-full', wrapperClass)">
        <h3
            v-if="label"
            :id="labelId"
            class="mb-3 text-sm font-medium text-ink"
        >
            {{ label }}
        </h3>

        <div
            role="radiogroup"
            :aria-labelledby="label ? labelId : undefined"
            :aria-invalid="Boolean(error) || undefined"
            :class="cn('space-y-3', props.class)"
        >
            <slot />
        </div>

        <InputError :message="error" />
    </div>
</template>
