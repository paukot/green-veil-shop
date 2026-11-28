<script setup lang="ts">
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import type { BadgeTone } from '@/components/ui/Badge.vue';

const props = defineProps<{
    skinTypes?: Array<{ id: number; name: string }>;
    effects?: Array<{ id: number; name: string }>;
    ingredients?: Array<{ id: number; name: string }>;
    claims?: Array<{ id: number; name: string }>;
}>();

const groups = computed(() =>
    (
        [
            { label: 'Skin type', items: props.skinTypes, tone: 'neutral' },
            { label: 'Benefits', items: props.effects, tone: 'brand' },
            { label: 'Actives', items: props.ingredients, tone: 'accent' },
            { label: 'Claims', items: props.claims, tone: 'neutral' },
        ] as Array<{
            label: string;
            items?: Array<{ id: number; name: string }>;
            tone: BadgeTone;
        }>
    ).filter((group) => group.items && group.items.length > 0),
);
</script>

<template>
    <dl
        v-if="groups.length > 0"
        class="flex flex-col gap-3"
        aria-label="Product benefits and attributes"
    >
        <div
            v-for="group in groups"
            :key="group.label"
            class="flex items-start gap-4"
        >
            <dt
                class="flex h-6 w-24 shrink-0 items-center text-xs font-semibold tracking-wider text-ink-subtle uppercase"
            >
                {{ group.label }}
            </dt>
            <dd class="flex flex-wrap gap-2">
                <Badge
                    v-for="item in group.items"
                    :key="item.id"
                    :tone="group.tone"
                >
                    {{ item.name }}
                </Badge>
            </dd>
        </div>
    </dl>
</template>
