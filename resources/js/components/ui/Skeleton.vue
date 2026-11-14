<script setup lang="ts">
withDefaults(
    defineProps<{
        as?: string;
        rounded?: 'none' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';
    }>(),
    {
        as: 'div',
        rounded: 'xl',
    },
);
</script>

<template>
    <component
        :is="as"
        class="animate-pulse bg-cream-200/80 motion-reduce:animate-none motion-reduce:bg-cream-200"
        :class="{
            'rounded-none': rounded === 'none',
            'rounded-sm': rounded === 'sm',
            'rounded-md': rounded === 'md',
            'rounded-lg': rounded === 'lg',
            'rounded-xl': rounded === 'xl',
            'rounded-2xl': rounded === '2xl',
            'rounded-full': rounded === 'full',
        }"
        aria-hidden="true"
    />
</template>
