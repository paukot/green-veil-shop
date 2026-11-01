<script setup lang="ts">
import { computed } from 'vue';
import RadioInput from '@/components/form/primitives/RadioInput.vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    value: string | number;
    name?: string;
    title?: string | null;
    subtitle?: string | null;
    description?: string | null;
    align?: 'center' | 'start';
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    align: 'center',
    disabled: false,
});

const model = defineModel<string | number>();
const isSelected = computed(() => model.value === props.value);
</script>

<template>
    <label
        :class="
            cn(
                'flex cursor-pointer justify-between rounded-2xl border p-4 transition-all',
                align === 'center' ? 'items-center' : 'items-start',
                isSelected
                    ? 'border-sage-700 bg-sage-50/40 shadow-xs'
                    : 'border-cream-200 bg-white hover:border-cream-300',
                disabled && 'cursor-not-allowed opacity-50',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'flex gap-3',
                    align === 'center' ? 'items-center' : 'items-start',
                )
            "
        >
            <RadioInput
                v-bind="$attrs"
                v-model="model"
                :value="value"
                :name="name"
                :disabled="disabled"
                :class="align === 'start' ? 'mt-1' : ''"
            />
            <div>
                <div class="flex items-center gap-2">
                    <slot name="title">
                        <span class="text-sm font-medium text-charcoal-900">
                            {{ title }}
                        </span>
                    </slot>
                    <slot name="badge" />
                </div>

                <slot name="subtitle">
                    <span
                        v-if="subtitle"
                        class="text-charcoal-500 block text-xs"
                    >
                        {{ subtitle }}
                    </span>
                </slot>

                <slot name="description">
                    <span
                        v-if="description"
                        class="text-charcoal-400 mt-0.5 block text-xs"
                    >
                        {{ description }}
                    </span>
                </slot>
            </div>
        </div>

        <div v-if="$slots.trailing" class="text-right">
            <slot name="trailing" />
        </div>
    </label>
</template>
