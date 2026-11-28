<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    FileTextIcon,
    HeartIcon,
    LogOutIcon,
    MapPinIcon,
    UserIcon,
} from '@lucide/vue';
import Container from '@/components/ui/Container.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { logout } from '@/routes';
import { profile, rituals } from '@/routes/account';
import { index as addressesIndex } from '@/routes/account/addresses';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as favouritesIndex } from '@/routes/favourites';
import { cn } from '@/lib/utils';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const navigation = computed(() => [
    {
        label: 'Profile',
        href: profile.url(),
        icon: UserIcon,
        active: page.url.startsWith(profile.url()),
    },
    {
        label: 'Addresses',
        href: addressesIndex.url(),
        icon: MapPinIcon,
        active: page.url.startsWith(addressesIndex.url()),
    },
    {
        label: 'Orders',
        href: ordersIndex.url(),
        icon: FileTextIcon,
        active: page.url.startsWith(ordersIndex.url()),
    },
    {
        label: 'Saved',
        href: rituals.url(),
        icon: HeartIcon,
        active:
            page.url.startsWith(rituals.url()) ||
            page.url.startsWith(favouritesIndex.url()),
    },
]);
</script>

<template>
    <StorefrontLayout>
        <Container
            class="flex flex-col gap-8 py-8 lg:gap-12 lg:py-12 print:p-0"
        >
            <PageHeader
                eyebrow="Your account"
                :title="
                    user ? `Hello, ${user.name.split(' ')[0]}` : 'My account'
                "
                :description="user?.email"
                class="print:hidden"
            />

            <div
                class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12 print:block"
            >
                <aside
                    aria-label="Account sidebar"
                    class="lg:col-span-3 print:hidden"
                >
                    <nav
                        aria-label="Account"
                        class="-mx-4 scrollbar-none flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0 lg:sticky lg:top-sticky lg:flex-col lg:gap-1"
                    >
                        <Link
                            v-for="item in navigation"
                            :key="item.href"
                            :href="item.href"
                            :aria-current="item.active ? 'page' : undefined"
                            :class="
                                cn(
                                    'flex h-12 shrink-0 items-center gap-3 rounded-full px-4 text-sm whitespace-nowrap transition-colors lg:rounded-lg',
                                    item.active
                                        ? 'bg-rose-900 font-medium text-cream-50'
                                        : 'text-ink hover:bg-surface-sunken',
                                )
                            "
                        >
                            <component
                                :is="item.icon"
                                class="size-4"
                                aria-hidden="true"
                            />
                            {{ item.label }}
                        </Link>
                        <Link
                            :href="logout.url()"
                            method="post"
                            as="button"
                            class="flex h-12 shrink-0 items-center gap-3 rounded-full px-4 text-left text-sm whitespace-nowrap text-danger-600 transition-colors hover:bg-danger-50 lg:mt-4 lg:rounded-lg lg:border-t lg:border-border"
                        >
                            <LogOutIcon class="size-4" aria-hidden="true" />
                            Sign out
                        </Link>
                    </nav>
                </aside>

                <div class="min-w-0 lg:col-span-9">
                    <slot />
                </div>
            </div>
        </Container>
    </StorefrontLayout>
</template>
