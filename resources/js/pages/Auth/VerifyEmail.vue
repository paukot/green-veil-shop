<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { MailCheckIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { send } from '@/routes/verification';

defineOptions({
    layout: StorefrontLayout,
});

defineProps<{
    email: string;
}>();

const form = useForm({});

const resend = () => {
    form.post(send.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="Confirm Your Email — Green Veil" />

    <div class="px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-md">
            <div
                class="space-y-5 rounded-2xl border border-cream-200 bg-white p-6 text-center shadow-sm sm:p-8"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sage-100 text-sage-700"
                >
                    <MailCheckIcon class="h-7 w-7" aria-hidden="true" />
                </div>

                <div class="space-y-2">
                    <h1
                        class="font-serif text-2xl tracking-tight text-sage-900 sm:text-3xl"
                    >
                        Confirm Your Email
                    </h1>
                    <p class="text-charcoal-600 text-sm">
                        We sent a confirmation link to
                        <span class="font-semibold text-charcoal-900">{{
                            email
                        }}</span
                        >. Once confirmed, any orders you placed as a guest with
                        this address will appear in your order history.
                    </p>
                </div>

                <form @submit.prevent="resend">
                    <Button
                        type="submit"
                        :loading="form.processing"
                        class="w-full"
                    >
                        Resend Confirmation Link
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
