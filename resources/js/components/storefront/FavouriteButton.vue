<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { toggle as toggleFavourite } from '@/actions/App/Http/Controllers/FavouriteController';

const props = withDefaults(
    defineProps<{
        productId: number;
        productName?: string;
        size?: 'sm' | 'md' | 'lg';
        variant?: 'circle' | 'pill' | 'icon';
    }>(),
    {
        size: 'md',
        variant: 'circle',
    },
);

const page = usePage<SharedProps>();
const isToggling = ref(false);

const isFavourited = computed(() => {
    return page.props.favourites?.includes(props.productId) ?? false;
});

function toggle() {
    if (isToggling.value) return;

    isToggling.value = true;
    router.post(
        toggleFavourite.url(props.productId),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isToggling.value = false;
            },
        },
    );
}

const iconSizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'w-3.5 h-3.5';
        case 'lg':
            return 'w-5 h-5';
        default:
            return 'w-4 h-4';
    }
});

const wrapperClasses = computed(() => {
    if (props.variant === 'pill') {
        return 'px-4 py-2 rounded-full border border-cream-300 bg-white hover:border-sage-500 hover:bg-cream-100 flex items-center gap-2 text-xs font-medium text-charcoal-700 transition-colors shadow-xs';
    }

    if (props.variant === 'icon') {
        return 'p-1 text-charcoal-500 hover:text-rose-600 transition-colors';
    }

    // Default circle
    const sizeMap = {
        sm: 'p-1.5',
        md: 'p-2',
        lg: 'p-3',
    };

    return `${sizeMap[props.size]} rounded-full bg-cream-50/90 hover:bg-white text-charcoal-700 shadow-xs hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-sage-500`;
});
</script>

<template>
    <button
        type="button"
        @click.stop="toggle"
        :class="[
            wrapperClasses,
            isFavourited
                ? 'text-rose-500 hover:text-rose-600'
                : 'text-charcoal-600 hover:text-rose-500',
        ]"
        :aria-label="
            isFavourited
                ? `Remove ${productName || 'product'} from favourites`
                : `Save ${productName || 'product'} to favourites`
        "
        :disabled="isToggling"
    >
        <svg
            :class="[
                iconSizeClasses,
                'transition-transform active:scale-125',
                isFavourited
                    ? 'fill-rose-500 text-rose-500'
                    : 'text-currentColor fill-none',
            ]"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.75"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            />
        </svg>
        <span v-if="variant === 'pill'" class="select-none">
            {{ isFavourited ? 'Saved to Wishlist' : 'Add to Wishlist' }}
        </span>
    </button>
</template>
