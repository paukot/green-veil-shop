<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { TextField } from '@/components/form';
import AccountLayout from '@/layouts/AccountLayout.vue';
import {
    updateProfile as updateProfileRoute,
    updatePassword as updatePasswordRoute,
} from '@/actions/App/Http/Controllers/AccountController';

defineOptions({
    layout: AccountLayout,
});

interface ProfileProps {
    profile: {
        name: string;
        email: string;
        created_at: string;
    };
}

const props = defineProps<ProfileProps>();

const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch(updateProfileRoute.url(), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(updatePasswordRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Account Profile & Security — Green Veil" />

    <div class="space-y-8">
        <!-- Personal Details Section -->
        <section
            class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
        >
            <div class="mb-6 border-b border-cream-200 pb-4">
                <h2 class="font-serif text-xl text-sage-900 sm:text-2xl">
                    Personal Information
                </h2>
                <p class="text-charcoal-600 mt-1 text-xs">
                    Update your client account name and email address. Member
                    since {{ profile.created_at }}.
                </p>
            </div>

            <form @submit.prevent="updateProfile" class="max-w-xl space-y-5">
                <!-- Name Field -->
                <TextField
                    id="name"
                    v-model="profileForm.name"
                    type="text"
                    label="Full Name"
                    variant="uppercase"
                    autocomplete="name"
                    required
                    :error="profileForm.errors.name"
                />

                <!-- Email Field -->
                <TextField
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    label="Email Address"
                    variant="uppercase"
                    autocomplete="email"
                    required
                    :error="profileForm.errors.email"
                />

                <div class="flex items-center gap-4 pt-2">
                    <button
                        type="submit"
                        :disabled="
                            profileForm.processing || !profileForm.isDirty
                        "
                        class="flex items-center gap-2 rounded-xl bg-sage-800 px-6 py-2.5 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="profileForm.processing"
                            class="mr-1.5 -ml-1 h-3.5 w-3.5 animate-spin text-white"
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
                            profileForm.processing
                                ? 'Saving...'
                                : 'Save Profile'
                        }}</span>
                    </button>

                    <span
                        v-if="profileForm.recentlySuccessful"
                        class="animate-fade-in text-xs font-medium text-sage-700"
                    >
                        Saved successfully!
                    </span>
                </div>
            </form>
        </section>

        <!-- Password & Security Section -->
        <section
            class="rounded-2xl border border-cream-200 bg-white p-6 shadow-xs sm:p-8"
        >
            <div class="mb-6 border-b border-cream-200 pb-4">
                <h2 class="font-serif text-xl text-sage-900 sm:text-2xl">
                    Security & Password
                </h2>
                <p class="text-charcoal-600 mt-1 text-xs">
                    Ensure your account is using a long, random password to stay
                    secure.
                </p>
            </div>

            <form @submit.prevent="updatePassword" class="max-w-xl space-y-5">
                <!-- Current Password -->
                <TextField
                    id="current_password"
                    v-model="passwordForm.current_password"
                    type="password"
                    label="Current Password"
                    variant="uppercase"
                    autocomplete="current-password"
                    required
                    placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    :error="passwordForm.errors.current_password"
                />

                <!-- New Password -->
                <TextField
                    id="password"
                    v-model="passwordForm.password"
                    type="password"
                    label="New Password"
                    variant="uppercase"
                    autocomplete="new-password"
                    required
                    placeholder="At least 8 characters"
                    :error="passwordForm.errors.password"
                />

                <!-- Confirm Password -->
                <TextField
                    id="password_confirmation"
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    label="Confirm New Password"
                    variant="uppercase"
                    autocomplete="new-password"
                    required
                    placeholder="Repeat new password"
                    :error="passwordForm.errors.password_confirmation"
                />

                <div class="flex items-center gap-4 pt-2">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="flex items-center gap-2 rounded-xl bg-sage-800 px-6 py-2.5 text-xs font-medium tracking-wider text-cream-50 uppercase shadow-xs transition-colors hover:bg-sage-900 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="passwordForm.processing"
                            class="mr-1.5 -ml-1 h-3.5 w-3.5 animate-spin text-white"
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
                            passwordForm.processing
                                ? 'Updating...'
                                : 'Update Password'
                        }}</span>
                    </button>

                    <span
                        v-if="passwordForm.recentlySuccessful"
                        class="animate-fade-in text-xs font-medium text-sage-700"
                    >
                        Password updated!
                    </span>
                </div>
            </form>
        </section>
    </div>
</template>
