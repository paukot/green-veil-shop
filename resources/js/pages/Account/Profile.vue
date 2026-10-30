<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';

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
    profileForm.patch('/account/profile', {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put('/account/password', {
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
                <div>
                    <label
                        for="name"
                        class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                    >
                        Full Name
                    </label>
                    <input
                        id="name"
                        v-model="profileForm.name"
                        type="text"
                        autocomplete="name"
                        required
                        class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :class="{
                            'border-red-400 focus:ring-red-400':
                                profileForm.errors.name,
                        }"
                    />
                    <p
                        v-if="profileForm.errors.name"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ profileForm.errors.name }}
                    </p>
                </div>

                <!-- Email Field -->
                <div>
                    <label
                        for="email"
                        class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                    >
                        Email Address
                    </label>
                    <input
                        id="email"
                        v-model="profileForm.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :class="{
                            'border-red-400 focus:ring-red-400':
                                profileForm.errors.email,
                        }"
                    />
                    <p
                        v-if="profileForm.errors.email"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ profileForm.errors.email }}
                    </p>
                </div>

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
                <div>
                    <label
                        for="current_password"
                        class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                    >
                        Current Password
                    </label>
                    <input
                        id="current_password"
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :class="{
                            'border-red-400 focus:ring-red-400':
                                passwordForm.errors.current_password,
                        }"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    />
                    <p
                        v-if="passwordForm.errors.current_password"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ passwordForm.errors.current_password }}
                    </p>
                </div>

                <!-- New Password -->
                <div>
                    <label
                        for="password"
                        class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                    >
                        New Password
                    </label>
                    <input
                        id="password"
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :class="{
                            'border-red-400 focus:ring-red-400':
                                passwordForm.errors.password,
                        }"
                        placeholder="At least 8 characters"
                    />
                    <p
                        v-if="passwordForm.errors.password"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ passwordForm.errors.password }}
                    </p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label
                        for="password_confirmation"
                        class="mb-1.5 block text-xs font-medium tracking-wider text-charcoal-700 uppercase"
                    >
                        Confirm New Password
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="placeholder-charcoal-400 w-full rounded-lg border border-cream-300 bg-cream-50/50 px-3.5 py-2.5 text-sm text-charcoal-900 transition-colors focus:border-sage-500 focus:ring-2 focus:ring-sage-500 focus:outline-none"
                        :class="{
                            'border-red-400 focus:ring-red-400':
                                passwordForm.errors.password_confirmation,
                        }"
                        placeholder="Repeat new password"
                    />
                    <p
                        v-if="passwordForm.errors.password_confirmation"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ passwordForm.errors.password_confirmation }}
                    </p>
                </div>

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
