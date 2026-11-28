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
    <Head title="Account Profile & Security" />

    <div class="flex flex-col gap-6">
        <!-- Personal Details Section -->
        <section
            class="rounded-2xl border border-border bg-surface-raised p-6 sm:p-8"
            aria-labelledby="profile-personal-heading"
        >
            <div class="mb-6 flex flex-col gap-1 border-b border-border pb-4">
                <h2
                    id="profile-personal-heading"
                    class="font-serif text-2xl text-ink"
                >
                    Personal Information
                </h2>
                <p class="text-sm text-ink-muted">
                    Update your client account name and email address. Member
                    since {{ profile.created_at }}.
                </p>
            </div>

            <form
                @submit.prevent="updateProfile"
                class="flex max-w-xl flex-col gap-4"
                aria-labelledby="profile-personal-heading"
            >
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
                        class="animate-fade-in text-xs font-medium text-rose-700"
                        role="status"
                    >
                        Saved successfully!
                    </span>
                </div>
            </form>
        </section>

        <!-- Password & Security Section -->
        <section
            class="rounded-2xl border border-border bg-surface-raised p-6 sm:p-8"
            aria-labelledby="profile-password-heading"
        >
            <div class="mb-6 flex flex-col gap-1 border-b border-border pb-4">
                <h2
                    id="profile-password-heading"
                    class="font-serif text-2xl text-ink"
                >
                    Security & Password
                </h2>
                <p class="text-sm text-ink-muted">
                    Ensure your account is using a long, random password to stay
                    secure.
                </p>
            </div>

            <form
                @submit.prevent="updatePassword"
                class="flex max-w-xl flex-col gap-4"
                aria-labelledby="profile-password-heading"
            >
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
                        class="animate-fade-in text-xs font-medium text-rose-700"
                        role="status"
                    >
                        Password updated!
                    </span>
                </div>
            </form>
        </section>
    </div>
</template>
