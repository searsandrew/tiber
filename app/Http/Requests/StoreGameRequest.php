<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'player_count' => ['sometimes', 'integer', 'min:2'],
            'seed' => ['sometimes', 'integer'],
            'use_corporations' => ['sometimes', 'boolean'],
            'use_mercenaries' => ['sometimes', 'boolean'],
            'use_planet_abilities' => ['sometimes', 'boolean'],
            'use_admirals' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,friends,private'],
        ];
    }

    public function messages(): array
    {
        return [
            'visibility.in' => 'Visibility must be one of: public, friends, private.',
        ];
    }
}
