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
    <Head title="Create an Account" />

    <div class="px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-md">
            <div class="mb-8 space-y-2 text-center">
                <p
                    class="text-xs font-semibold tracking-widest text-rose-600 uppercase"
                >
                    Join Green Veil
                </p>
                <h1
                    class="font-serif text-3xl tracking-tight text-rose-900 sm:text-4xl"
                >
                    Begin Your Ritual
                </h1>
                <p class="text-sm text-charcoal-600">
                    Create an account to save your favourites, track orders, and
                    curate your beauty cabinet.
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
                        placeholder="Emma Evans"
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
                        placeholder="emma@example.com"
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
                    <p class="pt-1 text-xs leading-relaxed text-charcoal-500">
                        By creating an account, you agree to receive essential
                        order notifications and experience clean, respectful
                        beauty shopping.
                    </p>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :loading="form.processing"
                        size="md"
                        block
                        class="mt-2"
                    >
                        {{
                            form.processing
                                ? 'Creating Account...'
                                : 'Create Account'
                        }}
                    </Button>
                </form>

                <div
                    class="mt-6 border-t border-cream-200 pt-6 text-center text-xs text-charcoal-600"
                >
                    <span>Already have an account?</span>
                    <Link
                        :href="login.url()"
                        class="ml-2 font-semibold text-rose-800 underline underline-offset-2 hover:text-rose-900"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
