<script setup lang="ts">
withDefaults(
    defineProps<{
        text?: string;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        text: 'Loading...',
        size: 'md',
    },
);
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3">
        <!-- Rotating Thingy -->
        <div class="relative flex items-center justify-center">
            <!-- Outer rotating ring -->
            <div
                class="animate-spin rounded-full border-sage-200 border-t-sage-700"
                :class="{
                    'h-8 w-8 border-2': size === 'sm',
                    'h-12 w-12 border-3': size === 'md',
                    'h-16 w-16 border-4': size === 'lg',
                }"
            />
            <!-- Inner leaf icon -->
            <div class="absolute">
                <svg
                    class="animate-pulse text-sage-600"
                    :class="{
                        'h-3.5 w-3.5': size === 'sm',
                        'h-5 w-5': size === 'md',
                        'h-7 w-7': size === 'lg',
                    }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.75"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21V3m0 0a9.004 9.004 0 018.716 6.747M12 3a9.004 9.004 0 00-8.716 6.747"
                    />
                </svg>
            </div>
        </div>

        <!-- Even Funny Bouncing Dots -->
        <div class="flex items-center justify-center gap-2 pt-0.5">
            <span
                class="inline-block rounded-full bg-sage-700 animate-bounce"
                :class="{
                    'h-1.5 w-1.5': size === 'sm',
                    'h-2.5 w-2.5': size === 'md',
                    'h-3 w-3': size === 'lg',
                }"
                style="animation-delay: -0.32s;"
            />
            <span
                class="inline-block rounded-full bg-sage-700 animate-bounce"
                :class="{
                    'h-1.5 w-1.5': size === 'sm',
                    'h-2.5 w-2.5': size === 'md',
                    'h-3 w-3': size === 'lg',
                }"
                style="animation-delay: -0.16s;"
            />
            <span
                class="inline-block rounded-full bg-sage-700 animate-bounce"
                :class="{
                    'h-1.5 w-1.5': size === 'sm',
                    'h-2.5 w-2.5': size === 'md',
                    'h-3 w-3': size === 'lg',
                }"
                style="animation-delay: 0s;"
            />
        </div>

        <!-- Label -->
        <p
            v-if="text"
            class="font-serif tracking-wider text-sage-900"
            :class="{
                'text-xs': size === 'sm',
                'text-sm': size === 'md',
                'text-base': size === 'lg',
            }"
        >
            {{ text }}
        </p>
    </div>
</template>
