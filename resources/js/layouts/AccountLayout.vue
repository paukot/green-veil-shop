<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CustomerUser } from '@/types/storefront';
import { profile, rituals } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as addressesIndex } from '@/routes/account/addresses';
import { logout } from '@/routes';
import {
    FileTextIcon,
    HeartIcon,
    LogOutIcon,
    MapPinIcon,
    UserIcon,
} from '@lucide/vue';

const page = usePage<{ auth: { user: CustomerUser | null } }>();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <StorefrontLayout>
        <div
            class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 print:p-0"
        >
            <!-- Header greeting -->
            <div class="mb-8 border-b border-cream-200 pb-6 print:hidden">
                <p
                    class="text-xs font-semibold tracking-widest text-sage-600 uppercase"
                >
                    Client Sanctuary
                </p>
                <h1 class="mt-1 font-serif text-3xl text-sage-900 sm:text-4xl">
                    My Account
                </h1>
                <p v-if="user" class="text-charcoal-600 mt-1 text-xs">
                    Welcome back,
                    <span class="font-medium text-sage-800">{{
                        user.name
                    }}</span>
                    ({{ user.email }})
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-8 md:grid-cols-4 lg:gap-10 print:block"
            >
                <!-- Account Navigation Sidebar -->
                <aside class="md:col-span-1 print:hidden">
                    <nav
                        class="flex scrollbar-none flex-row items-center gap-2 overflow-x-auto rounded-2xl border border-cream-200 bg-white p-2 shadow-xs sm:p-3 md:flex-col md:items-stretch md:gap-0 md:space-y-1 md:p-4"
                        aria-label="Account navigation"
                    >
                        <Link
                            :href="profile.url()"
                            class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium whitespace-nowrap transition-colors"
                            :class="
                                $page.url.startsWith(profile.url())
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <UserIcon
                                class="h-4 w-4 shrink-0 transition-colors"
                                :class="
                                    $page.url.startsWith(profile.url())
                                        ? 'text-sage-900'
                                        : 'text-sage-600'
                                "
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <span>Profile & Security</span>
                        </Link>

                        <Link
                            :href="addressesIndex.url()"
                            class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium whitespace-nowrap transition-colors"
                            :class="
                                $page.url.startsWith(addressesIndex.url())
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <MapPinIcon
                                class="h-4 w-4 shrink-0 transition-colors"
                                :class="
                                    $page.url.startsWith(addressesIndex.url())
                                        ? 'text-sage-900'
                                        : 'text-sage-600'
                                "
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <span>Shipping Addresses</span>
                        </Link>

                        <Link
                            :href="ordersIndex.url()"
                            class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium whitespace-nowrap transition-colors"
                            :class="
                                $page.url.startsWith(ordersIndex.url())
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <FileTextIcon
                                class="h-4 w-4 shrink-0 transition-colors"
                                :class="
                                    $page.url.startsWith(ordersIndex.url())
                                        ? 'text-sage-900'
                                        : 'text-sage-600'
                                "
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <span>Order History</span>
                        </Link>

                        <Link
                            :href="rituals.url()"
                            class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium whitespace-nowrap transition-colors"
                            :class="
                                $page.url.startsWith(rituals.url()) ||
                                $page.url.startsWith('/favourites')
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <HeartIcon
                                class="h-4 w-4 shrink-0 transition-colors"
                                :class="
                                    $page.url.startsWith(rituals.url()) ||
                                    $page.url.startsWith('/favourites')
                                        ? 'text-sage-900'
                                        : 'text-sage-600'
                                "
                                :stroke-width="1.5"
                                aria-hidden="true"
                            />
                            <span>Wishlist</span>
                        </Link>

                        <div
                            class="shrink-0 border-l border-cream-200 pl-2 md:mt-2 md:border-t md:border-l-0 md:pt-2 md:pl-0"
                        >
                            <Link
                                :href="logout.url()"
                                method="post"
                                as="button"
                                class="flex min-h-[44px] w-full shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium whitespace-nowrap text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                            >
                                <LogOutIcon
                                    class="h-4 w-4 shrink-0 text-red-500"
                                    :stroke-width="1.5"
                                    aria-hidden="true"
                                />
                                <span>Sign Out</span>
                            </Link>
                        </div>
                    </nav>
                </aside>

                <!-- Content Area -->
                <div class="md:col-span-3 print:w-full">
                    <slot />
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
