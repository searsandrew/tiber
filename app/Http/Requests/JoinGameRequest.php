<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game && $this->user()->can('join', $game);
    }

    public function rules(): array
    {
        return [
            'invite_code' => ['sometimes', 'string', 'max:16'],
        ];
    }
}
