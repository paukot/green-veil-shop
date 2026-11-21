<script setup lang="ts">
import Container from '@/components/ui/Container.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Section from '@/components/ui/Section.vue';
import { computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import WishlistContent from '@/components/storefront/WishlistContent.vue';
import type { CustomerUser, ProductCardData } from '@/types/storefront';
import { useFavourites } from '@/composables/useFavourites';
import { usePageLoading } from '@/composables/usePageLoading';

defineOptions({
    layout: (pageProps: { auth?: { user?: CustomerUser | null } }) =>
        pageProps.auth?.user ? AccountLayout : StorefrontLayout,
});

const { isLoading } = usePageLoading();
const page = usePage<{ auth: { user: CustomerUser | null } }>();
const user = computed(() => page.props.auth?.user);

const props = defineProps<{
    products: ProductCardData[];
}>();

const favourites = useFavourites();

onMounted(() => {
    if (props.products.length > 0) {
        favourites.initFromServer(props.products.map((p) => p.id));
    }
});

const displayedProducts = computed(() => {
    return props.products.filter((p) => favourites.isFavourited(p.id));
});
</script>

<template>
    <template v-if="user">
        <Head title="Wishlist" />

        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <h2 class="font-serif text-3xl text-ink">Saved</h2>
                <p class="text-sm text-ink-muted">
                    Your favourite formulas, in one place.
                </p>
            </div>
            <WishlistContent
                :products="displayedProducts"
                :loading="isLoading"
                empty-title="Nothing saved yet"
            />
        </section>
    </template>

    <template v-else>
        <Head title="Your Wishlist" />

        <Section spacing="sm" class="border-b border-border">
            <PageHeader
                eyebrow="Wishlist"
                title="Saved for later"
                description="Your favourite formulas, in one place. Sign in to keep them across devices."
            />
        </Section>

        <Container class="py-8 lg:py-12">
            <WishlistContent
                :products="displayedProducts"
                :loading="isLoading"
                :show-browse-link="true"
                empty-title="Your wishlist is empty"
            />
        </Container>
    </template>
</template>
