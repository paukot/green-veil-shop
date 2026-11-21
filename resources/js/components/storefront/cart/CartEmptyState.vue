<script setup lang="ts">
import { ShoppingBagIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { index as productsIndex } from '@/routes/products';
</script>

<template>
    <EmptyState
        title="Your bag is empty"
        description="Your bag is waiting for botanical cleansers, soothing mists and restorative skincare."
    >
        <template #icon>
            <ShoppingBagIcon aria-hidden="true" />
        </template>
        <template #actions>
            <Button :href="productsIndex.url()" size="lg"
                >Start shopping</Button
            >
        </template>
    </EmptyState>
</template>
