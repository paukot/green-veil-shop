<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { TextField } from '@/components/form';
import Button from '@/components/ui/Button.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { login, register } from '@/routes';

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
    form.post(register.url(), {
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
                    <TextField
                        id="name"
                        v-model="form.name"
                        type="text"
                        label="Full Name"
                        variant="uppercase"
                        autocomplete="name"
                        required
                        placeholder="Flora Evans"
                        :error="form.errors.name"
                    />

                    <!-- Email Address -->
                    <TextField
                        id="email"
                        v-model="form.email"
                        type="email"
                        label="Email Address"
                        variant="uppercase"
                        autocomplete="email"
                        required
                        placeholder="flora@example.com"
                        :error="form.errors.email"
                    />

                    <!-- Password -->
                    <TextField
                        id="password"
                        v-model="form.password"
                        type="password"
                        label="Password"
                        variant="uppercase"
                        autocomplete="new-password"
                        required
                        placeholder="At least 8 characters"
                        :error="form.errors.password"
                    />

                    <!-- Password Confirmation -->
                    <TextField
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        label="Confirm Password"
                        variant="uppercase"
                        autocomplete="new-password"
                        required
                        placeholder="Repeat password"
                        :error="form.errors.password_confirmation"
                    />

                    <!-- Terms info -->
                    <p
                        class="text-charcoal-500 pt-1 text-[11px] leading-relaxed"
                    >
                        By creating an account, you agree to receive essential
                        order notifications and experience clean, respectful
                        botanical commerce.
                    </p>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :loading="form.processing"
                        size="md"
                        class="mt-2 w-full py-3"
                    >
                        {{
                            form.processing
                                ? 'Creating Account...'
                                : 'Create Account'
                        }}
                    </Button>
                </form>

                <div
                    class="text-charcoal-600 mt-6 border-t border-cream-200 pt-6 text-center text-xs"
                >
                    <span>Already have an account?</span>
                    <Link
                        :href="login.url()"
                        class="ml-1.5 font-semibold text-sage-800 underline underline-offset-2 hover:text-sage-900"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
