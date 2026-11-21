<script setup lang="ts">
import { fieldStateClasses, fieldSurfaceClasses } from '@/lib/fieldStyles';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    hasError?: boolean;
    /** Kept for API compatibility; all textareas use the shared field radius. */
    rounded?: 'md' | 'lg' | 'xl';
    rows?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rounded: 'lg',
    rows: 3,
});

const model = defineModel<string | null>();
</script>

<template>
    <textarea
        v-bind="$attrs"
        v-model="model"
        :rows="rows"
        :class="
            cn(
                fieldSurfaceClasses,
                'px-4 py-3 text-base sm:text-sm',
                fieldStateClasses(hasError),
                props.class,
            )
        "
    ></textarea>
</template>
