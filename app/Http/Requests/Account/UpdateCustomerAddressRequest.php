<?php

namespace App\Http\Requests\Account;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');
        $user = $this->user();

        if (! ($user instanceof User) || ! ($address instanceof CustomerAddress)) {
            return false;
        }

        return $address->user_id === $user->id;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country') && is_string($this->country)) {
            $this->merge([
                'country' => strtoupper(trim($this->country)),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => [
                'required',
                'string',
                'size:2',
                Rule::exists('countries', 'code')
                    ->where('is_active', true)
                    ->where('is_shipping_available', true)
                    ->whereNull('deleted_at'),
            ],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addressData(): array
    {
        return [
            'label' => $this->filled('label') ? (string) $this->validated('label') : 'Home',
            'recipient_name' => (string) $this->validated('recipient_name'),
            'phone' => $this->validated('phone'),
            'line1' => (string) $this->validated('line1'),
            'line2' => $this->validated('line2'),
            'city' => (string) $this->validated('city'),
            'postal_code' => (string) $this->validated('postal_code'),
            'country' => strtoupper((string) $this->validated('country')),
            'is_default' => $this->boolean('is_default'),
        ];
    }
}
