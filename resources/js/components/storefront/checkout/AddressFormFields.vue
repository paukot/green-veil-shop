<script setup lang="ts">
import { SelectField, TextField } from '@/components/form';
import type { SelectOption } from '@/components/form';

type AddressData = {
    line1: string;
    line2?: string;
    city: string;
    postal_code: string;
    country: string;
};

const props = withDefaults(
    defineProps<{
        idPrefix: string;
        errorPrefix: string;
        countryOptions: SelectOption[];
        errors: Record<string, string | undefined>;
    }>(),
    {
        idPrefix: 'address',
        errorPrefix: 'address',
    },
);

const model = defineModel<AddressData>({ required: true });
</script>

<template>
    <div class="space-y-4">
        <TextField
            :id="`${idPrefix}_line1`"
            v-model="model.line1"
            label="Address Line 1"
            required
            placeholder="42 Willowbrook Lane"
            :error="errors[`${errorPrefix}.line1`]"
        />

        <TextField
            :id="`${idPrefix}_line2`"
            v-model="model.line2"
            label="Apartment, suite, unit"
            subtext="(optional)"
            placeholder="Apt 3B"
            :error="errors[`${errorPrefix}.line2`]"
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <TextField
                :id="`${idPrefix}_city`"
                v-model="model.city"
                label="City"
                required
                placeholder="Dublin"
                :error="errors[`${errorPrefix}.city`]"
            />

            <TextField
                :id="`${idPrefix}_postal_code`"
                v-model="model.postal_code"
                label="Postal Code / Eircode"
                required
                placeholder="D02 X285"
                :error="errors[`${errorPrefix}.postal_code`]"
            />

            <SelectField
                :id="`${idPrefix}_country`"
                v-model="model.country"
                label="Country"
                required
                :options="countryOptions"
                :error="errors[`${errorPrefix}.country`]"
            />
        </div>
    </div>
</template>
