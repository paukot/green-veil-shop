<?php

namespace App\Http\Requests\Checkout;

use App\Actions\Checkout\CheckoutData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'delivery_option_id' => ['required', 'integer', 'exists:delivery_options,id'],
            'payment_option_id' => ['required', 'integer', 'exists:payment_options,id'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => [
                'required',
                'string',
                'size:2',
                Rule::exists('countries', 'code')
                    ->where('is_active', true)
                    ->where('is_shipping_available', true)
                    ->whereNull('deleted_at'),
            ],
            'billing_same_as_shipping' => ['boolean'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.line1' => ['required_if:billing_same_as_shipping,false', 'nullable', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_if:billing_same_as_shipping,false', 'nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['required_if:billing_same_as_shipping,false', 'nullable', 'string', 'max:20'],
            'billing_address.country' => [
                'required_if:billing_same_as_shipping,false',
                'nullable',
                'string',
                'size:2',
                Rule::exists('countries', 'code')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
        ];
    }

    public function toCheckoutData(): CheckoutData
    {
        /** @var array<string, string|null> $shipping */
        $shipping = $this->validated('shipping_address');
        $billing = $this->boolean('billing_same_as_shipping', true)
            ? $shipping
            : ($this->validated('billing_address') ?? $shipping);

        $sessionPromoId = $this->hasSession() ? $this->session()->get('applied_promotion_id') : null;
        $promotionId = $this->filled('promotion_id')
            ? (int) $this->validated('promotion_id')
            : ($sessionPromoId ? (int) $sessionPromoId : null);

        return new CheckoutData(
            customerName: (string) $this->validated('customer_name'),
            customerEmail: (string) $this->validated('customer_email'),
            customerPhone: $this->validated('customer_phone'),
            deliveryOptionId: (int) $this->validated('delivery_option_id'),
            paymentOptionId: (int) $this->validated('payment_option_id'),
            shippingAddress: $shipping,
            billingAddress: $billing,
            notes: $this->validated('notes'),
            user: ($user = $this->user()) instanceof User ? $user : null,
            promotionId: $promotionId,
        );
    }
}
