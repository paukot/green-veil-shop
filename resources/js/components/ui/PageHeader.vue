<script setup lang="ts">
import { cn } from '@/lib/utils';

/**
 * One heading treatment for the whole storefront: an optional eyebrow, a serif title,
 * a description and an actions slot that sits beside the text on wide screens.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        eyebrow?: string;
        description?: string;
        /** page renders an h1, section an h2. */
        level?: 'page' | 'section';
        align?: 'start' | 'center';
        /** Light text for dark bands. */
        inverse?: boolean;
        class?: string;
    }>(),
    {
        level: 'page',
        align: 'start',
        inverse: false,
    },
);
</script>

<template>
    <header
        :class="
            cn(
                'flex flex-col gap-6',
                align === 'center'
                    ? 'items-center text-center'
                    : 'sm:flex-row sm:items-end sm:justify-between',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'flex max-w-3xl flex-col gap-3',
                    align === 'center' && 'items-center',
                )
            "
        >
            <p
                v-if="eyebrow"
                :class="
                    cn(
                        'text-xs font-semibold tracking-widest uppercase',
                        inverse ? 'text-rose-300' : 'text-clay-600',
                    )
                "
            >
                {{ eyebrow }}
            </p>
            <component
                :is="level === 'page' ? 'h1' : 'h2'"
                :class="
                    cn(
                        'font-serif tracking-tight text-balance',
                        level === 'page'
                            ? 'text-4xl lg:text-6xl'
                            : 'text-3xl lg:text-4xl',
                        inverse ? 'text-cream-50' : 'text-ink',
                    )
                "
            >
                {{ title }}
            </component>
            <p
                v-if="description || $slots.description"
                :class="
                    cn(
                        'text-base text-pretty',
                        inverse ? 'text-cream-200' : 'text-ink-muted',
                    )
                "
            >
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-3">
            <slot name="actions" />
        </div>
    </header>
</template>
