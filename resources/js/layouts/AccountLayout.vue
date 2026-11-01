<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { CustomerUser } from '@/types/storefront';
import { profile } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { index as favouritesIndex } from '@/routes/favourites';
import { index as cartIndex } from '@/routes/cart';
import { logout } from '@/routes';

const page = usePage<{ auth: { user: CustomerUser | null } }>();
const user = page.props.auth.user;
</script>

<template>
    <StorefrontLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <!-- Header greeting -->
            <div class="mb-8 border-b border-cream-200 pb-6">
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

            <div class="grid grid-cols-1 gap-8 md:grid-cols-4 lg:gap-10">
                <!-- Account Navigation Sidebar -->
                <aside class="md:col-span-1">
                    <nav
                        class="space-y-1 rounded-2xl border border-cream-200 bg-white p-3 shadow-xs sm:p-4"
                        aria-label="Account navigation"
                    >
                        <Link
                            :href="profile.url()"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium transition-colors"
                            :class="
                                $page.url.startsWith(profile.url())
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <svg
                                class="h-4 w-4 text-sage-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                />
                            </svg>
                            <span>Profile & Security</span>
                        </Link>

                        <Link
                            :href="ordersIndex.url()"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium transition-colors"
                            :class="
                                $page.url.startsWith(ordersIndex.url())
                                    ? 'bg-sage-100 font-semibold text-sage-900'
                                    : 'text-charcoal-700 hover:bg-cream-100 hover:text-sage-800'
                            "
                        >
                            <svg
                                class="h-4 w-4 text-sage-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                                />
                            </svg>
                            <span>Order History</span>
                        </Link>

                        <Link
                            :href="favouritesIndex.url()"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-800"
                        >
                            <svg
                                class="h-4 w-4 text-sage-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                />
                            </svg>
                            <span>Saved Rituals</span>
                        </Link>

                        <Link
                            :href="cartIndex.url()"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium text-charcoal-700 transition-colors hover:bg-cream-100 hover:text-sage-800"
                        >
                            <svg
                                class="h-4 w-4 text-sage-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.67 0-1.19-.578-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                                />
                            </svg>
                            <span>Shopping Bag</span>
                        </Link>

                        <div class="mt-2 border-t border-cream-200 pt-2">
                            <Link
                                :href="logout.url()"
                                method="post"
                                as="button"
                                class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                            >
                                <svg
                                    class="h-4 w-4 text-red-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"
                                    />
                                </svg>
                                <span>Sign Out</span>
                            </Link>
                        </div>
                    </nav>
                </aside>

                <!-- Content Area -->
                <div class="md:col-span-3">
                    <slot />
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
