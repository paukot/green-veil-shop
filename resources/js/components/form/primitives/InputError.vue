<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    message?: string | null;
    id?: string;
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <p
        v-if="message"
        :id="id"
        role="alert"
        :class="cn('mt-1.5 text-xs text-rose-600', props.class)"
    >
        {{ message }}
    </p>
</template>
