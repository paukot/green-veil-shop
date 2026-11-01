<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckboxField, TextField } from '@/components/form';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { login, register } from '@/routes';

defineOptions({
    layout: StorefrontLayout,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(login.url(), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Sign In — Green Veil" />

    <div class="px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-md">
            <div class="mb-8 space-y-2 text-center">
                <p
                    class="text-xs font-semibold tracking-widest text-sage-600 uppercase"
                >
                    Customer Sanctuary
                </p>
                <h1
                    class="font-serif text-3xl tracking-tight text-sage-900 sm:text-4xl"
                >
                    Sign In to Your Account
                </h1>
                <p class="text-charcoal-600 text-sm">
                    Access your botanical rituals, saved bag, and order history.
                </p>
            </div>

            <div
                class="rounded-2xl border border-cream-200 bg-white p-6 shadow-sm sm:p-8"
            >
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- General / throttled error -->
                    <div
                        v-if="form.errors.email && !form.email"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700"
                        role="alert"
                    >
                        {{ form.errors.email }}
                    </div>

                    <!-- Email Input -->
                    <TextField
                        id="email"
                        v-model="form.email"
                        type="email"
                        label="Email Address"
                        variant="uppercase"
                        autocomplete="email"
                        required
                        placeholder="you@example.com"
                        :error="form.errors.email"
                    />

                    <!-- Password Input -->
                    <TextField
                        id="password"
                        v-model="form.password"
                        type="password"
                        label="Password"
                        variant="uppercase"
                        autocomplete="current-password"
                        required
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        :error="form.errors.password"
                    />

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between">
                        <CheckboxField
                            id="remember"
                            v-model="form.remember"
                            label="Remember this device"
                        />
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-sage-800 px-4 py-3 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-sm transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-50"
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
                            form.processing ? 'Signing In...' : 'Sign In'
                        }}</span>
                    </button>
                </form>

                <div
                    class="text-charcoal-600 mt-6 border-t border-cream-200 pt-6 text-center text-xs"
                >
                    <span>New to Green Veil?</span>
                    <Link
                        :href="register.url()"
                        class="ml-1.5 font-semibold text-sage-800 underline underline-offset-2 hover:text-sage-900"
                    >
                        Create an account
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
