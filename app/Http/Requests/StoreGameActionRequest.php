<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:play_card,play_mercenary'],
            'card_value' => ['required_if:type,play_card', 'integer'],
            'mercenary_id' => ['required_if:type,play_mercenary', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Action type must be one of: play_card, play_mercenary.',
        ];
    }
}
