<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    title: string;
    description?: string;
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center gap-6 rounded-2xl border border-dashed border-border-strong px-6 py-16 text-center',
                props.class,
            )
        "
    >
        <div
            v-if="$slots.icon"
            class="flex size-16 items-center justify-center rounded-full bg-surface-sunken text-rose-700 [&_svg]:size-6"
        >
            <slot name="icon" />
        </div>
        <div class="flex max-w-md flex-col gap-2">
            <h2 class="font-serif text-2xl text-ink">
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="text-sm text-ink-muted"
            >
                {{ description }}
            </p>
        </div>
        <div
            v-if="$slots.actions"
            class="flex flex-wrap justify-center gap-3"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
