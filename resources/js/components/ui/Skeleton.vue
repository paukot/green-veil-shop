<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        as?: string;
        /** Small radii all map to the shared control radius. */
        rounded?: 'none' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';
    }>(),
    {
        as: 'div',
        rounded: 'lg',
    },
);

const radiusClass = {
    none: 'rounded-none',
    sm: 'rounded-lg',
    md: 'rounded-lg',
    lg: 'rounded-lg',
    xl: 'rounded-lg',
    '2xl': 'rounded-2xl',
    full: 'rounded-full',
}[props.rounded];
</script>

<template>
    <component
        :is="as"
        :class="[
            'animate-pulse bg-cream-200 motion-reduce:animate-none',
            radiusClass,
        ]"
        aria-hidden="true"
    />
</template>
