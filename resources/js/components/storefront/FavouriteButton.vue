<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/storefront';
import { useFavourites } from '@/composables/useFavourites';
import { HeartIcon } from '@lucide/vue';

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
const favourites = useFavourites();

const isFavourited = computed(() => {
    if (favourites.productIds.value.length > 0) {
        return favourites.isFavourited(props.productId);
    }
    return (
        favourites.isFavourited(props.productId) ||
        (page.props.favourites?.includes(props.productId) ?? false)
    );
});

function toggle() {
    favourites.toggle(props.productId);
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
            'flex min-h-[44px] min-w-[44px] items-center justify-center',
            isFavourited
                ? 'text-rose-500 hover:text-rose-600'
                : 'text-charcoal-600 hover:text-rose-500',
        ]"
        :aria-label="
            isFavourited
                ? `Remove ${productName || 'product'} from favourites`
                : `Save ${productName || 'product'} to favourites`
        "
    >
        <!-- Solid Heart when favourited -->
        <HeartIcon
            v-if="isFavourited"
            :class="[
                iconSizeClasses,
                'fill-current text-rose-500 transition-transform active:scale-125',
            ]"
            aria-hidden="true"
        />

        <!-- Outline Heart when not favourited -->
        <HeartIcon
            v-else
            :class="[
                iconSizeClasses,
                'fill-none stroke-current text-current transition-transform active:scale-125',
            ]"
            aria-hidden="true"
            :stroke-width="1.75"
        />
        <span v-if="variant === 'pill'" class="select-none">
            {{ isFavourited ? 'Saved to Wishlist' : 'Add to Wishlist' }}
        </span>
    </button>
</template>
