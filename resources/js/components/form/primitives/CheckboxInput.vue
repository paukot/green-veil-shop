<script setup lang="ts">
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    hasError?: boolean;
    class?: string;
}

const props = defineProps<Props>();
const model = defineModel<boolean>();
</script>

<template>
    <input
        v-bind="$attrs"
        v-model="model"
        type="checkbox"
        :class="
            cn(
                'size-4 shrink-0 cursor-pointer accent-rose-800 disabled:cursor-not-allowed disabled:opacity-50',
                hasError && 'outline-2 outline-offset-2 outline-danger-500',
                props.class,
            )
        "
    />
</template>
