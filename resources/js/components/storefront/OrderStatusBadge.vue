<script setup lang="ts">
import StatusBadge from '@/components/storefront/StatusBadge.vue';

defineProps<{
    status: string;
    class?: string;
}>();
</script>

<template>
    <StatusBadge :status="status" type="order" :class="$props.class" />
</template>
