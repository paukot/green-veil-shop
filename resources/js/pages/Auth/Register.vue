<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';

defineOptions({
    layout: StorefrontLayout,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Create an Account — Green Veil" />

    <div class="px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-md">
            <div class="mb-8 space-y-2 text-center">
                <p
                    class="text-xs font-semibold tracking-widest text-sage-600 uppercase"
                >
                    Join Green Veil
                </p>
                <h1
                    class="font-serif text-3xl tracking-tight text-sage-900 sm:text-4xl"
                >
                    Begin Your Ritual
                </h1>
                <p class="text-charcoal-600 text-sm">
                    Create an account to save botanical favourites, track
                    orders, and curate your beauty cabinet.
                </p>
            </div>

            <div
                class="rounded-2xl border border-cream-200 bg-white p-6 shadow-sm sm:p-8"
            >
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                        >
                            Full Name
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            autocomplete="name"
                            required
                            class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            :class="{
                                'border-red-400 focus:ring-red-400':
                                    form.errors.name,
                            }"
                            placeholder="Flora Evans"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1.5 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                        >
                            Email Address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            :class="{
                                'border-red-400 focus:ring-red-400':
                                    form.errors.email,
                            }"
                            placeholder="flora@example.com"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1.5 text-xs text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            :class="{
                                'border-red-400 focus:ring-red-400':
                                    form.errors.password,
                            }"
                            placeholder="At least 8 characters"
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1.5 text-xs text-red-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                        >
                            Confirm Password
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                            :class="{
                                'border-red-400 focus:ring-red-400':
                                    form.errors.password_confirmation,
                            }"
                            placeholder="Repeat password"
                        />
                        <p
                            v-if="form.errors.password_confirmation"
                            class="mt-1.5 text-xs text-red-600"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Terms info -->
                    <p
                        class="text-charcoal-500 pt-1 text-[11px] leading-relaxed"
                    >
                        By creating an account, you agree to receive essential
                        order notifications and experience clean, respectful
                        botanical commerce.
                    </p>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-sage-800 px-4 py-3 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-sm transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="form.processing"
                            class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>{{
                            form.processing
                                ? 'Creating Account...'
                                : 'Create Account'
                        }}</span>
                    </button>
                </form>

                <div
                    class="text-charcoal-600 mt-6 border-t border-cream-200 pt-6 text-center text-xs"
                >
                    <span>Already have an account?</span>
                    <Link
                        href="/login"
                        class="ml-1.5 font-semibold text-sage-800 underline underline-offset-2 hover:text-sage-900"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
