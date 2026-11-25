<script setup lang="ts">
import Container from '@/components/ui/Container.vue';
import { cn } from '@/lib/utils';

/**
 * A page band with the shared vertical rhythm. Sections stack edge to edge,
 * so spacing between them always comes from this component and is never doubled.
 */
const props = withDefaults(
    defineProps<{
        tone?: 'default' | 'sunken' | 'dark' | 'accent';
        spacing?: 'md' | 'sm' | 'none';
        /** Wrap the content in the page Container. */
        contained?: boolean;
        containerSize?: 'default' | 'narrow' | 'compact';
        as?: string;
        class?: string;
    }>(),
    {
        tone: 'default',
        spacing: 'md',
        contained: true,
        containerSize: 'default',
        as: 'section',
    },
);

const toneClasses = {
    default: '',
    sunken: 'bg-surface-sunken',
    dark: 'bg-rose-950 text-cream-50',
    accent: 'bg-clay-50',
} as const;

const spacingClasses = {
    md: 'py-16 lg:py-24',
    sm: 'py-12 lg:py-16',
    none: '',
} as const;
</script>

<template>
    <component
        :is="as"
        :class="cn(toneClasses[tone], spacingClasses[spacing], props.class)"
    >
        <Container v-if="contained" :size="containerSize">
            <slot />
        </Container>
        <slot v-else />
    </component>
</template>
