<script setup lang="ts">
import StatusBadge from '@/components/storefront/StatusBadge.vue';

withDefaults(
    defineProps<{
        status: string;
        showPrefix?: boolean;
        class?: string;
    }>(),
    {
        showPrefix: true,
    },
);
</script>

<template>
    <StatusBadge
        :status="status"
        type="payment"
        :prefix="showPrefix ? 'Payment:' : ''"
        :class="$props.class"
    />
</template>
