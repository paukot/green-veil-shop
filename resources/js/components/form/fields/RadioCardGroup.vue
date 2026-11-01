<script setup lang="ts">
import InputError from '@/components/form/primitives/InputError.vue';
import { cn } from '@/lib/utils';

interface Props {
    label?: string | null;
    error?: string | null;
    wrapperClass?: string;
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('w-full', wrapperClass)">
        <h3
            v-if="label"
            class="mb-3 text-xs font-semibold tracking-wider text-charcoal-700 uppercase"
        >
            {{ label }}
        </h3>

        <div :class="cn('space-y-3', props.class)">
            <slot />
        </div>

        <InputError :message="error" />
    </div>
</template>
