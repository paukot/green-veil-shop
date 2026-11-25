<script setup lang="ts">
import { cn } from '@/lib/utils';

/**
 * A titled group of fields. Heading and fields are separated by one divider rhythm
 * (1rem under the text, 1.5rem before the fields) and fields sit on an even 1rem grid.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        /** Optional step number shown before the title, e.g. in checkout. */
        step?: number;
        columns?: 1 | 2;
        class?: string;
    }>(),
    {
        columns: 1,
    },
);
</script>

<template>
    <section :class="cn('flex flex-col gap-6', props.class)">
        <header
            class="flex items-start justify-between gap-4 border-b border-border pb-4"
        >
            <div class="flex items-start gap-3">
                <span
                    v-if="step"
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-rose-900 text-xs font-semibold text-cream-50"
                    aria-hidden="true"
                >
                    {{ step }}
                </span>
                <div class="flex flex-col gap-1">
                    <h2 class="font-serif text-2xl leading-8 text-ink">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="text-sm text-ink-muted">
                        {{ description }}
                    </p>
                </div>
            </div>
            <slot name="actions" />
        </header>

        <div :class="cn('grid gap-4', columns === 2 && 'sm:grid-cols-2')">
            <slot />
        </div>
    </section>
</template>
