<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game && $this->user()->can('start', $game);
    }

    public function rules(): array
    {
        return [];
    }
}
