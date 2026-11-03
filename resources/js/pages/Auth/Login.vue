<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckboxField, TextField } from '@/components/form';
import Button from '@/components/ui/Button.vue';
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
                    <Button
                        type="submit"
                        :loading="form.processing"
                        size="md"
                        class="w-full py-3"
                    >
                        {{ form.processing ? 'Signing In...' : 'Sign In' }}
                    </Button>
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
