<script setup lang="ts">
defineProps<{
    skinTypes?: Array<{ id: number; name: string }>;
    effects?: Array<{ id: number; name: string }>;
    ingredients?: Array<{ id: number; name: string }>;
    claims?: Array<{ id: number; name: string }>;
}>();
</script>

<template>
    <div
        v-if="
            (skinTypes && skinTypes.length > 0) ||
            (effects && effects.length > 0) ||
            (ingredients && ingredients.length > 0) ||
            (claims && claims.length > 0)
        "
        class="space-y-2 border-t border-cream-200 pt-4"
    >
        <!-- Skin Types -->
        <div
            v-if="skinTypes && skinTypes.length > 0"
            class="flex items-start gap-2 text-xs"
        >
            <span class="shrink-0 font-semibold text-charcoal-700"
                >Skin Type:</span
            >
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="st in skinTypes"
                    :key="st.id"
                    class="rounded bg-cream-100 px-2 py-0.5 text-charcoal-700"
                >
                    {{ st.name }}
                </span>
            </div>
        </div>

        <!-- Effects & Actions -->
        <div
            v-if="effects && effects.length > 0"
            class="flex items-start gap-2 text-xs"
        >
            <span class="shrink-0 font-semibold text-charcoal-700"
                >Action:</span
            >
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="ef in effects"
                    :key="ef.id"
                    class="rounded bg-sage-100 px-2 py-0.5 font-medium text-sage-800"
                >
                    {{ ef.name }}
                </span>
            </div>
        </div>

        <!-- Active Ingredients -->
        <div
            v-if="ingredients && ingredients.length > 0"
            class="flex items-start gap-2 text-xs"
        >
            <span class="shrink-0 font-semibold text-charcoal-700"
                >Actives:</span
            >
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="ing in ingredients"
                    :key="ing.id"
                    class="rounded bg-cream-200/80 px-2 py-0.5 font-medium text-charcoal-800"
                >
                    {{ ing.name }}
                </span>
            </div>
        </div>

        <!-- Clean Beauty Claims -->
        <div
            v-if="claims && claims.length > 0"
            class="flex items-start gap-2 text-xs"
        >
            <span class="shrink-0 font-semibold text-charcoal-700"
                >Claims:</span
            >
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="cl in claims"
                    :key="cl.id"
                    class="rounded-full border border-sage-300 bg-white px-2 py-0.5 text-[11px] font-medium text-sage-800"
                >
                    {{ cl.name }}
                </span>
            </div>
        </div>
    </div>
</template>
