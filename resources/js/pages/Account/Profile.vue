<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { TextField } from '@/components/form';
import Button from '@/components/ui/Button.vue';
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
                    <Button
                        type="submit"
                        :disabled="!profileForm.isDirty"
                        :loading="profileForm.processing"
                        size="md"
                    >
                        {{
                            profileForm.processing
                                ? 'Saving...'
                                : 'Save Profile'
                        }}
                    </Button>

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
                    <Button
                        type="submit"
                        :loading="passwordForm.processing"
                        size="md"
                    >
                        {{
                            passwordForm.processing
                                ? 'Updating...'
                                : 'Update Password'
                        }}
                    </Button>

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
