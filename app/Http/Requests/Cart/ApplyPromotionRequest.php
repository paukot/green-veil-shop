<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApplyPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
        ];
    }

    public function promotionCode(): ?string
    {
        $code = $this->validated('code');

        return ! empty($code) ? trim((string) $code) : null;
    }

    public function promotionId(): ?int
    {
        $id = $this->validated('promotion_id');

        return ! empty($id) ? (int) $id : null;
    }
}
